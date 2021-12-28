<?php
// -----
// Part of the "Monthly Sales and Tax" plugin (v2.0.0+) for Zen Cart 157+.
// Copyright (C) 2021, Vinos de Frutas Tropicales.
//
class MonthlySalesAndTax extends base
{
    public
        $sales,         //-An array of sales' information
        $status;        //-The orders-status value used to create the above array (0 if all statuses)

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
        $base_classes = [
            'ot_subtotal',
            'ot_tax',
            'ot_shipping',
            'ot_total',
        ];
        if ($this->using_loworder) {
            $base_classes[] = 'ot_loworderfee';
        }
        if ($this->using_gv) {
            $base_classes[] = 'ot_gv';
        }
        if ($this->using_coupon) {
            $base_classes[] = 'ot_coupon';
        }
        $this->baseClassesList = "'" . implode("', '", $base_classes) . "'";
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
        $this->selectedDay = '0';

        $this->reportModeMonthly = ($this->selectedYear === '0' && $this->selectedMonth === '0');

        $this->sortDir = ($sort_dir === 'DESC') ? 'DESC' : 'ASC';

        $this->sales = $this->createReportDates();

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
    // Called from the class constructor to determine the dates (either year/month or year/month/day) to
    // be used for the report-generation method (createReport).
    //
    protected function createReportDates()
    {
        global $db;

        $sql_query = "SELECT DISTINCT MONTHNAME(o.date_purchased) AS `monthname`, YEAR(o.date_purchased) AS `year`, MONTH(o.date_purchased) AS `month` ";
        if ($this->reportModeMonthly === false) {
            $sql_query .= ', DAY(o.date_purchased) AS `day` ';
        }
        $sql_query .= 'FROM ' . TABLE_ORDERS . ' AS o ';
        
        $connector = ' WHERE';
        if ($this->status !== '0') {
            $connector = ' AND';
            $sql_query .= ' WHERE o.orders_status = ' . $this->status;
        }
        if ($this->reportModeMonthly === false) {
            $sql_query .= $connector . ' YEAR(o.date_purchased) = ' . $this->selectedYear . ' AND MONTH(o.date_purchased) = ' . $this->selectedMonth . ' ';
        }
        $sql_query .= ' ORDER BY o.date_purchased ' . $this->sortDir;
        $sales_dates = $db->Execute($sql_query);

        $sales = [];
        foreach ($sales_dates as $sale) {
            $sales[] = $sale;
        }
        return $sales;
    }

    // -----
    // The public method that creates the array of information used for various outputs by the
    // main report.
    //
    // The constructor has already created the class variable $this->sales which, at this point,
    // contains fields 'yearmonth', 'year', 'month' and (optionally) 'day'.  Those dates will be
    // used to fill-in each record's sales-values.
    //
    // Note:  The class variables $this->selectedYear, $this->selectedMonth and $this->selectedDay
    // are used by the class method addStatusAndDate to augment each SQL query to gather information
    // only for the specified year/month and (optional) day.
    //
    public function createReport()
    {
        global $db;

        if (count($this->sales) === 0) {
            $this->debug('createReport, nothing found');
            return $this->sales;
        }

        foreach ($this->sales as &$next_sale) {
            $this->selectedYear = $next_sale['year'];
            $this->selectedMonth = $next_sale['month'];
            if (!empty($next_sale['day'])) {
                $this->selectedDay = $next_sale['day'];
            }

            $next_sale['gross_sales'] = $this->getOrderTotalTotal('ot_total');
            $next_sale['products_untaxed'] = $this->getSalesTotalUntaxed();
            $next_sale['products_taxed'] = $this->getSalesTotalTaxed();
            $next_sale['products_total'] = $next_sale['products_untaxed'] + $next_sale['products_taxed'];
            $next_sale['tax'] = $this->getOrderTotalTotal('ot_tax');
            $next_sale['shipping'] = $this->getOrderTotalTotal('ot_shipping');
            if ($this->using_loworder === true) {
                $next_sale['loworder'] = $this->getOrderTotalTotal('ot_loworderfee');
            }
            if ($this->using_gv === true) {
                $next_sale['gv'] = $this->getOrderTotalTotal('ot_gv');
            }
            if ($this->using_coupon === true) {
                $next_sale['coupon'] = $this->getOrderTotalTotal('ot_coupon');
            }
            if ($this->additional_totals === true) {
                $sql_query =
                    "SELECT ot.value
                       FROM " . TABLE_ORDERS . " o
                            INNER JOIN " . TABLE_ORDERS_TOTAL . " ot
                                ON o.orders_id = ot.orders_id 
                      WHERE ot.class NOT IN (" . $this->baseClassesList . ")";
                $sql_query .= $this->addStatusAndDate();
                $others = $db->Execute($sql_query);
                
                $other_value = 0;
                foreach ($others as $other) {
                    $other_value += round($other['value'], $this->decimal_places);
                }
                $next_sale['other'] = $other_value;
            }
        }
        return $this->sales;
    }

