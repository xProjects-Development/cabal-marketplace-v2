<?php
/**
 * app/cm_admin_block.php
 * Drop this inside the Platform Settings <form>.
 */
require_once __DIR__ . '/cm_settings.php';
$db = cm_db();
$cats_csv = implode(', ', $db ? cm_settings_read_categories($db) : array());
$srvs_csv = implode(', ', $db ? cm_settings_read_servers($db)   : array());
?>
<div class="form-group" style="margin-top:14px;">
  <label for="categories_csv"><strong>Categories (comma-separated)</strong></label>
  <input id="categories_csv" name="categories_csv" class="form-control" value="<?= htmlspecialchars($cats_csv, ENT_QUOTES, 'UTF-8') ?>">
</div>
<div class="form-group" style="margin-top:14px;">
  <label for="servers_csv"><strong>Servers (comma-separated)</strong></label>
  <input id="servers_csv" name="servers_csv" class="form-control" value="<?= htmlspecialchars($srvs_csv, ENT_QUOTES, 'UTF-8') ?>">
</div>
