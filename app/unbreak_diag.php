<?php
// app/unbreak_diag.php
require_once __DIR__ . '/site_unbreak_hotfix.php';
require_once __DIR__ . '/force_errors.php';
header('Content-Type: text/plain; charset=utf-8');

echo "servers from settings_servers():\n";
echo "  - ".implode("\n  - ", settings_servers())."\n\n";

if (function_exists('db')) {
  $db = @db();
  if ($db instanceof mysqli) {
    echo "DB OK. Checking settings.servers_json...\n";
    $r=@$db->query("SHOW COLUMNS FROM `settings` LIKE 'servers_json'");
    if ($r && $r->num_rows) {
      $r->close();
      $q=@$db->query("SELECT `servers_json` FROM `settings`");
      $i=0; while($row=$q->fetch_assoc()){ $i++; echo "Row #$i servers_json = ".$row['servers_json']."\n"; } $q && $q->close();
    } else { echo "Column servers_json DOES NOT EXIST.\n"; }
  } else { echo "db() returned no mysqli handle.\n"; }
} else { echo "No db() function found.\n"; }
?>
