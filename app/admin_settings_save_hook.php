<?php
/**
 * app/admin_settings_save_hook.php
 * Include near the TOP of admin.php (after bootstrap, before redirects).
 * Saves servers/categories from POST into the `settings` table.
 */
require_once __DIR__ . '/settings_simple.php';

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $cats_in = isset($_POST['categories_csv']) ? $_POST['categories_csv'] : (isset($_POST['categories'])?$_POST['categories']:null);
  $srv_in  = isset($_POST['servers_csv'])    ? $_POST['servers_csv']    : (isset($_POST['servers'])   ?$_POST['servers']   :null);
  if ($cats_in!==null || $srv_in!==null) {
    $db = ss_db(); if ($db) ss_write_settings($db, ($cats_in!==null?$cats_in:''), ($srv_in!==null?$srv_in:''));
  }
}
?>
