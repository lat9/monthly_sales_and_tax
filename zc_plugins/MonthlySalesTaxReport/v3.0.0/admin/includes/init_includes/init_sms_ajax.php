<?php
// -----
// Admin-level initialization script for the Monthly Sales and Tax Report plugin for Zen Cart, by lat9.
// Copyright (C) 2025, Vinos de Frutas Tropicales.
//
// Last updated: v3.0.0
//
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

global $PHP_SELF;

// -----
// Now loaded prior to language-loading to identify the current page
// as 'edit_orders' for SMS's AJAX processing, so that the associated language
// constants will be pulled in for EO during its AJAX processing.
//
if ($PHP_SELF === 'ajax.php' && ($_GET['act'] ?? '') === 'ajaxMonthlySales') {
    $PHP_SELF = FILENAME_STATS_MONTHLY_SALES . '.php';
    return;
}