    // -----
    // Protected helper-methods to retrieve the current date's taxed and untaxed product sales.
    //
    protected function getSalesTotalUntaxed()
    {
        return $this->getSalesTotal(false);
    }
    protected function getSalesTotalTaxed()
    {
        return $this->getSalesTotal(true);
    }
    protected function getSalesTotal($taxed)
    {
        global $db;

        $sales_query_raw = 
            "SELECT op.final_price, op.products_quantity
               FROM " . TABLE_ORDERS_PRODUCTS . " op
                    INNER JOIN " . TABLE_ORDERS . " o
                        ON o.orders_id = op.orders_id ";
        if ($taxed) {
            $sales_query_raw .= ' WHERE op.products_tax != 0';
        } else {
            $sales_query_raw .= ' WHERE op.products_tax = 0';
        }
        $sales_query_raw .= $this->addStatusAndDate();
        $sales = $db->Execute($sales_query_raw);

        $sales_total = 0;
        foreach ($sales as $sale) {
            $sales_total += round($sale['final_price'] * $sale['products_quantity'], $this->decimal_places);
        }
        return $sales_total;
    }

    // -----
    // Protected method to compute and return the sum of order-values for a given
    // order-total for the current date.
    //
    protected function getOrderTotalTotal($total_class)
    {
        global $db;

        $sales_query_raw =
            "SELECT ot.value
               FROM " . TABLE_ORDERS . " o
                    INNER JOIN " . TABLE_ORDERS_TOTAL . " ot
                        ON o.orders_id = ot.orders_id
              WHERE ot.class = ':total_class:'";
        $sales_query_raw .= $this->addStatusAndDate();

        $sales_query = $db->bindVars($sales_query_raw, ':total_class:', $total_class, 'noquotestring');
        $sales = $db->Execute($sales_query);

        $total_value = 0;
        foreach ($sales as $sale) {
            $total_value += round($sale['value'], $this->decimal_places);
        }
        return $total_value;
    }

    // -----
    // Protected method that produces additional SQL query qualifiers based on the
    // current report's date settings.
    //
    protected function addStatusAndDate($connector = 'AND')
    {
        $status_and_date = '';
        if ($this->status !== '0') {
            $status_and_date .= ' ' . $connector . ' o.orders_status = ' . $this->status;
            $connector = 'AND';
        }
        if ($this->selectedMonth !== '0') {
            $status_and_date .= ' ' . $connector . ' MONTH(o.date_purchased) = ' . $this->selectedMonth;
            $connector = 'AND';
        }
        if ($this->selectedYear !== '0') {
            $status_and_date .= ' ' . $connector . ' YEAR(o.date_purchased) = ' . $this->selectedYear;
            $connector = 'AND';
        }
        if ($this->selectedDay !== '0') {
            $status_and_date .= ' ' . $connector . ' DAY(o.date_purchased) = ' . $this->selectedDay;
        }
        $status_and_date .= ' ORDER BY o.date_purchased ' . $this->sortDir;

        return $status_and_date;
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
