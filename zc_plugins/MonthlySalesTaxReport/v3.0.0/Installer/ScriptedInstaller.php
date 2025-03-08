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

use Zencart\PluginSupport\ScriptedInstaller as ScriptedInstallBase;

class ScriptedInstaller extends ScriptedInstallBase
{
    protected function executeInstall()
    {
        if (!$this->purgeOldFiles()) {
            return false;
        }

        if (!zen_page_key_exists('stats_monthly_sales')) {
            // Add Monthly Report link to Reports menu
            zen_register_admin_page('stats_monthly_sales', 'BOX_STATS_SALES_TOTALS', 'FILENAME_STATS_MONTHLY_SALES', '', 'reports', 'Y');
        }
        return true;
    }

    protected function executeUninstall()
    {
        zen_deregister_admin_pages('stats_monthly_sales');
        return true;
    }

    protected function purgeOldFiles(): bool
    {
        $filesToDelete = [
            DIR_FS_ADMIN . 'stats_monthly_sales.php',
            DIR_FS_ADMIN . 'includes/classes/MonthlySalesAndTax.php',
            DIR_FS_ADMIN . 'includes/functions/extra_functions/stats_monthly_sales.php',
            DIR_FS_ADMIN . 'includes/javascript/stats_monthly_sales.js',
            DIR_FS_ADMIN . 'includes/languages/english/extra_definitions/stats_monthly_sales.php',
            DIR_FS_ADMIN . 'includes/modules/sms/tpl_stats_monthly_sales_taxes.php',
            DIR_FS_CATALOG . 'includes/classes/ajax/zcAjaxMonthlySales.php',
        ];

        $errorOccurred = false;
        foreach ($filesToDelete as $key => $nextFile) {
            if (file_exists($nextFile)) {
                $result = unlink($nextFile);
                if (!$result && file_exists($nextFile)) {
                    $errorOccurred = true;
                    $this->errorContainer->addError(
                        0,
                        sprintf(ERROR_UNABLE_TO_DELETE_FILE, $nextFile),
                        false,
                        // this str_replace has to do DIR_FS_ADMIN before CATALOG because catalog is contained within admin, so results are wrong.
                        // also, '[admin_directory]' is used to obfuscate the admin dir name, in case the user copy/pastes output to a public forum for help.
                        sprintf(ERROR_UNABLE_TO_DELETE_FILE, str_replace([DIR_FS_ADMIN, DIR_FS_CATALOG], ['[admin_directory]/', ''], $nextFile))
                    );
                }
            }
        }
        return !$errorOccurred;
    }
}
