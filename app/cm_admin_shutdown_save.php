<?php
/**
 * app/cm_admin_shutdown_save.php
 * Add at the very top of admin.php (after bootstrap). Saves at shutdown so
 * it can't be overwritten by later code.
 */
require_once __DIR__ . '/cm_settings.php';
if (!defined('CM_ADMIN_SHUTDOWN_SAVE')) {
  define('CM_ADMIN_SHUTDOWN_SAVE', 1);
  $cm__cats_in = null; $cm__srv_in = null;
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['categories_csv'])) $cm__cats_in = (string)$_POST['categories_csv'];
    elseif (isset($_POST['categories'])) $cm__cats_in = (string)$_POST['categories'];
    if (isset($_POST['servers_csv'])) $cm__srv_in = (string)$_POST['servers_csv'];
    elseif (isset($_POST['servers'])) $cm__srv_in = (string)$_POST['servers'];
  }
  register_shutdown_function(function() use ($cm__cats_in,$cm__srv_in){
    if ($cm__cats_in===null && $cm__srv_in===null) return;
    $db = cm_db(); if (!$db) return;
    cm_settings_write($db,
      ($cm__cats_in!==null ? $cm__cats_in : cm_defaults_categories_csv()),
      ($cm__srv_in !==null ? $cm__srv_in  : implode(', ', cm_defaults_servers_array()))
    );
  });
}
?>
