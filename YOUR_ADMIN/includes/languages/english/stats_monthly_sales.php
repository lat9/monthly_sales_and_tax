<?php
/*
 $Id: stats_monthly_sales.php, v2.0.3, 2022-04-13 (lat9)  $

  By SkipWater <skip@ccssinc.net> 11.24.2011
  With modifications by lat9: Copyright (c) 2013-2021 Vinos de Frutas Tropicales

  Powered by Zen-Cart (www.zen-cart.com)
  Portions Copyright (c) 2006 The Zen Cart Team

  Released under the GNU General Public License
  available at www.zen-cart.com/license/2_0.txt
  or see "license.txt" in the downloaded zip 

  DESCRIPTION: Monthly Sales Report
*/
//  Enables (on) or disables (off) the ability of the plugin to create a debug trace file (myDEBUG-sms.log) in the /logs directory.
define('STATS_MONTHLY_SALES_DEBUG', 'off');

/////////////
define('SMS_VERSION', 'v2.0.3');

define('HEADING_TITLE', 'Monthly Sales/Tax Summary');
define('HEADING_SUBTITLE', 'Viewing Sales for %s');
define('HEADING_SUBTITLE_STATUS', ', with orders-status of %s');    //-%s is filled in with the name of the orders-status selected
define('HEADING_TITLE_STATUS', 'Status');
define('TEXT_ALL_ORDERS', 'All orders');
define('TEXT_NOTHING_FOUND', 'No income for this date/status selection');
define('TEXT_BUTTON_REPORT_INVERT', 'Invert');
define('TEXT_BUTTON_REPORT_PRINT', 'Print');
define('TEXT_BUTTON_REPORT_SAVE', 'Save CSV');
define('TEXT_BUTTON_REPORT_BACK_DESC', 'Return to summary by months');
define('TEXT_BUTTON_REPORT_INVERT_DESC', 'Invert rows top to bottom');
define('TEXT_BUTTON_REPORT_PRINT_DESC', 'Show report in printer friendly window');
define('TEXT_BUTTON_REPORT_HELP_DESC', 'About this report and how to use its features');
define('TEXT_BUTTON_REPORT_GET_DETAIL', 'Click to report daily summary for this month');

define('TABLE_HEADING_YEAR','Year');
define('TABLE_HEADING_MONTH', 'Month');
define('TABLE_HEADING_DAY', 'Day');
define('TABLE_HEADING_INCOME', 'Gross Income');
define('TABLE_HEADING_SALES', 'Product Sales');
define('TABLE_HEADING_NONTAXED', 'Untaxed Sales');
define('TABLE_HEADING_TAXED', 'Taxed Sales');
define('TABLE_HEADING_TAX_COLL', 'Taxes Collected');
define('TABLE_HEADING_SHIPHNDL', 'Shipping &amp; Handling');
define('TABLE_HEADING_LOWORDER', 'Low Order Fees');
define('TABLE_HEADING_VOUCHER', 'Gift Vouchers');
define('TABLE_HEADING_COUPON', 'Coupons');
define('TABLE_HEADING_OTHER', 'Other');
define('TABLE_FOOTER_YEAR','YEAR');

// -----
// Language constants used by the report's AJAX handler (/includes/classes/ajax/zcAjaxMonthlySales.php).
//
define('SMS_AJAX_ORDER_ID', 'Order #');
define('SMS_AJAX_DATE_PURCHASED', 'Date Purchased');
define('SMS_AJAX_TAX_DESCRIPTION', 'Tax Description');
define('SMS_AJAX_TAX', 'Tax');
define('SMS_AJAX_TAX_TOTAL', 'Total:');

define('SMS_AJAX_TITLE_MONTHLY', 'Viewing Orders for %1$s, %2$u');      //-Uses monthname (%1$s), year (%2$u)
define('SMS_AJAX_TITLE_DAILY', 'Viewing Orders for %1$u %2$s, %3$u');   //-Uses day (%1$u), monthname (%2$s) and year (%3%u)

