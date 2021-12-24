<?php
/*
 $Id: stats_monthly_sales.php, v 1.4 2011/11/24  $
                                                     
  By SkipWater <skip@ccssinc.net> 11.24.2011
  With modifications by lat9: Copyright (c) 2013-2014 Vinos de Frutas Tropicales
  
                                                      
  Powered by Zen-Cart (www.zen-cart.com)              
  Portions Copyright (c) 2006 The Zen Cart Team       
                                                      
  Released under the GNU General Public License       
  available at www.zen-cart.com/license/2_0.txt       
  or see "license.txt" in the downloaded zip 

  DESCRIPTION: Monthly Sales Report
*/
//-bof-a-lat9
//  Identifies whether the Shipping & Handling values are to be merged into the overall totals, one of:
//  - auto ... (default) S/H charges added to taxed/untaxed totals based on whether shipping is taxed for the store (i.e. sort order of "Tax" order total is greater than that of "Shipping").
//  - on ..... S/H charges added to taxed/untaxed totals *regardless* of store's Order Totals settings
//  - off .... S/H charges kept separate from taxed/untaxed totals *regardless* of store's Order Totals settings
define('STATS_MONTHLY_SALES_COMBINE_SHIPPING', 'auto');
//  Enables (on) or disables (off) the ability of the plugin to create a debug trace file (myDEBUG-sms.log) in the /logs directory (v1.5.1 and later) or the /cache directory otherwise.
define('STATS_MONTHLY_SALES_DEBUG', 'off');

/////////////
define('SMS_VERSION', 'v1.5');
define('SALES_QUERY_ADDITIONAL', ' AND o.orders_status != 101'); 
define('TABLE_HEADING_NONTAXED_TOTAL', 'Untaxed Total (incl. S/H)');
define('TABLE_HEADING_TAXED_TOTAL', 'Taxed Total (incl. S/H)');
define('TABLE_HEADING_VOUCHER', 'Gift Vouchers');
define('TABLE_HEADING_COUPON', 'Coupons');
define('TEXT_ORDER_NUM', 'Order #: ');
define('TEXT_SHOW_DETAIL', 'Show Detail');
define('TEXT_TOTAL', 'Total');
define('FORMAT_YYYYMMDD', 'Tax Details: %1$4d-%2$02d-%3$02d');  // yyyy-mm-dd
define('FORMAT_YYYYMM', 'Tax Details: %1$4d-%2$02d'); // yyyy-mm

//-eof-a-lat9

define('HEADING_TITLE', 'Monthly Sales/Tax Summary');
define('HEADING_TITLE_STATUS','Status');
define('HEADING_TITLE_REPORTED','Reported');
define('TEXT_DETAIL','Detail');
define('TEXT_ALL_ORDERS', 'All orders');
define('TEXT_NOTHING_FOUND', 'No income for this date/status selection');
define('TEXT_BUTTON_REPORT_BACK','Back');
define('TEXT_BUTTON_REPORT_INVERT','Invert');
define('TEXT_BUTTON_REPORT_PRINT','Print');
define('TEXT_BUTTON_REPORT_SAVE','Save CSV');
define('TEXT_BUTTON_REPORT_HELP','Help');
define('TEXT_BUTTON_REPORT_BACK_DESC', 'Return to summary by months');
define('TEXT_BUTTON_REPORT_INVERT_DESC', 'Invert rows top to bottom');
define('TEXT_BUTTON_REPORT_PRINT_DESC', 'Show report in printer friendly window');
define('TEXT_BUTTON_REPORT_HELP_DESC', 'About this report and how to use its features');
define('TEXT_BUTTON_REPORT_GET_DETAIL', 'Click to report daily summary for this month');
define('TEXT_REPORT_DATE_FORMAT', 'j M Y -   g:i a'); // date format string
//  as specified in php manual here: http://www.php.net/manual/en/function.date.php

