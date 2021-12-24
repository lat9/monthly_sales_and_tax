<?php
/*
 $Id: stats_monthly_sales.php, v 1.4 2011/11/24  $																    
                                                     
  By SkipWater <skip@ccssinc.net> 11.24.2011
                                                      
  Powered by Zen-Cart (www.zen-cart.com)              
  Portions Copyright (c) 2006 The Zen Cart Team       
                                                      
  Released under the GNU General Public License       
  available at www.zen-cart.com/license/2_0.txt       
  or see "license.txt" in the downloaded zip          

  DESCRIPTION: Add Monthly Report link to Reports menu
*/

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

if (function_exists('zen_register_admin_page')) {
    if (!zen_page_key_exists('stats_monthly_sales')) {
        // Add Monthly Report link to Reports menu
        zen_register_admin_page('stats_monthly_sales', 'BOX_STATS_SALES_TOTALS','FILENAME_STATS_MONTHLY_SALES', '', 'reports', 'Y', 17);
    }
}
?>