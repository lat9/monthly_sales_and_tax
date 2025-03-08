<?php
/**
 * Monthly Sales and Tax Summary mod for Zen Cart
 * Version 2.1.0
 * @copyright Portions Copyright 2004-2024 Zen Cart Team
 * @author Vinos de Frutas Tropicales (lat9)
****************************************************************************
    Copyright (C) 2024  Vinos de Frutas Tropicales (lat9)

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, version 2 of the License.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************/
// -----
// Part of the "Monthly Sales and Tax" plugin (v2.0.0+) for Zen Cart 157+.
// Copyright (C) 2021-2022, Vinos de Frutas Tropicales.
//
class MonthlySalesAndTax extends base
{
    public
        $sales,         //-An array of sales' information
        $status;        //-The orders-status value used to create the above array (0 if all statuses)

    protected
        $debug,
        $debug_logfile,
        $using_loworder,
        $using_gv,
        $using_coupon,
        $base_classes,
        $decimal_places,
        $selectedYear,
        $selectedMonth,
        $reportModeMonthly,
        $sortDir,
        $additional_totals,
        $baseClassesList;

    // -----
    // Class constructor, providing the settings to be used to create the report:
    //
    // $status ........... The orders_status_id.  If '0', all statuses; otherwise, just that selected status_id.
    // $sort_dir ......... Identifies the sort 'direction' for the report, either 'ASC' or 'DESC'.
    // $selected_year .... The specific year for which the report is run; set to '0' for all years.
    // $selected_month ... The specific month (with year) for which the report is run; set to '0' for all dates.
    //
    public function __construct($status, $sort_dir, $selected_year, $selected_month)
    {
        global $db;

        // -----
        // Determine whether/not debug is enabled, setting the current debug-filename.
        //
        $this->debug = defined('STATS_MONTHLY_SALES_DEBUG') && STATS_MONTHLY_SALES_DEBUG === 'on';
        $this->debug_logfile = DIR_FS_LOGS . '/myDEBUG-sms-' . date('Ymd-His') . '.log';

        // -----
        // Set a couple of processing flags for optional total-related values.  These are used by protected
        // class methods and returned for the main report's use in determining whether/not columns should
        // be included.
        ///
        $this->using_loworder = defined('MODULE_ORDER_TOTAL_LOWORDERFEE_LOW_ORDER_FEE') && MODULE_ORDER_TOTAL_LOWORDERFEE_LOW_ORDER_FEE === 'true';
        $this->using_gv = defined('MODULE_ORDER_TOTAL_GV_STATUS') && MODULE_ORDER_TOTAL_GV_STATUS === 'true';
        $this->using_coupon = defined('MODULE_ORDER_TOTAL_COUPON_STATUS') && MODULE_ORDER_TOTAL_COUPON_STATUS === 'true';

        // -----
        // Determine whether/not an additional column is needed for the main report to collect totals for
        // classes **other than** those in the base.
        //
        $this->base_classes = [
            'ot_total' => 'gross_sales',
            'ot_tax' => 'tax',
            'ot_shipping' => 'shipping',
            'ot_subtotal' => '',
        ];
        if ($this->using_loworder) {
            $this->base_classes['ot_loworderfee'] = 'loworder';
        }
        if ($this->using_gv) {
            $this->base_classes['ot_gv'] = 'gv';
        }
        if ($this->using_coupon) {
            $this->base_classes['ot_coupon'] = 'coupon';
        }
        $this->baseClassesList = "'" . implode("', '", array_keys($this->base_classes)) . "'";
        $extra_class_query = $db->Execute(
            "SELECT value
               FROM " . TABLE_ORDERS_TOTAL . "
              WHERE class NOT IN (" . $this->baseClassesList . ")
              LIMIT 1"
        );
        $this->additional_totals = !$extra_class_query->EOF;

        // -----
        // Determine the number of decimal points to include in the sales output, based on
        // the site's **default** currency.
        //
        require DIR_WS_CLASSES . 'currencies.php';
        $currencies = new currencies();
        $this->decimal_places = $currencies->get_decimal_places(DEFAULT_CURRENCY);

        // -----
        // Now that the base order_totals to be gathered has been determined, see which form
        // of report (monthly for all years vs. daily for a specified month) is to be
        // created and generate the 'base' sales structure for the to-be-returned report's
        // information array.
        //
        $this->status = (string)$status;

        $this->selectedYear = (string)$selected_year;
        $this->selectedMonth = (string)$selected_month;

        $this->reportModeMonthly = ($this->selectedYear === '0' && $this->selectedMonth === '0');

        $this->sortDir = ($sort_dir === 'DESC') ? 'DESC' : 'ASC';

        // -----
        // Log the values set; they define how the report will run.
        //
        $this->debug('constructor, on exit: ' . json_encode($this));
    }

