<?php
/**
 * app/admin_settings_fields.php
 * Include INSIDE the Platform Settings <form> (same form as Save button).
 * Renders Categories and Servers inputs populated from DB.
 */
require_once __DIR__ . '/settings_simple.php';
$db = ss_db();
$cats_csv = $db ? implode(', ', ss_read_categories($db)) : ss_defaults_categories_csv();
$srvs_csv = $db ? implode(', ', ss_read_servers($db))    : implode(', ', ss_defaults_servers());
?>
<div class="form-group" style="margin-top:14px;">
  <label for="categories_csv"><strong>Categories (comma-separated)</strong></label>
  <input id="categories_csv" name="categories_csv" class="form-control" value="<?= htmlspecialchars($cats_csv, ENT_QUOTES, 'UTF-8') ?>">
</div>
<div class="form-group" style="margin-top:14px;">
  <label for="servers_csv"><strong>Servers (comma-separated)</strong></label>
  <input id="servers_csv" name="servers_csv" class="form-control" value="<?= htmlspecialchars($srvs_csv, ENT_QUOTES, 'UTF-8') ?>">
</div>
