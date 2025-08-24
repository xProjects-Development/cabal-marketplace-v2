<?php
/**
 * app/pages_servers_helpers.php
 * Use in create.php and marketplace.php for live server values.
 */
require_once __DIR__ . '/settings_simple.php';
function pages_servers(){ $db = ss_db(); return $db ? ss_read_servers($db) : ss_defaults_servers(); }
?>
