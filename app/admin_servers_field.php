<?php
/**
 * app/admin_servers_field.php
 * Drop inside admin.php Settings form. Saves and renders servers list.
 */
require_once __DIR__ . '/settings_servers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['servers_csv'])) {
    $raw = explode(',', (string)$_POST['servers_csv']);
    $arr = array();
    foreach ($raw as $x) { $x = trim($x); if ($x !== '') $arr[] = $x; }
    if (!$arr) $arr = settings_servers_defaults();
    settings_servers_write($arr);
}

$csv = implode(', ', settings_servers_read());
?>
<div class="form-group" style="margin-top:14px;">
  <label for="servers_csv"><strong>Servers (comma-separated)</strong></label>
  <input id="servers_csv" name="servers_csv" class="form-control" value="<?= htmlspecialchars($csv, ENT_QUOTES, 'UTF-8') ?>">
  <small class="form-text text-muted">Example: EU, NA, SEA</small>
</div>
