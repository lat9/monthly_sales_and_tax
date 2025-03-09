<?php
/**
 * Monthly Sales and Tax Summary mod for Zen Cart
 * Version 3.0.0
 * @copyright Portions Copyright 2004-2024 Zen Cart Team
 * @author Vinos de Frutas Tropicales (lat9)
****************************************************************************
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, version 2 of the License.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************

  Copyright 2013-2025 Vinos de Frutas Tropicales (lat9)
  Copyright 2003-2005 Zen Cart Development Team
  Portions Copyright 2004 osCommerce
  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com
  Copyright (c) 2004 osCommerce
  Released under the GNU General Public License

  Orginal OSC contributed by Fritz Clapp <fritz@sonnybarger.com>

  2021-12-13 (lat9):
    -
  2014-07-23 (lat9):
    - Converted to use $db for SQL actions, as preventative measure for PHP 5.5 deprecation of mysql_* functions
    - Converted explicit <input type="hidden" ...> to zen_draw_hidden_field calls
    - Converted explicit <form ...> to zen_draw_form calls
    - Corrected PHP warning on CSV download (file name issue)
    - Converted use of $_SERVER variables to zen-cart functions
    - Moved all language phrases to the language file
    - Converted to use Zen Cart rounding function
    - Added formatting strings for title of popup tax details title
    - Added configuration switch to control whether or not S/H charges are added into the store's taxed/untaxed totals
    - PHP 5.4-ready (remove/change ereg* calls)
    - Use DEFAULT_CURRENCY decimal places for number_format
    - Added separate columns for Gift Vouchers and Coupons; non-core Order Totals summed in "Other" column
  Ported to ZenCart 1.50.RC1/2 SkipWater <skip@ccssinc.net> 11.24.2011
  Ported to ZenCart 1.3.8a SkipWater <skip@ccssinc.net> 06.23.08

  This report displays a summary of monthly or daily totals:
  gross income (order totals)
  subtotals of all orders in the selected period
  nontaxed sales subtotals
  taxed sales subtotals
  tax collected
  shipping/handling charges
  low order fees (if present)
  gift vouchers (or other addl order total component, if present)

The data comes from the orders and orders_total tables.

Data is reported as of order purchase date.

If an order status is chosen, the report summarizes orders with that status.

The capability to "drill down" on any month to report the daily summary for that month.

Report rows are initially shown in newest to oldest, top to bottom,
but this order may be inverted by clicking the "Invert" control button.

A popup display that lists the various types (and their
subtotals) comprising the tax values in the report rows.

Columns that summarize nontaxed and taxed order subtotals.
The taxed column summarizes subtotals for orders in which tax was charged.
The nontaxed column is the subtotal for the row less the taxed column value.

used class="pageHeading"
   class="smallText"
   class="dataTableRow"
    class="dataTableHeadingRow"
    class="dataTableHeadingContent"

A popup help display window on how to use.

*/
require 'includes/application_top.php';

// -----
// Set the boolean flag indicating whether to 'invert' the order of the displayed totals.
//
$invert = (isset($_GET['invert']) && $_GET['invert'] === 'yes') ? '&invert=yes' : '';

// -----
// See if this is monthly detail request (i.e. $_GET['month'] is set).  If so:
//
// - The month value must be numeric and between 1 and 12.
// - $_GET['year'] must also be set and contain a 4-character numeric value.
//
$sel_month = '0';
$sel_year = '0';
if (!empty($_GET['month']) && ctype_digit($_GET['month']) && !empty($_GET['year']) && ctype_digit($_GET['year'])) {
    if ($_GET['month'] < 1 || $_GET['month'] > 12 || strlen($_GET['year']) !== 4) {
        unset($_GET['month'], $_GET['year']);
    } else {
        $sel_month = $_GET['month'];
        $sel_year = $_GET['year'];
    }
}

// -----
// See if a specific order-status has been requested.
//
$status = (isset($_GET['status']) && ctype_digit($_GET['status'])) ? $_GET['status'] : '0';