    // -----
    // A collection of public methods (used by the main report) to return some of the report's processing flags.
    //
    public function usingLowOrder()
    {
        return $this->using_loworder;
    }
    public function usingGiftVouchers()
    {
        return $this->using_gv;
    }
    public function usingCoupons()
    {
        return $this->using_coupon;
    }
    public function usingAdditionalTotals()
    {
        return $this->additional_totals;
    }
    public function isMonthlyReport()
    {
        return $this->reportModeMonthly;
    }

    public function formatValue($value)
    {
        return number_format($value, $this->decimal_places);
    }

    // -----
    // Returns the translated name-of-month, based on a month value supplied in the
    // range 1 (January) to 12 (December).  Constants are defined in the admin's main
    // language file.
    //
    public function getMonthName($month)
    {
        switch ($month) {
            case 1:
                $monthname = _JANUARY;
                break;
            case 2:
                $monthname = _FEBRUARY;
                break;
            case 3:
                $monthname = _MARCH;
                break;
            case 4:
                $monthname = _APRIL;
                break;
            case 5:
                $monthname = _MAY;
                break;
            case 6:
                $monthname = _JUNE;
                break;
            case 7:
                $monthname = _JULY;
                break;
            case 8:
                $monthname = _AUGUST;
                break;
            case 9:
                $monthname = _SEPTEMBER;
                break;
            case 10:
                $monthname = _OCTOBER;
                break;
            case 11:
                $monthname = _NOVEMBER;
                break;
            case 12:
                $monthname = _DECEMBER;
                break;
            default:
                $monthname = '????';
                break;
        }
        return $monthname;
    }

    // -----
    // The public method that creates the array of information used for various outputs by the
    // main report.
    //
    public function createReport()
    {
        global $db;

        // -----
        // First, gather the taxed and untaxed product sales for the requested period.  This will
        // provide the base into which additional order-total values will next be inserted.
        //
        $this->sales = $this->getProductSales();
        if (count($this->sales) === 0) {
            $this->debug('createReport, nothing found');
            return $this->sales;
        }

        // -----
        // Now, update the 'sales' array, gathering order-total values for the various timeframes.
        //
        $this->addTimeframeTotals();

        return $this->sales;
    }

