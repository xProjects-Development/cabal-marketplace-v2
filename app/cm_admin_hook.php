<?php
/**
 * app/cm_admin_hook.php
 * Include near the TOP of admin.php (after bootstrap, before redirects).
 * Saves Categories + Servers from POST (names: categories_csv/categories, servers_csv/servers).
 */
require_once __DIR__ . '/cm_settings.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = cm_db();
    if ($db) {
        $cats_in = isset($_POST['categories_csv']) ? $_POST['categories_csv'] :
                   (isset($_POST['categories']) ? $_POST['categories'] : '');
        $srv_in  = isset($_POST['servers_csv'])    ? $_POST['servers_csv'] :
                   (isset($_POST['servers'])      ? $_POST['servers']      : '');
        cm_settings_write($db, $cats_in, $srv_in);
    }
}
?>
