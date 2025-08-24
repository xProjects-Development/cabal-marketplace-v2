<?php
// app/servers_diag.php  (temporary)
// Visit this to see what's stored & what the polyfill returns.
require_once __DIR__ . '/servers_polyfill.php';
header('Content-Type: text/plain; charset=utf-8');

$servers = settings_servers();
echo "settings_servers(): ".implode(', ', $servers)."\n\n";

// Show raw DB rows if possible
if (function_exists('db')) {
  $db = @db();
  if ($db instanceof mysqli) {
    $r = $db->query("SHOW COLUMNS FROM `settings` LIKE 'servers_json'");
    if ($r && $r->num_rows) {
      $r->close();
      $q = $db->query("SELECT `servers_json` FROM `settings`");
      $i=0; while($row=$q->fetch_assoc()){ $i++; echo "Row #$i servers_json = ".$row['servers_json']."\n"; }
      $q && $q->close();
    } else { echo "Column servers_json missing.\n"; }
  } else {
    echo "No mysqli db() handle.\n";
  }
} else {
  echo "No db() function available.\n";
}
?>
