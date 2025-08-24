<?php
// app/settings_servers_debug.php
require_once __DIR__ . '/settings_servers.php';
header('Content-Type: text/plain; charset=utf-8');

$servers = settings_servers();
echo "settings_servers():\n  - ".implode("\n  - ", $servers)."\n\n";

if (function_exists('db')) {
  $db = @db();
  if ($db instanceof mysqli) {
    $c = $db->query("SHOW COLUMNS FROM `settings` LIKE 'servers_json'");
    echo ($c && $c->num_rows) ? "servers_json column: YES\n" : "servers_json column: NO\n";
    $c && $c->close();
    $r = $db->query("SELECT id, servers_json FROM `settings` ORDER BY id ASC");
    while($row=$r->fetch_assoc()){
      echo "row id=".$row['id']." servers_json=".$row['servers_json']."\n";
    }
    $r->close();
  } else {
    echo "db() did not return mysqli handle.\n";
  }
} else {
  echo "No db() function in scope.\n";
}
?>
