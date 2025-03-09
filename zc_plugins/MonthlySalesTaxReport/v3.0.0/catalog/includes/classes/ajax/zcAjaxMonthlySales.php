<?php
/**
 * Monthly Sales and Tax Summary mod for Zen Cart
 * Version 3.0.0
 * @copyright Portions Copyright 2004-2025 Zen Cart Team
 * @author Vinos de Frutas Tropicales (lat9)
****************************************************************************
    Copyright (C) 2024  Paul Williams

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
// Part of the "Monthly Sales and Tax" plugin (v2.0.0+) by Cindy Merkin (lat9)
// Copyright (c) 2021-2025 Vinos de Frutas Tropicales
//
use Zencart\Traits\InteractsWithPlugins;

class zcAjaxMonthlySales extends base
{
    use Zencart\Traits\InteractsWithPlugins;

    public function getTaxes()
    {
        global $db;

        // -----
        // Use the base trait to determine this plugin's directory location.
        //
        $this->detectZcPluginDetails(__DIR__);

        $html = '';
        $title = '';
        if (ctype_digit($_POST['status'] ?? 'x') && ctype_digit($_POST['year'] ?? 'x') && ctype_digit($_POST['month'] ?? 'x')) {
            $year = $_POST['year'];
            $month = $_POST['month'];
            $status = $_POST['status'];
            $day = (isset($_POST['day']) && ctype_digit($_POST['day'])) ? $_POST['day'] : false;

            $sql_query =
                "SELECT ot.value, ot.title, ot.orders_id, o.date_purchased
                   FROM " . TABLE_ORDERS_TOTAL . " ot
                        INNER JOIN " . TABLE_ORDERS . " o
                            ON o.orders_id = ot.orders_id
                  WHERE ot.class = 'ot_tax'";
            if ($status !== '0') {
                $sql_query .= ' AND o.orders_status = ' . $status;
            }
            if ($year !== '0') {
                $sql_query .= ' AND YEAR(o.date_purchased) = ' . $year;
            }
            if ($month !== '0') {
                $sql_query .= ' AND MONTH(o.date_purchased) = ' . $month;
            }
            if ($day !== false) {
                $sql_query .= ' AND DAY(o.date_purchased) = ' . $day;
            }
            $selected_state = ($_POST['state'] ?? 'all');
            if ($selected_state !== 'all') {
                $sql_query .= " AND o.delivery_state = '" . zen_db_input($_POST['state']) . "'";
            }
            $taxes = $db->Execute($sql_query);

            require $this->pluginManagerInstalledVersionDirectory . 'admin/' . DIR_WS_CLASSES . 'MonthlySalesAndTax.php';
            $sms = new MonthlySalesAndTax($status, 'ASC', $year, $month, $selected_state);

            $monthname = $sms->getMonthName($month);
            $title = ($day === false) ? sprintf(SMS_AJAX_TITLE_MONTHLY, $monthname, $year) : sprintf(SMS_AJAX_TITLE_DAILY, $day, $monthname, $year);

            $this->disableGzip();
            ob_start();
            require $this->pluginManagerInstalledVersionDirectory . 'admin/' . DIR_WS_MODULES . 'sms/tpl_stats_monthly_sales_taxes.php';
            $html = ob_get_clean();
        }
        $response = [
            'title' => $title,
            'html' => $html,
        ];
        return $response;
    }

    // -----
    // Gzip compression can "get in the way" of the AJAX requests on current versions of IE and
    // Chrome.
    //
    // This internal method sets that compression "off" for the AJAX responses.
    //
    protected function disableGzip()
    {
        @ob_end_clean();
        @ini_set('zlib.output_compression', '0');
    }
}
