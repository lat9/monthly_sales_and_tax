<?php
/**
 * Monthly Sales and Tax Summary mod for Zen Cart
 * Version 3.0.0
 * @copyright Portions Copyright 2004-2025 Zen Cart Team
 * @author Vinos de Frutas Tropicales (lat9)

  By SkipWater <skip@ccssinc.net> 11.24.2011
  With modifications by lat9: Copyright (c) 2013-2022 Vinos de Frutas Tropicales

  Powered by Zen-Cart (www.zen-cart.com)
  Portions Copyright (c) 2006 The Zen Cart Team

  Released under the GNU General Public License
  available at www.zen-cart.com/license/2_0.txt
  or see "license.txt" in the downloaded zip 

  DESCRIPTION: Monthly Sales Report
*/
$define = [
    'STATS_MONTHLY_SALES_DEBUG' => 'off', //  Enables (on) or disables (off) the ability of the plugin to create a debug trace file (myDEBUG-sms.log) in the /logs directory.

    'SMS_VERSION' => 'v3.0.0',

    'HEADING_TITLE' => 'Monthly Sales/Tax Summary',
    'HEADING_SUBTITLE' => 'Viewing Sales for %s',
    'HEADING_SUBTITLE_STATUS' => ', with orders-status of %s',    //-%s is filled in with the name of the orders-status selected
    'HEADING_SUBTITLE_STATE' => ' and this state: %s',
    'HEADING_TITLE_STATUS' => 'Status: ',
    'TEXT_ALL_ORDERS' => 'All orders',
    'HEADING_TITLE_STATE' => 'State: ',
    'TEXT_ALL_STATES' => 'All states',
    'TEXT_NOTHING_FOUND' => 'No income for this date/status selection',
    'TEXT_BUTTON_REPORT_INVERT' => 'Invert',
    'TEXT_BUTTON_REPORT_PRINT' => 'Print',
    'TEXT_BUTTON_REPORT_SAVE' => 'Save CSV',
    'TEXT_BUTTON_REPORT_BACK_DESC' => 'Return to summary by months',
    'TEXT_BUTTON_REPORT_INVERT_DESC' => 'Invert rows top to bottom',
    'TEXT_BUTTON_REPORT_PRINT_DESC' => 'Show report in printer friendly window',
    'TEXT_BUTTON_REPORT_HELP_DESC' => 'About this report and how to use its features',
    'TEXT_BUTTON_REPORT_GET_DETAIL' => 'Click to report daily summary for this month',

    'TABLE_HEADING_YEAR' => 'Year',
    'TABLE_HEADING_MONTH' => 'Month',
    'TABLE_HEADING_DAY' => 'Day',
    'TABLE_HEADING_INCOME' => 'Gross Income',
    'TABLE_HEADING_SALES' => 'Product Sales',
    'TABLE_HEADING_NONTAXED' => 'Untaxed Sales',
    'TABLE_HEADING_TAXED' => 'Taxed Sales',
    'TABLE_HEADING_TAX_COLL' => 'Taxes Collected',
    'TABLE_HEADING_SHIPHNDL' => 'Shipping &amp; Handling',
    'TABLE_HEADING_LOWORDER' => 'Low Order Fees',
    'TABLE_HEADING_VOUCHER' => 'Gift Vouchers',
    'TABLE_HEADING_COUPON' => 'Coupons',
    'TABLE_HEADING_OTHER' => 'Other',
    'TABLE_FOOTER_YEAR' => 'YEAR',

    // -----
    // Language constants used by the report's AJAX handler (/includes/classes/ajax/zcAjaxMonthlySales.php).
    //
    'SMS_AJAX_ORDER_ID' => 'Order #',
    'SMS_AJAX_DATE_PURCHASED' => 'Date Purchased',
    'SMS_AJAX_TAX_DESCRIPTION' => 'Tax Description',
    'SMS_AJAX_TAX' => 'Tax',
    'SMS_AJAX_TAX_TOTAL' => 'Total:',

    'SMS_AJAX_TITLE_MONTHLY' => 'Viewing Orders for %1$s, %2$u',      //-Uses monthname (%1$s), year (%2$u)
    'SMS_AJAX_TITLE_DAILY' => 'Viewing Orders for %1$u %2$s, %3$u',   //-Uses day (%1$u), monthname (%2$s) and year (%3%u)

    'HELP_CLOSE' => 'Close',
    'HELP_CONTENT_HEADER' => 'Using the Monthly Sales Report',
];

