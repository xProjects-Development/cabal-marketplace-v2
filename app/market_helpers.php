<?php
/**
 * app/market_helpers.php
 * Helper read functions for Create/Marketplace pages.
 */
require_once __DIR__ . '/settings_categories.php';
require_once __DIR__ . '/servers_bootstrap_v3.php';

function market_categories() { return settings_categories_read_list(); }
function market_servers()    { return cm_get_servers(); }
?>