// -----
// I know, naughty HTML contained in language definitions, but there's no equivalent of a
// define-page in the admin :-(
//
define('HELP_CLOSE', 'Close');
define('HELP_CONTENT_HEADER', 'Using the Monthly Sales Report');
define('HELP_CONTENT_HTML', '
<h2>Reporting Store Activity by Month</h2>
<p>When initially selected from the Reports menu, this report displays a month-by-month financial summary of all orders in the store database.  Each month of the store\'s history is summarized in a row, showing the store income and its components and listing the amounts of taxes, shipping and handling charges, low-order fees, coupons and gift vouchers. The columns for the low-order fees, coupons and gift-vouchers are omitted from the report (and any such values considered &quot;other&quot;) if the associated feature is disabled in the store..</p>
<p>The top row is the current month and the following rows summarize each month of the store\'s order history.  Beneath the rows of each calendar year is a footer line, summarizing that year\'s totals in each column of the report. To invert the order of the rows, tick the <em>Invert</em> checkbox located in the top form-area of the display and click the <em>Go</em> button.</p>
<h2>Reporting monthly summary by days</h2>
<p>A month-based daily summary of activity is displayed by clicking the link on a month\'s name to the left of each row.  To return from the daily summary to the monthly summary, click the <em>Back</em> button located in the lower lefthand side of the daily display.</p>
<h2>What the columns represent</h2>
<p>Starting on the left, the month and year for the period are stated.  The remaining columns, left to right:</p><ul>
<li><b>' . TABLE_HEADING_INCOME . '</b> &mdash; the total of all orders. This is the sum of the totals for all orders for the period.</li>
<li><b>' . TABLE_HEADING_SALES . '</b> &mdash; the total sales of products purchased in the period. This the sum of each product\'s final price (less sales-discounts) times its quantity purchased.</li>
<li><b>' . TABLE_HEADING_NONTAXED . '</b> &mdash; the subtotal of <em>product</em> sales which were <b>not</b> taxed.</li>
<li><b>' . TABLE_HEADING_TAXED . '</b> &mdash; the subtotal of <em>product</em> sales which were taxed.</li>
<li><b>' . TABLE_HEADING_TAX_COLL . '</b> &mdash; the amount collected from customers for taxes, including any taxes applied to shipping charges.</li>
<li><b>' . TABLE_HEADING_SHIPHNDL . '</b> &mdash; the total shipping and handling charges collected.</li></ul><p>Finally, these optional fields are displayed:</p><ul>
<li><b>' . TABLE_HEADING_LOWORDER . '</b> &mdash; any low-order fees, if the store has those fees enabled.</li>
<li><b>' . TABLE_HEADING_VOUCHER . '</b> &mdash; any gift voucher charges, if the store has gift-vouchers enabled.</li>
<li><b>' . TABLE_HEADING_COUPON . '</b> &mdash; any coupon reductions, if the store has coupons enabled.</li>
<li><b>' . TABLE_HEADING_OTHER . '</b> &mdash; any other, non-core, totals (e.g. shipping insurance).</li>
</ul>
<h2>Selecting Report Summary by Status</h2>
<p>To show the monthly or daily summary information for just one Order Status, select the status in the drop-down box at the upper right of the report screen.  Depending on the store\'s setup for these values, there may be a status for <em>Pending</em> or <em>Shipped</em> for instance.  Change this status and the report will be recalculated and displayed.</p>
<h2>Showing Tax Details</h2>
<p>The amount of tax in any row of the report is a link to a modal window, which shows the name of the tax classes charged and their individual amounts.</p>
<h2>Printing the Report</h2>
<p>To view the report in a printer-friendly window, click on the <em>Print</em> button.  The store name and headers are added to show what orders were selected and when the report was generated.</p>
<h2>Saving Report Values to a File</h2>
<p>To save the values of the report to a local file, click on the <em>Save CSV</em> button at the top of the report.  The report values will be sent to your browser in a text file, and you will be prompted with a &quot;Save File&quot; dialog box to choose where to save the file.  The contents of the file are in Comma Separated Value (CSV) format, with a line for each row of the report beginning with the header line, and each value in the row is separated by commas. This file can be conveniently and accurately imported to common spreadsheet financial and statistical tools, such as Excel and QuattroPro. The file is provided to your browser with a suggested file name consisting of the report name, status selected and date/time of the report.</p>
<p>' . SMS_VERSION . '</p>');
