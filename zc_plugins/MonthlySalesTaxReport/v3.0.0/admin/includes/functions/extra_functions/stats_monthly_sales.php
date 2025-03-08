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
/*
 $Id: stats_monthly_sales.php, v2.0.0, 2021-12-24  $

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
    if (!zen_page_key_exists ('reportSalesWithGraphs')) {
        zen_register_admin_page('reportSalesWithGraphs', 'BOX_REPORTS_SALES_REPORT_GRAPHS', 'FILENAME_STATS_SALES_REPORT_GRAPHS', '', 'reports', 'Y', 15);
    }
}
