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

return [
    'pluginVersion' => 'v3.0.0',
    'pluginName' => 'Monthly Sales and Tax Summary mod for Zen Cart',
    'pluginDescription' => 'This report displays a summary of monthly or daily totals:<ul><li>gross income (order totals)</li><li>subtotals of all orders in the selected period</li><li>nontaxed sales subtotals</li><li>taxed sales subtotals</li><li>tax collected</li><li>shipping/handling charges</li><li>low order fees (if present)</li><li>gift vouchers (or other addl order total component, if present)</li></ul><br>The data comes from the orders and orders_total tables.',
    'pluginAuthor' => 'Vinos de Frutas Tropicales (lat9, retched)',
    'pluginId' => 734, // ID from Zen Cart forum
    'zcVersions' => ['v210'],
    'changelog' => 'changelog.md', // online URL (eg github release tag page, or changelog file there) or local filename only, ie: changelog.txt (in same dir as this manifest file)
    'github_repo' => 'https://github.com/lat9/monthly_sales_and_tax', // url
    'pluginGroups' => [],
];