$table_heading_income = $define['TABLE_HEADING_INCOME'];
$table_heading_sales = $define['TABLE_HEADING_SALES'];
$table_heading_nontaxed = $define['TABLE_HEADING_NONTAXED'];
$table_heading_taxed = $define['TABLE_HEADING_TAXED'];
$table_heading_tax_coll = $define['TABLE_HEADING_TAX_COLL'];
$table_heading_shiphndl = $define['TABLE_HEADING_SHIPHNDL'];
$table_heading_loworder = $define['TABLE_HEADING_LOWORDER'];
$table_heading_voucher = $define['TABLE_HEADING_VOUCHER'];
$table_heading_coupon = $define['TABLE_HEADING_COUPON'];
$table_heading_other = $define['TABLE_HEADING_OTHER'];
$sms_version = $define['SMS_VERSION'];

/**
 * -----
 * I know, naughty HTML contained in language definitions, but there's no equivalent of a
 * define-page in the admin :-( - lat9
 *
 * Be careful about editing this out. If you want to use a define, create a variable and 
 * assign the constant there. THEN use the new variable where you want to put the constant.
 * Otherwise... uhh... look up how PHP Heredocs work. (keep the first line as is and keep 
 * the EOF; at the end on its own separate line.
 * - retched
 */
$define['HELP_CONTENT_HTML'] =<<<EOF

<h2>Reporting Store Activity by Month</h2>
<p>When initially selected from the Reports menu, this report displays a month-by-month financial summary of all orders in the store database.  Each month of the store's history is summarized in a row, showing the store income and its components and listing the amounts of taxes, shipping and handling charges, low-order fees, coupons and gift vouchers. The columns for the low-order fees, coupons and gift-vouchers are omitted from the report (and any such values considered &quot;other&quot;) if the associated feature is disabled in the store..</p>
<p>The top row is the current month and the following rows summarize each month of the store's order history.  Beneath the rows of each calendar year is a footer line, summarizing that year's totals in each column of the report. To invert the order of the rows, tick the <em>Invert</em> checkbox located in the top form-area of the display and click the <em>Go</em> button.</p>
<h2>Reporting monthly summary by days</h2>
<p>A month-based daily summary of activity is displayed by clicking the link on a month's name to the left of each row.  To return from the daily summary to the monthly summary, click the <em>Back</em> button located in the lower lefthand side of the daily display.</p>
<h2>What the columns represent</h2>
<p>Starting on the left, the month and year for the period are stated.  The remaining columns, left to right:</p><ul>
<li><b>$table_heading_income</b> &mdash; the total of all orders. This is the sum of the totals for all orders for the period.</li>
<li><b>$table_heading_sales</b> &mdash; the total sales of products purchased in the period. This the sum of each product's final price (less sales-discounts) times its quantity purchased.</li>
<li><b>$table_heading_nontaxed</b> &mdash; the subtotal of <em>product</em> sales which were <b>not</b> taxed.</li>
<li><b>$table_heading_taxed</b> &mdash; the subtotal of <em>product</em> sales which were taxed.</li>
<li><b>$table_heading_tax_coll</b> &mdash; the amount collected from customers for taxes, including any taxes applied to shipping charges.</li>
<li><b>$table_heading_shiphndl </b> &mdash; the total shipping and handling charges collected.</li></ul><p>Finally, these optional fields are displayed:</p><ul>
<li><b>$table_heading_loworder</b> &mdash; any low-order fees, if the store has those fees enabled.</li>
<li><b>$table_heading_voucher</b> &mdash; any gift voucher charges, if the store has gift-vouchers enabled.</li>
<li><b>$table_heading_coupon</b> &mdash; any coupon reductions, if the store has coupons enabled.</li>
<li><b>$table_heading_other</b> &mdash; any other, non-core, totals (e.g. shipping insurance).</li>
</ul>
<h2>Selecting Report Summary by Status</h2>
<p>To show the monthly or daily summary information for just one Order Status, select the status in the drop-down box at the upper right of the report screen.  Depending on the store's setup for these values, there may be a status for <em>Pending</em> or <em>Shipped</em> for instance.  Change this status and the report will be recalculated and displayed.</p>
<h2>Showing Tax Details</h2>
<p>The amount of tax in any row of the report is a link to a modal window, which shows the name of the tax classes charged and their individual amounts.</p>
<h2>Printing the Report</h2>
<p>To view the report in a printer-friendly window, click on the <em>Print</em> button.  The store name and headers are added to show what orders were selected and when the report was generated.</p>
<h2>Saving Report Values to a File</h2>
<p>To save the values of the report to a local file, click on the <em>Save CSV</em> button at the top of the report.  The report values will be sent to your browser in a text file, and you will be prompted with a &quot;Save File&quot; dialog box to choose where to save the file.  The contents of the file are in Comma Separated Value (CSV) format, with a line for each row of the report beginning with the header line, and each value in the row is separated by commas. This file can be conveniently and accurately imported to common spreadsheet financial and statistical tools, such as Excel and QuattroPro. The file is provided to your browser with a suggested file name consisting of the report name, status selected and date/time of the report.</p>
<p>$sms_version</p>
EOF;

return $define;
