<?php
/**
 * app/settings_show.php
 * Debug page to confirm what values are used.
 */
require_once __DIR__ . '/settings_simple.php';
header('Content-Type: text/plain; charset=utf-8');
$db = ss_db();
echo "Servers:    " . implode(', ', ss_read_servers($db)) . "\n";
echo "Categories: " . implode(', ', ss_read_categories($db)) . "\n\n";
$r = $db->query("SELECT * FROM `settings`");
$i=0; while($row=$r->fetch_assoc()){ $i++; echo "Row #$i\n"; foreach($row as $k=>$v){ echo "  $k = $v\n"; } }
$r->close();
?>
