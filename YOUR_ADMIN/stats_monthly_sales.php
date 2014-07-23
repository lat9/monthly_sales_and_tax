<?php
/*
  $Id: stats_monthly_sales.php, v1.5.0 2014/07/23  $

  Copyright 2013-2014 Vinos de Frutas Tropicales (lat9)
  Copyright 2003-2005 Zen Cart Development Team
  Portions Copyright 2004 osCommerce
  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com
  Copyright (c) 2004 osCommerce
  Released under the GNU General Public License

  Orginal OSC contributed by Fritz Clapp <fritz@sonnybarger.com>

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

require('includes/application_top.php');

// Function used to format report
function mirror_out ($field) {
  global $csv_accum;
  echo $field;
  $field = str_replace (',', '', strip_tags($field));
  if ($csv_accum == '') {
    $csv_accum = $field; 
  } else {
    if (strrpos($csv_accum, chr(10)) == (strlen($csv_accum) - 1)) {
      $csv_accum .= $field;
    } else {
      $csv_accum .= ',' . $field; 
    }
  }
  return;
}

// Function that creates the common elements of the raw SQL row query
function add_status_and_daterange($query_raw, $status, $sales_query, $sel_month, $ot_class='') {
  $query_raw .= ($ot_class != '') ? (" WHERE ot.class = $ot_class ") : '';
  $query_raw .= ($status != '') ? (" AND o.orders_status ='" . zen_db_prepare_input($status) . "'") : '';
  $query_raw .= SALES_QUERY_ADDITIONAL;

  $query_raw .= " AND o.date_purchased BETWEEN '" . $sales_query->fields['row_year'] . "-" . $sales_query->fields['i_month'] . "-01' 
                  AND '" . $sales_query->fields['row_year'] . "-" . $sales_query->fields['i_month'] . "-31 23:59'";
  $query_raw .= ($sel_month != 0) ? " AND dayofmonth(o.date_purchased) = '" . $sales_query->fields['row_day'] . "'" : '';
  
  return $query_raw;
}

// Function to create debug-output file, if debug is enabled
function sms_debug($string) {
  if (defined('STATS_MONTHLY_SALES_DEBUG') && STATS_MONTHLY_SALES_DEBUG == 'on') {
    error_log(strftime("%Y-%m-%d %H:%M:%S") . ' ' . $string . "\n", 3, (defined('DIR_FS_LOGS') ? DIR_FS_LOGS : DIR_FS_SQL_CACHE) . '/myDEBUG-sms.log');
  }
}

require(DIR_WS_CLASSES . 'currencies.php');
$currencies = new currencies();
$decimal_places = $currencies->get_decimal_places(DEFAULT_CURRENCY);

sms_debug('ON ENTRY: using decimal places for ' . DEFAULT_CURRENCY . " ($decimal_places)" . ', $_GET (' . zen_get_all_get_params() . ')' . ((zen_not_null($_POST)) ? (', $_POST(' . print_r($_POST, true)) : ''));

// entry for help popup window
if (isset($_GET['help'])) { 
  echo TEXT_HELP;
  exit;
}

// entry for bouncing csv string back as file
if (isset($_POST['csv'])) {
  if (isset($_POST['saveas']) && $_POST['saveas'] != '') {  // rebound posted csv as save file
    $savename = $_POST['saveas'] . '.csv';
  } else {
    $savename = 'unknown.csv';
  }
  $csv_string = $_POST['csv'];
  if (strlen($csv_string) > 0) {
    header("Expires: Mon, 26 Nov 1962 00:00:00 GMT");
    header("Last-Modified: " . gmdate('D,d M Y H:i:s') . ' GMT');
    header("Cache-Control: no-cache, must-revalidate");
    header("Pragma: no-cache");
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=$savename");
    echo urlencode($csv_string);
  } else {
    echo "CSV string empty";
  }
  exit;
}

// Initialize common $_GET variables
$sel_day = (isset($_GET['day'])) ? $_GET['day'] : 0;  
$status = (isset($_GET['status'])) ? $_GET['status'] : '';

// Initialize common configuration values
$combine_shipping_totals = (defined('STATS_MONTHLY_SALES_COMBINE_SHIPPING')) ? STATS_MONTHLY_SALES_COMBINE_SHIPPING : 'auto';
switch ($combine_shipping_totals) {
  case 'on': {
    $combine_shipping_totals = true;
    break;
  }
  case 'off': {
    $combine_shipping_totals = false;
    break;
  }
  default: {
    $combine_shipping_totals = (MODULE_ORDER_TOTAL_TAX_STATUS == 'true' && 
                                  MODULE_ORDER_TOTAL_SHIPPING_STATUS == 'true' && 
                                    (((int)MODULE_ORDER_TOTAL_SHIPPING_SORT_ORDER) < ((int)MODULE_ORDER_TOTAL_TAX_SORT_ORDER))) ? true : false;
    break;
  }
}

// entry for popup display of tax detail
// show=ot_tax 
if (isset($_GET['show'])) {
  $ot_type = zen_db_prepare_input($_GET['show']);
  $sel_month = zen_db_prepare_input($_GET['month']);
  $sel_year = zen_db_prepare_input($_GET['year']);
 
  // construct query for selected detail
  $detail_query_raw = "SELECT ot.value amount, ot.title description, ot.orders_id ordernumber 
                         FROM " . TABLE_ORDERS . " o 
                         LEFT JOIN " . TABLE_ORDERS_TOTAL . " ot 
                         ON (o.orders_id = ot.orders_id) 
                         WHERE ";
  
  if ($status != '') {
    $detail_query_raw .= "o.orders_status ='" . $status . "' AND ";
  }
  
  $detail_query_raw .= "ot.class = '" . $ot_type . "' AND month(o.date_purchased)= '" . $sel_month . "' AND year(o.date_purchased)= '" . $sel_year . "'";
  
  if ($sel_day != 0) {
    $detail_query_raw .= " AND dayofmonth(o.date_purchased) = '" . $sel_day . "'";
  }

  $detail_query = $db->Execute($detail_query_raw);
?>
<!DOCTYPE HTML PUBLIC "-/W3C/DTD HTML 4.01 Transitional/EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset="<?php echo CHARSET; ?>">
<title><?php echo TEXT_DETAIL; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
</head>
<body>
<br />
<table width="98%" align="center">
  <caption align="center">
<?php
  echo '<strong>' . sprintf((($sel_day != 0) ? FORMAT_YYYYMMDD : FORMAT_YYYYMM), $sel_year, $sel_month, $sel_day) . '</strong>';
  if ($status != '') {
    echo '<br />' . HEADING_TITLE_STATUS . ':&nbsp;' . $status;
  }
  echo '</caption>';
  $amount_total = 0;  
  while (!$detail_query->EOF) {
?>
  <tr class="dataTableRow">
    <td align="left"><?php echo TEXT_ORDER_NUM . $detail_query->fields['ordernumber']; ?></td>
    <td align="left"><?php echo $detail_query->fields['description']; ?>&nbsp;</td>
    <td align="center"><?php $current_amount = number_format($detail_query->fields['amount'], $decimal_places); echo $current_amount; $amount_total += $current_amount; ?></td>
  </tr>
<?php
    $detail_query->MoveNext();
  }
?>
  <tr class="dataTableRow">
    <td align="right" colspan="2"><strong><?php echo TEXT_TOTAL; ?></strong></td>
    <td align="center"><?php echo $amount_total; ?></td>
  </tr>
</table>
</body>
<?php
  exit;
}

//
// main entry for report display
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<link rel="stylesheet" type="text/css" media="print" href="includes/stylesheet_print.css">
<link rel="stylesheet" type="text/css" href="includes/cssjsmenuhover.css" media="all" id="hoverJS">
<script type="text/javascript" src="includes/menu.js"></script>
<script type="text/javascript" src="includes/general.js"></script>
<script type="text/javascript">
  <!--
  function init()
  {
    cssjsmenu('navbar');
    if (document.getElementById)
    {
      var kill = document.getElementById('hoverJS');
      kill.disabled = true;
    }
  }
  // -->
</script>

</head>
<body onload="init()">
<?php
// set printer-friendly toggle
$print = (zen_db_prepare_input($_GET['print'] == 'yes')) ? true : false;
// set inversion toggle
$invert = (isset($_GET['invert']) && $_GET['invert'] == 'yes') ? '&invert=yes' : '';
?>
<!-- header //-->
<?php
if (!$print) {
  require(DIR_WS_INCLUDES . 'header.php');
}
?>
<!-- header_eof //-->
<!-- body //-->
<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>
<!-- body_text //-->
    <td width="100%" valign="top">
      <table border="0" width="100%" cellspacing="0" cellpadding="2">
        <tr>
          <td>
            <table border="0" width="100%" cellspacing="0" cellpadding="0">
<?php 
if ($print) {
?>
              <tr><td class="pageHeading"><?php echo STORE_NAME; ?></td></tr>
<?php
}
?>
              <tr>
                <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
<?php 
// detect whether this is monthly detail request
$sel_month = 0;
if ($_GET['month'] && $_GET['year']) {
  $sel_month = zen_db_prepare_input($_GET['month']);
  $sel_year = zen_db_prepare_input($_GET['year']);
}

// get list of orders_status names for dropdown selection
$orders_statuses = array();
$orders_status_array = array();
$orders_status = $db->Execute("SELECT orders_status_id, orders_status_name
                                 FROM " . TABLE_ORDERS_STATUS . "
                                 WHERE language_id = '" . (int)$_SESSION['languages_id'] . "'");
while (!$orders_status->EOF) {
  $orders_statuses[] = array('id' => $orders_status->fields['orders_status_id'],
                             'text' => $orders_status->fields['orders_status_name'] . ' [' . $orders_status->fields['orders_status_id'] . ']');
  $orders_status_array[$orders_status->fields['orders_status_id']] = $orders_status->fields['orders_status_name'];
  $orders_status->MoveNext();
}
// name of status selection
$orders_status_text = TEXT_ALL_ORDERS;
if ($status != '') {
  $orders_status_query = $db->Execute("SELECT orders_status_name FROM " . TABLE_ORDERS_STATUS . " WHERE language_id = '" . $languages_id . "' AND orders_status_id =" . zen_db_prepare_input($status));
  while (!$orders_status_query->EOF) {
    $orders_status_text = $orders_status_query->fields['orders_status_name'];
    $orders_status_query->MoveNext();
  }
}

if (!$print) { 
?>
                <td align="right">
                  <table border="0" width="100%" cellspacing="0" cellpadding="0">
                    <tr>
                      <td class="smallText" align="right">
<?php 
  echo zen_draw_form('status', FILENAME_STATS_MONTHLY_SALES, '', 'get');
  echo HEADING_TITLE_STATUS . ': ' . zen_draw_pull_down_menu('status', array_merge(array(array('id' => '', 'text' => TEXT_ALL_ORDERS)), $orders_statuses), '', 'onChange="this.form.submit();"') . zen_draw_hidden_field('selected_box', 'reports'); 

  if ($sel_month != 0) {
    echo zen_draw_hidden_field('month', $sel_month) . zen_draw_hidden_field('year', $sel_year);
  }
  if ($invert != '') {
    echo zen_draw_hidden_field('invert', 'yes');
  }
?>
                      </form></td>
                    </tr>
                  </table>
                </td>
<?php
} else { 
?>
                <td>&nbsp;</td>
              </tr>
              <tr>
                <td>
                  <table>
                    <tr>
                      <td class="smallText"><?php echo HEADING_TITLE_REPORTED . ": "; ?></td>
                      <td width="8"></td>
                      <td class="smallText" align="left"><?php echo date(ltrim(TEXT_REPORT_DATE_FORMAT)); ?></td>
                    </tr>
                    <tr>
                      <td class="smallText" align="left"><?php echo HEADING_TITLE_STATUS . ": ";  ?></td>
                      <td width="8"></td>
                      <td class="smallText" align="left"><?php echo $orders_status_text;?></td>
                    </tr>
                  </table>
                </td>
                <td>&nbsp;</td>
              </tr>
<?php
}   
?>
            </table>
          </td>
        </tr>
<?php 
if(!$print) { 
?>
<!--
row for buttons to print, save, and help
-->
        <tr>
          <td align="right">
            <table align=right cellspacing="10">
              <tr>
<?php  
// back button if monthly detail
  if ($sel_month != 0) {
?>
                <td align="left" class="smallText"><a href="<?php echo zen_href_link(FILENAME_STATS_MONTHLY_SALES, 'selected_box=reports' . (($status == '') ? '' : '&status=' . $status) . $invert . '" title="' . TEXT_BUTTON_REPORT_BACK_DESC . '"'); ?>><?php echo TEXT_BUTTON_REPORT_BACK; ?></a></td>
<?php
  }
  ?>
                <td class="smallText"><a href="<?php echo zen_href_link(FILENAME_STATS_MONTHLY_SALES, zen_get_all_get_params() . '&print=yes'); ?>" target="print" title="<?php echo TEXT_BUTTON_REPORT_PRINT_DESC . '">' . TEXT_BUTTON_REPORT_PRINT; ?></a></td>
                <td class="smallText"><a href="<?php echo zen_href_link(FILENAME_STATS_MONTHLY_SALES, zen_get_all_get_params(array('invert')) . (($invert == '') ? '&invert=yes' : '')); ?>" title="<?php echo TEXT_BUTTON_REPORT_INVERT_DESC . '">' . TEXT_BUTTON_REPORT_INVERT; ?></a></td>
                <td class="smallText"><a href="#" onClick="window.open('<?php echo zen_href_link(FILENAME_STATS_MONTHLY_SALES, 'help=yes'); ?>','help',config='height=400,width=600,scrollbars=1,resizable=1')" title="<?php echo TEXT_BUTTON_REPORT_HELP_DESC . "\">" . TEXT_BUTTON_REPORT_HELP; ?></a></td>
              </tr>
            </table>
          </td>
        </tr>
<?php  
}  
//
// determine if loworder fee is enabled in configuration, include/omit the column
$loworder = (defined ('MODULE_ORDER_TOTAL_LOWORDERFEE_LOW_ORDER_FEE') && MODULE_ORDER_TOTAL_LOWORDERFEE_LOW_ORDER_FEE == 'true') ? true : false;
$gv = (defined ('MODULE_ORDER_TOTAL_GV_STATUS') && MODULE_ORDER_TOTAL_GV_STATUS == 'true') ? true : false;
$coupon = (defined ('MODULE_ORDER_TOTAL_COUPON_STATUS') && MODULE_ORDER_TOTAL_COUPON_STATUS == 'true') ? true : false;

//
// if there are extended class values in orders_table
// create extra column so totals are comprehensively correct
$class_val_subtotal = "'ot_subtotal'";
$class_val_tax = "'ot_tax'";
$class_val_shiphndl = "'ot_shipping'";
$class_val_loworder = "'ot_loworderfee'";
$class_val_total = "'ot_total'";
$class_val_gv = "'ot_gv'";
$class_val_coupon = "'ot_coupon'";
$extra_class_query_raw = "SELECT value FROM " . TABLE_ORDERS_TOTAL . " 
                            WHERE class != $class_val_subtotal 
                              AND class != $class_val_tax
                              AND class != $class_val_shiphndl
                              AND class != $class_val_loworder
                              AND class != $class_val_total
                              AND class != $class_val_gv
                              AND class != $class_val_coupon";
$extra_class_query = $db->Execute($extra_class_query_raw);
$extra_class = ($extra_class_query->EOF) ? false : true;

sms_debug('CONTROL FLAGS: combine_shipping(' . (($combine_shipping_totals) ? 'enabled' : 'disabled') . '), low-order fee(' . (($loworder) ? 'enabled' : 'disabled') . '), gift vouchers(' . (($gv) ? 'enabled' : 'disabled') . '), coupons(' . (($coupons) ? 'enabled' : 'disabled') . '), extra-class(' . (($extra_class) ? 'enabled' : 'disabled') . ')');

// start accumulator for the report content mirrored in CSV
$csv_accum = '';
?>
        <tr>
          <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
            <tr>
              <td valign="top">
                <table border="0" width='100%' cellspacing="0" cellpadding="2">
                  <tr class="dataTableHeadingRow">
                    <td class="dataTableHeadingContent" width="45" align="left" valign="bottom"><?php mirror_out(TABLE_HEADING_MONTH); ?></td>
                    <td class="dataTableHeadingContent" width="35" align="left" valign="bottom"><?php mirror_out(($sel_month == 0) ? TABLE_HEADING_YEAR : TABLE_HEADING_DAY); ?></td>
                    <td class="dataTableHeadingContent" width="70" align="right" valign="bottom"><?php mirror_out(TABLE_HEADING_INCOME); ?></td>
                    <td class="dataTableHeadingContent" width="70" align="right" valign="bottom"><?php mirror_out(TABLE_HEADING_SALES); ?></td>
                    <td class="dataTableHeadingContent" width="70" align="right" valign="bottom"><?php mirror_out(($combine_shipping_totals) ? TABLE_HEADING_NONTAXED_TOTAL : TABLE_HEADING_NONTAXED); ?></td>
                    <td class="dataTableHeadingContent" width="70" align="right" valign="bottom"><?php mirror_out(($combine_shipping_totals) ? TABLE_HEADING_TAXED_TOTAL : TABLE_HEADING_TAXED); ?></td>
                    <td class="dataTableHeadingContent" width="70" align="right" valign="bottom"><?php mirror_out(TABLE_HEADING_TAX_COLL); ?></td>
<?php
if (!$combine_shipping_totals) {
?>
                    <td class="dataTableHeadingContent" width="70" align="right" valign="bottom"><?php mirror_out(TABLE_HEADING_SHIPHNDL); ?></td>
                    <td class="dataTableHeadingContent" width="70" align="right" valign="bottom"><?php mirror_out(TABLE_HEADING_SHIP_TAX); ?></td>
<?php 
}
if ($loworder) { 
?>
                    <td class="dataTableHeadingContent" width="70" align="right" valign="bottom"><?php mirror_out(TABLE_HEADING_LOWORDER); ?></td>
<?php 
}
if ($gv) { 
?>
                    <td class="dataTableHeadingContent" width="70" align="right" valign="bottom"><?php mirror_out(TABLE_HEADING_VOUCHER); ?></td>
<?php 
}
if ($coupon) { 
?>
                    <td class="dataTableHeadingContent" width="70" align="right" valign="bottom"><?php mirror_out(TABLE_HEADING_COUPON); ?></td>
<?php 
}

if ($extra_class) { 
?>
                    <td class="dataTableHeadingContent" width="70" align="right" valign="bottom"><?php mirror_out(TABLE_HEADING_OTHER); ?></td>
<?php 
}
?>
                  </tr>
<?php 
// clear footer totals
$footer_gross = 0;
$footer_sales = 0;
$footer_sales_nontaxed = 0;
$footer_sales_taxed = 0;
$footer_tax_coll = 0;
$footer_shiphndl = 0;
$footer_shipping_tax = 0;
$footer_loworder = 0;
$footer_gv = 0;
$footer_coupon = 0;
$footer_other = 0;
// new line for CSV
$csv_accum .= "\n";
// order totals, the driving force 
$sales_query_raw = "SELECT sum(ot.value) gross_sales, monthname(o.date_purchased) row_month, year(o.date_purchased) row_year, month(o.date_purchased) i_month, dayofmonth(o.date_purchased) row_day 
                      FROM " . TABLE_ORDERS . " o LEFT JOIN " . TABLE_ORDERS_TOTAL . " ot 
                      ON (o.orders_id = ot.orders_id) WHERE ";
if ($status != '') {
  $sales_query_raw .= "o.orders_status =" . zen_db_prepare_input($status) . " AND ";
}
$sales_query_raw .= "ot.class = " . $class_val_total . SALES_QUERY_ADDITIONAL;
if ($sel_month != 0) {
  $sales_query_raw .= " AND month(o.date_purchased) = $sel_month";
}
if ($sel_year != 0) {
  $sales_query_raw .= " AND year(o.date_purchased) = $sel_year";
}
$sales_query_raw .= " GROUP BY year(o.date_purchased), month(o.date_purchased)";
if ($sel_month != 0) {
  $sales_query_raw .= ", dayofmonth(o.date_purchased)";
}
$sales_query_raw .=  " ORDER BY o.date_purchased " . (($invert == '') ? 'DESC' : 'ASC');

$sales_query = $db->Execute($sales_query_raw);
$num_rows = $sales_query->RecordCount();
if ($num_rows == 0) {
  echo '<tr><td class="smalltext">' . TEXT_NOTHING_FOUND . '</td></tr>';
}

//
// loop here for each row reported
$rows = 0;
while (!$sales_query->EOF) {
  $rows++;
  if ($rows > 1 && $sales_query->fields['row_year'] != $last_row_year) {  // emit annual footer
?>
                  <tr class="dataTableHeadingRow">
                    <td class="dataTableHeadingContent" align="left">
<?php 
    if ($sales_query->fields['row_year'] == date("Y")) {
      mirror_out(TABLE_FOOTER_YTD);
    } else {
      if ($sel_month == 0) {
        mirror_out(TABLE_FOOTER_YEAR);
      } else {
        mirror_out(strtoupper(substr($sales_query->fields['row_month'],0,3)));
      }
    }
?>
                    </td>
                    <td class="dataTableHeadingContent" align="left"><?php mirror_out($last_row_year); ?></td>
                    <td class="dataTableHeadingContent" width="70" align="right"><?php mirror_out(number_format($footer_gross, $decimal_places)); ?></td>
                    <td class="dataTableHeadingContent" width="70" align="right"><?php mirror_out(number_format($footer_sales, $decimal_places)); ?></td>
                    <td class="dataTableHeadingContent" width="70" align="right"><?php mirror_out(number_format($footer_sales_nontaxed, $decimal_places)); ?></td>
                    <td class="dataTableHeadingContent" width="70" align="right"><?php mirror_out(number_format($footer_sales_taxed, $decimal_places)); ?></td>
                    <td class="dataTableHeadingContent" width="70" align="right"><?php mirror_out(number_format($footer_tax_coll, $decimal_places)); ?></td>
<?php
    if (!$combine_shipping_totals) {
?>
                    <td class="dataTableHeadingContent" width="70" align="right"><?php mirror_out(number_format($footer_shiphndl, $decimal_places)); ?></td>
                    <td class="dataTableHeadingContent" width="70" align="right"><?php mirror_out(number_format(($footer_shipping_tax <= 0) ? 0 : $footer_shipping_tax, $decimal_places)); ?></td>
<?php 
    }
    if ($loworder) { 
?>
                    <td class="dataTableHeadingContent" width="70" align="right"><?php mirror_out(number_format($footer_loworder, $decimal_places)); ?></td>
<?php 
    }
    if ($gv) { 
?>
                    <td class="dataTableHeadingContent" width="70" align="right"><?php mirror_out(number_format($footer_gv, $decimal_places)); ?></td>
<?php 
    }
    if ($coupon) { 
?>
                    <td class="dataTableHeadingContent" width="70" align="right"><?php mirror_out(number_format($footer_coupon, $decimal_places)); ?></td>
<?php 
    }
    if ($extra_class) { 
?>
                    <td class="dataTableHeadingContent" width="70" align="right"><?php mirror_out(number_format($footer_other, $decimal_places)); ?></td>
<?php 
    }
    // clear footer totals
    $footer_gross = 0;
    $footer_sales = 0;
    $footer_sales_nontaxed = 0;
    $footer_sales_taxed = 0;
    $footer_tax_coll = 0;
    $footer_shiphndl = 0;
    $footer_shipping_tax = 0;
    $footer_loworder = 0;
    $footer_gv = 0;
    $footer_coupon = 0;
    $footer_other = 0;
    // new line for CSV
    $csv_accum .= "\n";
?>
                  </tr>
<?php 
  }
//
// determine net sales for row

// Retrieve totals for products that are zero VAT rated
  $zero_rated_sales_query_raw = "SELECT sum( op.final_price * op.products_quantity ) net_sales
                                    FROM " . TABLE_ORDERS . " o 
                                      INNER JOIN " . TABLE_ORDERS_PRODUCTS . " op ON ( o.orders_id = op.orders_id )
                                    WHERE op.products_tax = 0";
  $zero_rated_sales_query_raw = add_status_and_daterange($zero_rated_sales_query_raw, $status, $sales_query, $sel_month);
  $zero_rated_sales_query = $db->Execute($zero_rated_sales_query_raw);
  $zero_rated_sales_this_row = ($zero_rated_sales_query->EOF) ? 0 : $zero_rated_sales_query->fields;
  
  $untaxed_shipping_query_raw = "SELECT DISTINCT op.orders_id, ot.value net_shipping
                                    FROM " . TABLE_ORDERS_TOTAL . " ot, " . TABLE_ORDERS_PRODUCTS . " op, " . TABLE_ORDERS . " o
                                    WHERE op.products_tax = 0
                                      AND  o.orders_id = op.orders_id
                                      AND  o.orders_id = ot.orders_id
                                      AND ot.class = $class_val_shiphndl";
  $untaxed_shipping_query_raw = add_status_and_daterange($untaxed_shipping_query_raw, $status, $sales_query, $sel_month);
  $untaxed_shipping_query = $db->Execute($untaxed_shipping_query_raw);
  $zero_rated_sales_this_row['net_shipping'] = 0;
  while (!$untaxed_shipping_query->EOF) {
    $zero_rated_sales_this_row['net_shipping'] += $untaxed_shipping_query->fields['net_shipping'];
    $untaxed_shipping_query->MoveNext();
  }

// Retrieve totals for products that are NOT zero VAT rated
  $net_sales_query_raw = "SELECT sum(op.final_price * op.products_quantity) net_sales, sum(op.final_price * op.products_quantity * (1 + (op.products_tax / 100.0))) gross_sales, sum((op.final_price * op.products_quantity * (1 + (op.products_tax / 100.0))) - (op.final_price * op.products_quantity)) tax, sum( DISTINCT ot.value ) net_shipping 
                            FROM " . TABLE_ORDERS . " o 
                              INNER JOIN " . TABLE_ORDERS_PRODUCTS . " op ON (o.orders_id = op.orders_id) 
                              INNER JOIN " . TABLE_ORDERS_TOTAL . " ot ON ( o.orders_id = ot.orders_id AND op.orders_id = ot.orders_id AND ot.class=$class_val_shiphndl )
                            WHERE op.products_tax != 0";
  $net_sales_query_raw = add_status_and_daterange($net_sales_query_raw, $status, $sales_query, $sel_month);
  $net_sales_query = $db->Execute($net_sales_query_raw);
  $net_sales_this_row = ($net_sales_query->EOF) ? 0 : $net_sales_query->fields;
  
  $taxed_shipping_query_raw = "SELECT DISTINCT op.orders_id, ot.value AS net_shipping
                                    FROM " . TABLE_ORDERS_TOTAL . " ot, " . TABLE_ORDERS_PRODUCTS . " op, " . TABLE_ORDERS . " o
                                    WHERE op.products_tax != 0
                                      AND  o.orders_id = op.orders_id
                                      AND  o.orders_id = ot.orders_id
                                      AND ot.class = $class_val_shiphndl";
  $taxed_shipping_query_raw = add_status_and_daterange($taxed_shipping_query_raw, $status, $sales_query, $sel_month);
  $taxed_shipping_query = $db->Execute($taxed_shipping_query_raw);
  $net_sales_this_row['net_shipping'] = 0;
  while (!$taxed_shipping_query->EOF) {
    $net_sales_this_row['net_shipping'] += $taxed_shipping_query->fields['net_shipping'];
    $taxed_shipping_query->MoveNext();
  }
  
// Total tax. This is needed so we can calculate any tax that has been added to the postage
  $tax_coll_query_raw = "SELECT sum(ot.value) tax_coll FROM " . TABLE_ORDERS . " o INNER JOIN " . TABLE_ORDERS_TOTAL . " ot ON (o.orders_id = ot.orders_id)";
  $tax_coll_query_raw = add_status_and_daterange($tax_coll_query_raw, $status, $sales_query, $sel_month, $class_val_tax);
  $tax_coll_query = $db->Execute($tax_coll_query_raw);
  $tax_this_row = ($tax_coll_query->EOF) ? 0 : $tax_coll_query->fields;

// shipping AND handling charges for row
  $shiphndl_query_raw = "SELECT sum(ot.value) shiphndl from " . TABLE_ORDERS . " o INNER JOIN " . TABLE_ORDERS_TOTAL . " ot ON (o.orders_id = ot.orders_id)";
  $shiphndl_query_raw = add_status_and_daterange($shiphndl_query_raw, $status, $sales_query, $sel_month, $class_val_shiphndl);
  $shiphndl_query = $db->Execute($shiphndl_query_raw);
  $shiphndl_this_row = ($shiphndl_query->EOF) ? 0 : $shiphndl_query->fields;

// low order fees for row
  $loworder_this_row = 0;
  if ($loworder) {
    $loworder_query_raw = "SELECT sum(ot.value) loworder from " . TABLE_ORDERS . " o INNER JOIN " . TABLE_ORDERS_TOTAL . " ot ON (o.orders_id = ot.orders_id)";
    $loworder_query_raw = add_status_and_daterange($loworder_query_raw, $status, $sales_query, $sel_month, $class_val_loworder);
    $loworder_query = $db->Execute($loworder_query_raw);
    if (!$loworder_query->EOF) {
      $loworder_this_row = $loworder_query->fields;
    }
  }

// Gift Vouchers for row
  $gv_this_row = 0;
  if ($gv) {
    $gv_query_raw = "SELECT sum(ot.value) gv from " . TABLE_ORDERS . " o INNER JOIN " . TABLE_ORDERS_TOTAL . " ot ON (o.orders_id = ot.orders_id)";
    $gv_query_raw = add_status_and_daterange($gv_query_raw, $status, $sales_query, $sel_month, $class_val_gv);
    $gv_query = $db->Execute($gv_query_raw);
    if (!$gv_query->EOF) {
      $gv_this_row = $gv_query->fields;
    }
  }

// coupons for row
  $coupon_this_row = 0;
  if ($coupon) {
    $coupon_query_raw = "SELECT sum(ot.value) coupon from " . TABLE_ORDERS . " o INNER JOIN " . TABLE_ORDERS_TOTAL . " ot ON (o.orders_id = ot.orders_id)";
    $coupon_query_raw = add_status_and_daterange($coupon_query_raw, $status, $sales_query, $sel_month, $class_val_coupon);
    $coupon_query = $db->Execute($coupon_query_raw);
    if (!$coupon_query->EOF) {
      $coupon_this_row = $coupon_query->fields;
    }
  }
  
// additional column if extra class value in orders_total table
  $other_this_row = 0;
  if ($extra_class) { 
    $other_query_raw = "SELECT sum(ot.value) other from " . TABLE_ORDERS . " o INNER JOIN " . TABLE_ORDERS_TOTAL . " ot ON (o.orders_id = ot.orders_id) 
                          WHERE ot.class != $class_val_subtotal 
                          AND class != $class_val_tax 
                          AND class != $class_val_shiphndl 
                          AND class != $class_val_loworder 
                          AND class != $class_val_total
                          AND class != $class_val_gv
                          AND class != $class_val_coupon";
    $other_query_raw = add_status_and_daterange($other_query_raw, $status, $sales_query, $sel_month);
    $other_query = $db->Execute($other_query_raw);
    if (!$other_query->EOF) {
      $other_this_row = $other_query->fields;
    }
  }

// Correct any rounding errors
  $sales_query->fields['gross_sales'] = zen_round($sales_query->fields['gross_sales'], $decimal_places);
  $net_sales_this_row['net_sales'] = zen_round($net_sales_this_row['net_sales'], $decimal_places);
  $net_sales_this_row['tax'] = zen_round($net_sales_this_row['tax'], $decimal_places);
  $net_sales_this_row['net_shipping'] = zen_round($net_sales_this_row['net_shipping'], $decimal_places);
  $zero_rated_sales_this_row['net_sales'] = zen_round($zero_rated_sales_this_row['net_sales'], $decimal_places);
  $zero_rated_sales_this_row['net_shipping'] = zen_round($zero_rated_sales_this_row['net_shipping'], $decimal_places);
  $tax_this_row['tax_coll'] = zen_round($tax_this_row['tax_coll'], $decimal_places);

// accumulate row results in footer
  $footer_gross += $sales_query->fields['gross_sales']; // Gross Income
  $footer_sales += $net_sales_this_row['net_sales'] + $zero_rated_sales_this_row['net_sales']; // Product Sales
  $footer_sales_nontaxed += $zero_rated_sales_this_row['net_sales'] + (($combine_shipping_totals) ? $zero_rated_sales_this_row['net_shipping'] : 0); // Nontaxed Sales
  $footer_sales_taxed += $net_sales_this_row['net_sales'] + (($combine_shipping_totals) ? $net_sales_this_row['net_shipping'] : 0); // Taxed Sales
  $footer_tax_coll += (($combine_shipping_totals) ? $tax_this_row['tax_coll'] : $net_sales_this_row['tax']); // Taxes Collected
  $footer_shiphndl += $shiphndl_this_row['shiphndl']; // Shipping & handling
  $footer_shipping_tax += ($tax_this_row['tax_coll'] - $net_sales_this_row['tax']); // Shipping Tax
  
  if ($loworder) {
    $loworder_this_row['loworder'] = zen_round($loworder_this_row['loworder'], $decimal_places);
    $footer_loworder += $loworder_this_row['loworder'];
  }
  if ($gv) {
    $gv_this_row['gv'] = zen_round($gv_this_row['gv'], $decimal_places);
    $footer_gv += $gv_this_row['gv'];
  }
  if ($coupon) {
    $coupon_this_row['coupon'] = zen_round($coupon_this_row['coupon'], $decimal_places);
    $footer_coupon += $coupon_this_row['coupon'];
  }
  if ($extra_class) {
    $other_this_row['other'] = zen_round($other_this_row['other'], $decimal_places);
    $footer_other += $other_this_row['other'];
  }
?>
                  <tr class="dataTableRow">
                    <td class="dataTableContent" align="left">
<?php  // live link to report monthly detail
  $need_anchor_closed = false;
  if ($sel_month == 0  && !$print) {
    $need_anchor_closed = true;
    echo '<a href="' . zen_href_link(FILENAME_STATS_MONTHLY_SALES, zen_get_all_get_params(array('month', 'year')) . "month=" . $sales_query->fields['i_month'] . "&year=" . $sales_query->fields['row_year']) . '"  title="' . TEXT_BUTTON_REPORT_GET_DETAIL . '">';
  }
  mirror_out(substr($sales_query->fields['row_month'],0,3)); 
  if ($need_anchor_closed) {
    echo '</a>';
  }
?>
                    </td>
                    <td class="dataTableContent" align="left">
<?php 
  mirror_out(($sel_month == 0) ? $sales_query->fields['row_year'] : $sales_query->fields['row_day']);
  $last_row_year = $sales_query->fields['row_year']; // save this row's year to check for annual footer
?>
                    </td>
                    <td class="dataTableContent" width="70" align="right"><?php mirror_out(number_format($sales_query->fields['gross_sales'], $decimal_places)); ?></td>
                    <td class="dataTableContent" width="70" align="right"><?php mirror_out(number_format($net_sales_this_row['net_sales'] + $zero_rated_sales_this_row['net_sales'], $decimal_places)); ?></td>
                    <td class="dataTableContent" width="70" align="right"><?php mirror_out(number_format($zero_rated_sales_this_row['net_sales'] + (($combine_shipping_totals) ? $zero_rated_sales_this_row['net_shipping'] : 0), $decimal_places)); ?></td>
                    <td class="dataTableContent" width="70" align="right"><?php mirror_out(number_format($net_sales_this_row['net_sales'] + (($combine_shipping_totals) ? $net_sales_this_row['net_shipping'] : 0), $decimal_places)); ?></td>
                    <td class="dataTableContent" width="70" align="right">
<?php 
  // make this a link to the detail popup if nonzero
  $need_closing_anchor = false;
  if (!$print && ((!$combine_shipping_totals && $net_sales_this_row['tax'] > 0) || ($combine_shipping_totals && $tax_this_row['tax_coll'] > 0))) {
    $need_closing_anchor = true;
    echo "<a href=\"#\" onClick=\"window.open('" . zen_href_link(FILENAME_STATS_MONTHLY_SALES, 'show=ot_tax&year=' . $sales_query->fields['row_year'] . '&month=' . $sales_query->fields['i_month']);
    if ($sel_month != 0) echo "&day=" . $sales_query->fields['row_day'];
    if ($status != '') echo "&status=" . $status;
    echo "','detail',config='height=200,width=600,scrollbars=1,resizable=1')\" title=\"" . TEXT_SHOW_DETAIL . "\">";
  }
  mirror_out(number_format((($combine_shipping_totals) ? $tax_this_row['tax_coll'] : $net_sales_this_row['tax']), $decimal_places)); 
  if ($need_closing_anchor) {
    echo "</a>";
  }
?>
                    </td>
<?php
  if (!$combine_shipping_totals) {
?>
                    <td class="dataTableContent" width="70" align="right"><?php mirror_out(number_format($shiphndl_this_row['shiphndl'], $decimal_places)); ?></td>
                    <td class="dataTableContent" width="70" align="right"><?php $sh_tax = $tax_this_row['tax_coll'] - $net_sales_this_row['tax']; mirror_out(number_format(($sh_tax <= 0) ? 0 : $sh_tax, $decimal_places)); ?></td>
<?php
  } 
  if ($loworder) { 
?>
                    <td class="dataTableContent" width="70" align="right"><?php mirror_out(number_format($loworder_this_row['loworder'], $decimal_places)); ?></td>
<?php 
  }
  if ($gv) { 
?>
                    <td class="dataTableContent" width="70" align="right"><?php mirror_out(number_format($gv_this_row['gv'], $decimal_places)); ?></td>
<?php 
  }
  if ($coupon) { 
?>
                    <td class="dataTableContent" width="70" align="right"><?php mirror_out(number_format($coupon_this_row['coupon'], $decimal_places)); ?></td>
<?php 
  }  
  if ($extra_class) { 
?>
                    <td class="dataTableContent" width="70" align="right"><?php mirror_out(number_format($other_this_row['other'], $decimal_places)); ?></td>
<?php 
  }
?>
                  </tr>
<?php 
  // new line for CSV
  $csv_accum .= "\n";

    // output footer below ending row
  if ($rows == $num_rows){
?>
                  <tr class="dataTableHeadingRow">
                    <td class="dataTableHeadingContent" align="left">
<?php 
    if ($sel_month != 0) {
      mirror_out(strtoupper(substr($sales_query->fields['row_month'],0,3)));
    } else {
      if ($sales_query->fields['row_year'] == date("Y")) {
        mirror_out(TABLE_FOOTER_YTD); 
      } else {
        mirror_out(TABLE_FOOTER_YEAR);
      }
    }
  ?>
                    </td>
                    <td class="dataTableHeadingContent" align="left"><?php mirror_out($sales_query->fields['row_year']); ?></td>
                    <td class="dataTableHeadingContent" width="70" align="right"><?php mirror_out(number_format($footer_gross, $decimal_places)); ?></td>
                    <td class="dataTableHeadingContent" width="70" align="right"><?php mirror_out(number_format($footer_sales, $decimal_places)); ?></td>
                    <td class="dataTableHeadingContent" width="70" align="right"><?php mirror_out(number_format($footer_sales_nontaxed, $decimal_places)); ?></td>
                    <td class="dataTableHeadingContent" width="70" align="right"><?php mirror_out(number_format($footer_sales_taxed, $decimal_places)); ?></td>
                    <td class="dataTableHeadingContent" width="70" align="right"><?php mirror_out(number_format($footer_tax_coll, $decimal_places)); ?></td>
<?php
    if (!$combine_shipping_totals) {
?>
                    <td class="dataTableHeadingContent" width="70" align="right"><?php mirror_out(number_format($footer_shiphndl, $decimal_places)); ?></td>
                    <td class="dataTableHeadingContent" width="70" align="right"><?php mirror_out(number_format(($footer_shipping_tax <= 0) ? 0 : $footer_shipping_tax, $decimal_places)); ?></td>
<?php
    }
    if ($loworder) { 
?>
                    <td class="dataTableHeadingContent" width="70" align="right"><?php mirror_out(number_format($footer_loworder, $decimal_places)); ?></td>
<?php 
    }
    if ($gv) { 
?>
                    <td class="dataTableHeadingContent" width="70" align="right"><?php mirror_out(number_format($footer_gv, $decimal_places)); ?></td>
<?php 
    }
    if ($coupon) { 
?>
                    <td class="dataTableHeadingContent" width="70" align="right"><?php mirror_out(number_format($footer_coupon, $decimal_places)); ?></td>
<?php 
    }
    if ($extra_class) { 
?>
                    <td class="dataTableHeadingContent" width="70" align="right"><?php mirror_out(number_format($footer_other, $decimal_places)); ?></td>
<?php 
    }

    // clear footer totals
    $footer_gross = 0;
    $footer_sales = 0;
    $footer_sales_nontaxed = 0;
    $footer_sales_taxed = 0;
    $footer_tax_coll = 0;
    $footer_shiphndl = 0;
    $footer_shipping_tax = 0;
    $footer_loworder = 0;
    $footer_gv = 0;
    $footer_coupon = 0;
    $footer_other = 0;
    // new line for CSV
    $csv_accum .= "\n";
?>
                  </tr>
<?php 
  } 
  $sales_query->MoveNext();
}  

// done with report body
// button for Save CSV
if ($num_rows > 0 && !$print) {
  $fn_suffix = ($sel_month == 0) ? '' : ($sel_year . (($sel_month < 10) ? '0' : '') . $sel_month) . '_';
  $fn_suffix .= ((strpos($orders_status_text, ' ')) ? substr($orders_status_text, 0, strpos($orders_status_text,' ')) : $orders_status_text) . "_" . date("YmdHi");
?>
                  <tr>
                    <td class="smallText" colspan="4"><?php echo zen_draw_form('csv_form', FILENAME_STATS_MONTHLY_SALES, '', 'post') . zen_draw_hidden_field('csv', $csv_accum); ?><input type="hidden" name="saveas" value="sales_report_<?php echo $fn_suffix; ?>"><input type="submit" value="<?php echo TEXT_BUTTON_REPORT_SAVE ;?>"></form></td>
                  </tr>
<?php 
}
// end button for Save CSV 
?>
                </table>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </td>
<!-- body_text_eof //-->
  </tr>
</table>

<?php
// suppress footer for printer-friendly version
if(!$print) {
  require(DIR_WS_INCLUDES . 'footer.php'); 
}
?>
</body>
</html>
<?php 
require(DIR_WS_INCLUDES . 'application_bottom.php'); 
?>
