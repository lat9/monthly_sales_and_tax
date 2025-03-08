<?php
/**
 * Monthly Sales and Tax Summary mod for Zen Cart
 * Version 3.0.0
 * @copyright Portions Copyright 2004-2025 Zen Cart Team
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
// Part of the "Monthly Sales and Tax Report", v3.0.0.  Required by the report's AJAX handler:
// /admin/includes/classes/ajax/zcAjaxMonthlySales.php.
//
?>
<div class="container-fluid">
    <table class="table table-striped">
        <thead>
            <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent text-center"><?= SMS_AJAX_ORDER_ID ?></td>
                <td class="dataTableHeadingContent"><?= SMS_AJAX_DATE_PURCHASED ?></td>
                <td class="dataTableHeadingContent"><?= SMS_AJAX_TAX_DESCRIPTION ?></td>
                <td class="dataTableHeadingContent text-right"><?= SMS_AJAX_TAX ?></td>
            </tr>
        </thead>
        <tbody>
<?php
if ($taxes->EOF) {
?>
            <tr class="dataTableRow text-center">
                <td class="dataTableContent" colspan="4"><?= TEXT_NOTHING_FOUND ?></td>
            </tr>
<?php
} else {
    $tax_total = 0;
    foreach ($taxes as $tax) {
        $tax_total += round($tax['value'], $sms->getDecimalPlaces());
?>
            <tr class="dataTableRow">
                <td class="dataTableContent text-center"><?= $tax['orders_id'] ?></td>
                <td class="dataTableContent"><?= $tax['date_purchased'] ?></td>
                <td class="dataTableContent"><?= $tax['title'] ?></td>
                <td class="dataTableContent text-right"><?= $sms->formatValue($tax['value']) ?></td>
            </tr>
<?php
    }
?>
            <tr class="dataTableHeadingRow">
                <td colspan="3" class="dataTableHeadingContent text-right"><?= SMS_AJAX_TAX_TOTAL ?></td>
                <td class="dataTableHeadingContent text-right"><?= $sms->formatValue($tax_total) ?></td>
            </tr>
<?php
}
?>
        </tbody>
    </table>
</div>