// -----
// Get a list of orders_status names for dropdown selection and capture the 'name' of
// any currently-selected order-status.
//
$orders_statuses = [
    ['id' => '0', 'text' => TEXT_ALL_ORDERS],
];
$orders_status_name = '';
$orders_status = $db->Execute(
    "SELECT orders_status_id, orders_status_name
       FROM " . TABLE_ORDERS_STATUS . "
      WHERE language_id = " . $_SESSION['languages_id'] . "
      ORDER BY sort_order, orders_status_id"
);
foreach ($orders_status as $next_status) {
    $orders_statuses[] = [
        'id' => $next_status['orders_status_id'],
        'text' => $next_status['orders_status_name'] . ' [' . $next_status['orders_status_id'] . ']'
    ];
    if ($next_status['orders_status_id'] === $status) {
        $orders_status_name = $next_status['orders_status_name'];
    }
}
unset($orders_status, $next_status);

// -----
// Note the current status being requested and use it only if it's a valid orders_status_id.
//
if ($orders_status_name === '') {
    $status = '0';
    unset($_GET['status']);
}

// -----
// We've got all the parameters to generate the report, create an instance of the Monthly Sales' helper-class
// and get the information to be displayed or downloaded.
//
require DIR_WS_CLASSES . 'MonthlySalesAndTax.php';
$sms = new MonthlySalesAndTax($status, ($invert === '') ? 'DESC' : 'ASC', $sel_year, $sel_month);
$sales = $sms->createReport();

// -----
// If the output is to be saved as a comma-separated-value (CSV) file ...
//
if (isset($_GET['csv']) && $_GET['csv'] === 'yes' && count($sales) !== 0) {
    $savename = 'monthlysales_status_' . $status . '_date_' . $sel_year . '_' . $sel_month . date('_Ymd_His') . '.csv';
    header("Expires: 0");
    header("Pragma: no-cache");
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=$savename");

    $fh = fopen('php://output', 'w');
    if ($fh) {
        // -----
        // Output header line, containing keys in the current sales' data.
        //
        fputcsv($fh, array_keys($sales[array_key_first($sales)]));

        // -----
        // Output each sale as a line in the .csv.
        //
        foreach ($sales as $sale) {
            fputcsv($fh, $sale);
        }

        fclose($fh);
    }
    session_write_close();
    exit;
}

// -----
// Main entry for report display
//
?>
<!doctype html>
<html <?php echo HTML_PARAMS; ?>>
<head>
    <?php require DIR_WS_INCLUDES . 'admin_html_head.php'; ?>
    <style>
        .fw-bold { font-weight: bold; }
        .mt-3 { margin-top: 1rem; }
        .ml-3 { margin-left: 1rem; }
        @media print {
            body > div:first-child, .headerBar, #footer { display: none; }
        }
    </style>
</head>
<body>
<?php
require DIR_WS_INCLUDES . 'header.php';

// -----
// Set the processing flag that indicates that the report generated is monthly (otherwise, it's a daily report
// for a selected month).
//
$is_monthly_report = $sms->isMonthlyReport();
if ($is_monthly_report === false) {
    $subtitle_value = $sms->getMonthName($sel_month) . ', ' . $sel_year;
} else {
    $subtitle_value = TEXT_ALL_ORDERS;
}
if ($orders_status_name !== '') {
    $subtitle_value .= sprintf(HEADING_SUBTITLE_STATUS, $orders_status_name);
}
?>
<div class="container-fluid">
<?php
// -----
// A modal popup that contains the report's help-text.  Displayed when an admin
// clicks the "Help" icon at the top-right of the display.
//
?>
    <div id="smsHelp" class="modal fade noprint" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><?php echo HELP_CONTENT_HEADER; ?></h4>
                </div>
                <div class="modal-body"><?php echo HELP_CONTENT_HTML; ?></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo HELP_CLOSE; ?></button>
                </div>
            </div>

        </div>
    </div>
<?php
// -----
// Another modal popup, this time controlled by the report's AJAX processing.  See /includes/javascript/stats_monthly_sales.js
// for additional processing information.
//
?>
    <div id="smsTaxes" class="modal fade noprint" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title" id="sms-tax-title"></h4>
                </div>
                <div class="modal-body" id="sms-tax-body"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo HELP_CLOSE; ?></button>
                </div>
            </div>

        </div>
    </div>
