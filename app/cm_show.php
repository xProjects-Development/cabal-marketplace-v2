<?php
/**
 * app/cm_show.php
 * Debug page to confirm values used by the site.
 */
require_once __DIR__ . '/cm_settings.php';
header('Content-Type: text/plain; charset=utf-8');
$db = cm_db();
echo "Using categories : " . implode(', ', cm_settings_read_categories($db)) . "\n";
echo "Using servers    : " . implode(', ', cm_settings_read_servers($db)) . "\n\n";
$r = $db->query("SELECT * FROM `settings`");
$ix = 0; while ($row = $r->fetch_assoc()) { $ix++; echo "Row #$ix\n"; foreach ($row as $k=>$v) echo "  $k = $v\n"; }
$r->close();
?>
