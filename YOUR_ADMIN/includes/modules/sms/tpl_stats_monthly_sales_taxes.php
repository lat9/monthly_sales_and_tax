<?php
// -----
// Part of the "Monthly Sales and Tax Report", v2.0.0.  Required by the report's AJAX handler:
// /admin/includes/classes/ajax/zcAjaxMonthlySales.php.
//
?>
<div class="container-fluid">
    <table class="table table-striped">
        <thead>
            <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent text-center"><?php echo SMS_AJAX_ORDER_ID; ?></td>
                <td class="dataTableHeadingContent"><?php echo SMS_AJAX_DATE_PURCHASED; ?></td>
                <td class="dataTableHeadingContent"><?php echo SMS_AJAX_TAX_DESCRIPTION; ?></td>
                <td class="dataTableHeadingContent text-right"><?php echo SMS_AJAX_TAX; ?></td>
            </tr>
        </thead>
        <tbody>
<?php
if ($taxes->EOF) {
?>
            <tr class="dataTableRow text-center">
                <td class="dataTableContent" colspan="4"><?php echo TEXT_NOTHING_FOUND; ?></td>
            </tr>
<?php
} else {
    $tax_total = 0;
    foreach ($taxes as $tax) {
        $tax_total += round($tax['value'], $sms->decimal_places);
?>
            <tr class="dataTableRow">
                <td class="dataTableContent text-center"><?php echo $tax['orders_id']; ?></td>
                <td class="dataTableContent"><?php echo $tax['date_purchased']; ?></td>
                <td class="dataTableContent"><?php echo $tax['title']; ?></td>
                <td class="dataTableContent text-right"><?php echo $sms->formatValue($tax['value']); ?></td>
            </tr>
<?php
    }
?>
            <tr class="dataTableHeadingRow">
                <td colspan="3" class="dataTableHeadingContent text-right"><?php echo SMS_AJAX_TAX_TOTAL; ?></td>
                <td class="dataTableHeadingContent text-right"><?php echo $sms->formatValue($tax_total); ?></td>
            </tr>
<?php
}
?>
        </tbody>
    </table>
</div>