<?php
// -----
// Display a "Help" button that will cause the help-modal to display on click.
//
?>
    <div class="pull-right noprint">
        <button type="button" class="btn btn-info" data-toggle="modal" data-target="#smsHelp" title="<?php echo TEXT_BUTTON_REPORT_HELP_DESC; ?>"><i class="fa fa-question fa-lg" aria-hidden="true"></i></button>
    </div>
<?php
// -----
// Starting the main body of the report, showing the page-heading and a form-row where the admin
// can choose a specific orders-status to filter and/or identify the output ordering of the report.
//
// Also includes buttons to "Print" or "Save CSV" the current output.
//
?>
    <h1><?php echo HEADING_TITLE . ' ' . SMS_VERSION; ?></h1>
    <h2><?php echo sprintf(HEADING_SUBTITLE, $subtitle_value); ?></h2>
    <div class="row noprint">
        <?php echo zen_draw_form('status', FILENAME_STATS_MONTHLY_SALES, zen_get_all_get_params(['status', 'invert', 'csv']), 'get', 'class="form-inline"'); ?>
            <?php echo zen_draw_hidden_field('year', $sel_year) . zen_draw_hidden_field('month', $sel_month); ?>
            <div class="form-group">
                <?php echo zen_draw_label(HEADING_TITLE_STATUS, 'selectstatus'); ?>
                <?php echo zen_draw_pull_down_menu('status', $orders_statuses, $status, 'class="form-control" id="selectstatus"'); ?>
            </div>
            <div class="form-group">
                <label for="invert"><?php echo zen_draw_checkbox_field('invert', 'yes', !empty($invert), '', 'id="invert"') . '&nbsp;' . TEXT_BUTTON_REPORT_INVERT; ?></label>
            </div>
            <button type="submit" class="btn btn-primary"><?php echo IMAGE_GO; ?></button>

            <a class="btn btn-info ml-3" href="javascript:window.print();" role="button" title="<?php echo TEXT_BUTTON_REPORT_HELP_DESC; ?>"><?php echo TEXT_BUTTON_REPORT_PRINT; ?></a>
            <button type="submit" class="btn btn-info ml-3" name="csv" value="yes"><?php echo TEXT_BUTTON_REPORT_SAVE; ?></button>
        <?php echo '</form>'; ?>
    </div>

    <table class="table table-hover mt-3">
        <thead>
            <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent text-center"><?php echo TABLE_HEADING_MONTH; ?></td>
                <td class="dataTableHeadingContent text-center"><?php echo ($is_monthly_report === true) ? TABLE_HEADING_YEAR : TABLE_HEADING_DAY; ?></td>
                <td class="dataTableHeadingContent text-right"><?php echo TABLE_HEADING_INCOME; ?></td>
                <td class="dataTableHeadingContent text-right"><?php echo TABLE_HEADING_SALES; ?></td>
                <td class="dataTableHeadingContent text-right"><?php echo TABLE_HEADING_NONTAXED; ?></td>
                <td class="dataTableHeadingContent text-right"><?php echo TABLE_HEADING_TAXED; ?></td>
                <td class="dataTableHeadingContent text-right"><?php echo TABLE_HEADING_TAX_COLL; ?></td>
                <td class="dataTableHeadingContent text-right"><?php echo TABLE_HEADING_SHIPHNDL; ?></td>
<?php
$column_count = 8;
if ($sms->usingLowOrder()) {
    $column_count++;
?>
                <td class="dataTableHeadingContent text-right"><?php echo TABLE_HEADING_LOWORDER; ?></td>
<?php
}
if ($sms->usingGiftVouchers()) {
    $column_count++;
?>
                <td class="dataTableHeadingContent text-right"><?php echo TABLE_HEADING_VOUCHER; ?></td>
<?php
}
if ($sms->usingCoupons()) {
    $column_count++;
?>
                <td class="dataTableHeadingContent text-right"><?php echo TABLE_HEADING_COUPON; ?></td>
<?php
}
if ($sms->usingAdditionalTotals()) {
    $column_count++;
?>
                <td class="dataTableHeadingContent text-right"><?php echo TABLE_HEADING_OTHER; ?></td>
<?php
}
?>
            </tr>
        </thead>
        <tbody>