define('TABLE_HEADING_YEAR','Year');
define('TABLE_HEADING_MONTH', 'Month');
define('TABLE_HEADING_DAY', 'Day');
define('TABLE_HEADING_INCOME', 'Gross Income');
define('TABLE_HEADING_SALES', 'Product Sales');
define('TABLE_HEADING_NONTAXED', 'Untaxed Sales');
define('TABLE_HEADING_TAXED', 'Taxed Sales');
define('TABLE_HEADING_TAX_COLL', 'Taxes Collected');
define('TABLE_HEADING_SHIPHNDL', 'Shipping &amp; Handling');
define('TABLE_HEADING_SHIP_TAX', 'Tax on Shipping');
define('TABLE_HEADING_LOWORDER', 'Low Order Fees');
define('TABLE_HEADING_OTHER', 'Other');
define('TABLE_FOOTER_YTD','YTD');
define('TABLE_FOOTER_YEAR','YEAR');
define('TEXT_HELP', '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title>Monthly Sales/Tax Report</title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<style type="text/css">
<!--
h2 { font-size: 14px; }
#helpTable { width: 95%; margin: 0 auto; text-align: justify; }
#helpTable p { font-size: 10px; }
-->
</style>

</head>
<body>
<table id="helpTable" width="95%"><tr><td>
<h1>How to view and use the store income summary report</h1>
<h2>Reporting Store Activity by Month</h2>
<p>When initially selected from the Reports menu, this report displays a month-by-month financial summary of all orders in the store database.  Each month of the store\'s history is summarized in a row, showing the store income and its components and listing the amounts of taxes, shipping and handling charges, low order fees and gift vouchers &mdash; if the store does not have low order fees or gift vouchers enabled, these columns are omitted from the report.</p>
<p>The top row is the current month and the following rows summarize each month of the store\'s order history.  Beneath the rows of each calendar year is a footer line, summarizing that year\'s totals in each column of the report. To invert the order of the rows, click the "Invert" link located in the upper righthand side of the display.</p>
<h2>Reporting monthly summary by days</h2>
<p>A month-based daily summary of activity is displayed by clicking the link on a month\'s name to the left of each row.  To return from the daily summary to the monthly summary, click the "Back" link located in the upper righthand side of the daily display.</p>
<h2>What the columns represent</h2>
<p>Starting on the left, the month and year for the period are stated.  The remaining columns, left to right:</p><ul>
<li><b>' . TABLE_HEADING_INCOME . '</b> &mdash; the total of all orders. This is the sum of the totals for all orders for the period.</li>
<li><b>' . TABLE_HEADING_SALES . '</b> &mdash; the total sales of products purchased in the month. This the sum of each product\'s final price (less discounts) times its quantity purchased.</li>
</ul><p>Then, depending on whether or not shipping for the store is taxed:</p><ul>
<li>Either <b>' . TABLE_HEADING_NONTAXED_TOTAL . '</b> or <b>' . TABLE_HEADING_NONTAXED . '</b> &mdash; the subtotal of sales which were <em>not</em> taxed, including the shipping charges if shipping is taxed for the store.</li>
<li>Either <b>' . TABLE_HEADING_TAXED_TOTAL . '</b> or <b>' . TABLE_HEADING_TAXED . '</b> &mdash; the subtotal of sales which were taxed, including the shipping charges if shipping is taxed for the store.</li></ul><p>Followed by some columns that are always displayed:</p><ul>
<li><b>' . TABLE_HEADING_TAX_COLL . '</b> &mdash; the amount collected from customers for taxes, including any taxes applied to shipping charges.</li>
<li><b>' . TABLE_HEADING_SHIPHNDL . '</b> &mdash; the total shipping and handling charges collected.</li>
<li><b>' . TABLE_HEADING_SHIP_TAX . '</b> &mdash; the tax on shipping and handling charges.</li></ul><p>Finally, these optional fields are displayed:</p><ul>
<li><b>' . TABLE_HEADING_LOWORDER . '</b> &mdash; any low-order fees, if the store has them enabled.</li>
<li><b>' . TABLE_HEADING_VOUCHER . '</b> &mdash; any gift voucher charges, if the store has them enabled.</li>
<li><b>' . TABLE_HEADING_OTHER . '</b> &mdash; any other, non-core, totals (e.g. shipping insurance).</li>
</ul>
<h2>Selecting report summary by status</h2>
<p>To show the monthly or daily summary information for just one Order Status, select the status in the drop-down box at the upper right of the report screen.  Depending on the store\'s setup for these values, there may be a status for "Pending" or "Shipped" for instance.  Change this status and the report will be recalculated and displayed.</p>
<h2>Showing detail of taxes</h2>
<p>The amount of tax in any row of the report is a link to a popup window, which shows the name of the tax classes charged and their individual amounts.</p>
<h2>Printing the report</h2>
<p>To view the report in a printer-friendly window, click on the "Print" button, then use your browser\'s print command in the File menu.  The store name and headers are added to show what orders were selected, and when the report was generated.</p>
<h2>Saving Report Values to a File</h2>
<p>To save the values of the report to a local file, click on the Save CSV button at the bottom of the report.  The report values will be sent to your browser in a text file, and you will be prompted with a Save File dialog box to choose where to save the file.  The contents of the file are in Comma Separated Value (CSV) format, with a line for each row of the report beginning with the header line, and each value in the row is separated by commas. This file can be conveniently and accurately imported to common spreadsheet financial and statistical tools, such as Excel and QuattroPro. The file is provided to your browser with a suggested file name consisting of the report name, status selected and date/time of the report.<br /><br /></p>
<p>' . SMS_VERSION . '</p>
</td></tr>
</table>
</body>
</html>');
?>
