<?php
/**
 * app/cm_market_helpers.php
 * Include in create.php and marketplace.php to read live values.
 */
require_once __DIR__ . '/cm_settings.php';
function cm_market_categories(){ $db = cm_db(); return $db ? cm_settings_read_categories($db) : array(); }
function cm_market_servers(){    $db = cm_db(); return $db ? cm_settings_read_servers($db)   : array('EU','NA','SEA'); }
?>
