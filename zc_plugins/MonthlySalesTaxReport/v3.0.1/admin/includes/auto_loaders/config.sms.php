<?php
// -----
// Admin-level auto-loader for the Monthly Sales and Tax Report plugin for Zen Cart, provided by lat9 and others.
//
// Last updated: v3.0.0
// 
if (!defined ('IS_ADMIN_FLAG')) { 
    die ('Illegal Access'); 
}

// -----
// Load point 63 is after the session's initialization [60] but before the
// languages are loaded [65].
//
// That gives SMS's configuration script a chance to identify the current page
// as 'monthly_sales_tax' for SMS's AJAX processing, so that the associated language
// constants will be pulled in for SMS during that AJAX processing.
//
$autoLoadConfig[63][] = [
    'autoType' => 'init_script',
    'loadFile' => 'init_sms_ajax.php'
];