<?php
if (count($sales) === 0) {
?>
            <tr class="dataTableContent text-center">
                <td colspan="<?php echo $column_count; ?>"><?php echo TEXT_NOTHING_FOUND; ?></td>
            </tr>
<?php
} else {
    $default_totals = [
        'gross_sales' => 0,
        'products_total' => 0,
        'products_taxed' => 0,
        'products_untaxed' => 0,
        'tax' => 0,
        'shipping' => 0,
        'loworder' => 0,
        'gv' => 0,
        'coupon' => 0,
        'other' => 0,
    ];
    $totals = $default_totals;
    foreach ($sales as $key => $sale) {
        // -----
        // Set the current report year (current 'row') on initial entry.  Whenever the report's
        // year changes, output a total-row for that year and reset the associated totals.
        //
        if (!isset($totals['year'])) {
            $totals['year'] = $sale['year'];
            $totals['monthname'] = $sms->getMonthName($sale['month']);
        } elseif ($totals['year'] !== $sale['year']) {
?>
            <tr class="dataTableHeadingRow fw-bold">
                <td class="dataTableContent text-center"><?php echo ($is_monthly_report === true) ? TABLE_FOOTER_YEAR : $totals['monthname']; ?></td>
                <td class="dataTableContent text-center"><?php echo $totals['year']; ?></td>
                <td class="dataTableContent text-right"><?php echo $sms->formatValue($totals['gross_sales']); ?></td>
                <td class="dataTableContent text-right"><?php echo $sms->formatValue($totals['products_total']); ?></td>
                <td class="dataTableContent text-right"><?php echo $sms->formatValue($totals['products_untaxed']); ?></td>
                <td class="dataTableContent text-right"><?php echo $sms->formatValue($totals['products_taxed']); ?></td>
                <td class="dataTableContent text-right"><?php echo $sms->formatValue($totals['tax']); ?></td>
                <td class="dataTableContent text-right"><?php echo $sms->formatValue($totals['shipping']); ?></td>
<?php
            if ($sms->usingLowOrder()) {
?>
                <td class="dataTableContent text-right"><?php echo $sms->formatValue($totals['loworder']); ?></td>
<?php
            }
            if ($sms->usingGiftVouchers()) {
?>
                <td class="dataTableContent text-right"><?php echo $sms->formatValue($totals['gv']); ?></td>
<?php
            }
            if ($sms->usingCoupons()) {
?>
                <td class="dataTableContent text-right"><?php echo $sms->formatValue($totals['coupon']); ?></td>
<?php
            }
            if ($sms->usingAdditionalTotals()) {
?>
                <td class="dataTableContent text-right"><?php echo $sms->formatValue($totals['other']); ?></td>
<?php
            }
?>
            </tr>
<?php
            $totals = $default_totals;
        }

        // -----
        // Output the current 'row' of the report.  Note that the data-* values are used by the report's jQuery
        // processing (/includes/javascript/stats_monthly_sales.js) to request tax details for a selected
        // period.
        //
        if ($is_monthly_report === false) {
            $month_output = '&nbsp;';
            $data_day = ' data-day="' . $sale['day'] . '"';
        } else {
            $month_output = '<a href="' . zen_href_link(FILENAME_STATS_MONTHLY_SALES, zen_get_all_get_params(['month', 'year']) . 'month=' . $sale['month'] . '&year=' . $sale['year']) . '"  title="' . TEXT_BUTTON_REPORT_GET_DETAIL . '">' . $sms->getMonthName($sale['month']) . '</a>';
            $data_day = '';
        }

        // -----
        // Account for totals that might not be present for the given time period.
        //
        foreach ($default_totals as $key => $notused) {
            if (!isset($sale[$key])) {
                $sale[$key] = 0;
            }
        }
?>
            <tr class="dataTableRow">
                <td class="dataTableContent text-center"><?php echo $month_output; ?></td>
                <td class="dataTableContent text-center"><?php echo ($is_monthly_report === true) ? $sale['year'] : $sale['day']; ?></td>
                <td class="dataTableContent text-right"><?php echo $sale['gross_sales']; ?></td>
                <td class="dataTableContent text-right"><?php echo $sale['products_total']; ?></td>
                <td class="dataTableContent text-right"><?php echo $sale['products_untaxed']; ?></td>
                <td class="dataTableContent text-right"><?php echo $sale['products_taxed']; ?></td>
                <td class="dataTableContent text-right"><a href="#" class="sms-tax" data-year="<?php echo $sale['year']; ?>" data-month="<?php echo $sale['month']; ?>" <?php echo $data_day; ?>><?php echo ($sale['tax'] === 0) ? '' : $sale['tax']; ?></a></td>
                <td class="dataTableContent text-right"><?php echo $sale['shipping']; ?></td>
<?php
        if ($sms->usingLowOrder()) {
            $totals['loworder'] += $sale['loworder'];
?>
                <td class="dataTableContent text-right"><?php echo $sale['loworder']; ?></td>
<?php
        }
        if ($sms->usingGiftVouchers()) {
            $totals['gv'] += $sale['gv'];
?>
                <td class="dataTableContent text-right"><?php echo $sale['gv']; ?></td>
<?php
        }
        if ($sms->usingCoupons()) {
            $totals['coupon'] += $sale['coupon'];
?>
                <td class="dataTableContent text-right"><?php echo $sale['coupon']; ?></td>
<?php
        }
        if ($sms->usingAdditionalTotals()) {
            $totals['other'] += $sale['other'];
?>
                <td class="dataTableContent text-right"><?php echo $sale['other']; ?></td>
<?php
        }
?>
            </tr>
<?php
        // -----
        // Accumulate always-preent totals for the current timeframe.
        //
        $totals['gross_sales'] += $sale['gross_sales'];
        $totals['products_taxed'] += $sale['products_taxed'];
        $totals['products_untaxed'] += $sale['products_untaxed'];
        $totals['products_total'] += $sale['products_total'];
        $totals['tax'] += $sale['tax'];
        $totals['shipping'] += $sale['shipping'];
    }
?>
            <tr class="dataTableHeadingRow fw-bold">
                <td class="dataTableContent text-center"><?php echo ($is_monthly_report === true) ? TABLE_FOOTER_YEAR : $totals['monthname']; ?></td>
                <td class="dataTableContent text-center"><?php echo $totals['year']; ?></td>
                <td class="dataTableContent text-right"><?php echo $sms->formatValue($totals['gross_sales']); ?></td>
                <td class="dataTableContent text-right"><?php echo $sms->formatValue($totals['products_total']); ?></td>
                <td class="dataTableContent text-right"><?php echo $sms->formatValue($totals['products_untaxed']); ?></td>
                <td class="dataTableContent text-right"><?php echo $sms->formatValue($totals['products_taxed']); ?></td>
                <td class="dataTableContent text-right"><?php echo $sms->formatValue($totals['tax']); ?></td>
                <td class="dataTableContent text-right"><?php echo $sms->formatValue($totals['shipping']); ?></td>
<?php
    if ($sms->usingLowOrder()) {
?>
                <td class="dataTableContent text-right"><?php echo $sms->formatValue($totals['loworder']); ?></td>
<?php
    }
    if ($sms->usingGiftVouchers()) {
?>
                <td class="dataTableContent text-right"><?php echo $sms->formatValue($totals['gv']); ?></td>
<?php
    }
    if ($sms->usingCoupons()) {
?>
                <td class="dataTableContent text-right"><?php echo $sms->formatValue($totals['coupon']); ?></td>
<?php
    }
    if ($sms->usingAdditionalTotals()) {
?>
                <td class="dataTableContent text-right"><?php echo $sms->formatValue($totals['other']); ?></td>
<?php
    }
?>
            </tr>
<?php
}
?>
        </tbody>
    </table>
<?php
if ($is_monthly_report === false) {
?>
    <div class="row"><a href="<?php echo zen_href_link(FILENAME_STATS_MONTHLY_SALES, zen_get_all_get_params(['year', 'month', 'csv'])); ?>" role="button" class="btn btn-default" title="<?php echo TEXT_BUTTON_REPORT_BACK_DESC; ?>"><?php echo IMAGE_BACK; ?></a></div>
<?php
}

require DIR_WS_INCLUDES . 'footer.php';
?>
</body>
</html>
<?php
require DIR_WS_INCLUDES . 'application_bottom.php';
