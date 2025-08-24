<?php
/**
 * app/admin_settings_block.php
 * Drop inside the Settings form in admin.php.
 * Handles POST for both Categories and Servers and renders the inputs.
 */
require_once __DIR__ . '/settings_categories.php';
require_once __DIR__ . '/settings_servers.php';

/* SAVE */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['categories_csv'])) {
        $raw = explode(',', (string)$_POST['categories_csv']);
        $arr = array();
        foreach ($raw as $x) { $x = trim($x); if ($x !== '') $arr[] = $x; }
        settings_categories_write_list($arr);
    }
    if (isset($_POST['servers_csv'])) {
        $raw = explode(',', (string)$_POST['servers_csv']);
        $arr = array();
        foreach ($raw as $x) { $x = trim($x); if ($x !== '') $arr[] = $x; }
        if (!$arr) $arr = settings_servers_defaults();
        settings_servers_write($arr);
    }
}

/* RENDER */
$cats_csv = implode(', ', settings_categories_read_list());
$servers_csv = implode(', ', settings_servers_read());
?>
<div class="form-group" style="margin-top:14px;">
  <label for="categories_csv"><strong>Categories (comma-separated)</strong></label>
  <input id="categories_csv" name="categories_csv" class="form-control" value="<?= htmlspecialchars($cats_csv, ENT_QUOTES, 'UTF-8') ?>">
  <small class="form-text text-muted">Example: Equipment, Services, Items</small>
</div>

<div class="form-group" style="margin-top:14px;">
  <label for="servers_csv"><strong>Servers (comma-separated)</strong></label>
  <input id="servers_csv" name="servers_csv" class="form-control" value="<?= htmlspecialchars($servers_csv, ENT_QUOTES, 'UTF-8') ?>">
  <small class="form-text text-muted">Example: EU, NA, SEA</small>
</div>