    protected function getProductSales()
    {
        global $db;

        $sales_query_raw = "SELECT " . $this->getQueryTimeframeFields();
        $sales_query_raw .= ", SUM(CASE WHEN op.products_tax = 0 THEN ROUND(op.final_price * op.products_quantity, " . $this->decimal_places . ") ELSE 0 END) AS products_untaxed";
        $sales_query_raw .= ", SUM(CASE WHEN op.products_tax != 0 THEN ROUND(op.final_price * op.products_quantity, " . $this->decimal_places . ") ELSE 0 END) AS products_taxed";
        $sales_query_raw .= " FROM " . TABLE_ORDERS . " o";
        $sales_query_raw .= " INNER JOIN " . TABLE_ORDERS_PRODUCTS . " op ON op.orders_id = o.orders_id";
        $sales_query_raw .= $this->getQueryCommonConditions();
        $product_sales = $db->Execute($sales_query_raw);

        $sales = [];
        foreach ($product_sales as $timeframe) {
            $key = $this->getTimeframeKey($timeframe);
            $timeframe['products_total'] = $timeframe['products_untaxed'] + $timeframe['products_taxed'];
            $sales[$key] = $timeframe;
        }
        return $sales;
    }
    protected function addTimeframeTotals()
    {
        global $db;

        foreach ($this->base_classes as $class => $field_name) {
            if ($field_name === '') {
                continue;
            }
            $sales_query_raw = "SELECT " . $this->getQueryTimeframeFields();
            $sales_query_raw .= ', ' . $this->getTotalSum($class, $field_name);
            $sales_query_raw .= " FROM " . TABLE_ORDERS . " o";
            $sales_query_raw .= " INNER JOIN " . TABLE_ORDERS_TOTAL . " ot ON ot.orders_id = o.orders_id";
            $sales_query_raw .= " WHERE ot.class = '$class'";
            $sales_query_raw .= $this->getQueryCommonConditions('AND');
            $sales = $db->Execute($sales_query_raw);
            foreach ($sales as $timeframe) {
                $key = $this->getTimeframeKey($timeframe);
                $this->sales[$key] = array_merge($this->sales[$key], $timeframe);
            }
        }
        if ($this->additional_totals === true) {
            $sales_query_raw = "SELECT " . $this->getQueryTimeframeFields();
            $sales_query_raw .= ', SUM(CASE WHEN ot.class NOT IN (' . $this->baseClassesList . ') THEN ROUND(ot.value, ' . $this->decimal_places . ') ELSE 0 END) AS `other`';
            $sales_query_raw .= " FROM " . TABLE_ORDERS . " o";
            $sales_query_raw .= " INNER JOIN " . TABLE_ORDERS_TOTAL . " ot ON ot.orders_id = o.orders_id";
            $sales_query_raw .= $this->getQueryCommonConditions();
            $sales = $db->Execute($sales_query_raw);
            foreach ($sales as $timeframe) {
                $key = $this->getTimeframeKey($timeframe);
                $this->sales[$key] = array_merge($this->sales[$key], $timeframe);
            }
        }
    }
    protected function getTimeframeKey($timeframe)
    {
        $key = $timeframe['year'] . '-' . $timeframe['month'];
        if ($this->reportModeMonthly === false) {
            $key .= '-' . $timeframe['day'];
        }
        return $key;
    }
    protected function getTotalSum($ot_class, $field_name)
    {
        return "SUM(CASE WHEN ot.class = '$ot_class' THEN ROUND(ot.value, " . $this->decimal_places . ") ELSE 0 END) AS $field_name";
    }
    protected function getQueryTimeframeFields()
    {
        $query_fields = "DATE_FORMAT(o.date_purchased, '%Y') AS `year`, DATE_FORMAT(o.date_purchased, '%m') AS `month`";
        if ($this->reportModeMonthly === false) {
            $query_fields .= ", DATE_FORMAT(o.date_purchased, '%d') AS `day`";
        }
        return $query_fields;
    }
    protected function getQueryCommonConditions($connector = 'WHERE')
    {
        $conditions = '';
        if ($this->status !== '0') {
            $conditions = ' ' . $connector . ' o.orders_status = ' . $this->status;
            $connector = 'AND';
        }
        if ($this->reportModeMonthly === false) {
            $year_month = $this->selectedYear . '-' . $this->selectedMonth . '-';
            $conditions .= ' ' . $connector . " o.date_purchased BETWEEN '" . $year_month . "01 00:00:00' AND '" . $year_month . "31 23:59:59'";
        }
        $conditions .= ' ';
        $conditions .= "GROUP BY DATE_FORMAT(o.date_purchased, '%Y'), DATE_FORMAT(o.date_purchased, '%m')";
        if ($this->reportModeMonthly === false) {
            $conditions .= ", DATE_FORMAT(o.date_purchased, '%d')";
        }
        $conditions .= ' ORDER BY `year` ' . $this->sortDir . ', `month` ' . $this->sortDir;
        if ($this->reportModeMonthly === false) {
            $conditions .= ', `day` ' . $this->sortDir;
        }

        return $conditions;
    }

    // -----
    // A protected function to conditionally output a debug message.
    //
    protected function debug($message)
    {
        if ($this->debug) {
            error_log(date('Y-m-d H:i:s: ') . $message . "\n", 3, $this->debug_logfile);
        }
    }
}
