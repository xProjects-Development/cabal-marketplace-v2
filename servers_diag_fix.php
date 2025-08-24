<?php
/**
 * servers_diag_fix.php
 * Standalone diagnostic + fixer for the Servers feature.
 * Upload to your web root and visit it in the browser: /servers_diag_fix.php
 * It will try to detect your DB connection, show schema, and add missing columns.
 *
 * Compatible with old PHP (no 7-only syntax).
 */

header('Content-Type: text/plain; charset=utf-8');

// 1) Try to include common project bootstrap files (non-fatal if missing)
$try_includes = array(
  __DIR__ . '/app/init.php',
  __DIR__ . '/app/bootstrap.php',
  __DIR__ . '/app/config.php',
  __DIR__ . '/config.php',
  __DIR__ . '/includes/config.php',
  __DIR__ . '/includes/db.php',
);
foreach ($try_includes as $inc) {
  if (is_file($inc)) {
    @include_once $inc;
  }
}

// 2) Try to detect a DB handle
$db = null; $kind = null;
$candidates = array('pdo','db','dbh','database','mysqli','conn','connection','link');
foreach ($candidates as $name) {
  if (isset($GLOBALS[$name]) && is_object($GLOBALS[$name])) {
    $h = $GLOBALS[$name];
    $isPdo = false;
    if (class_exists('PDO', false)) {
      $isPdo = ($h instanceof PDO);
    } else {
      $isPdo = method_exists($h, 'prepare') && method_exists($h, 'query');
    }
    if ($isPdo) { $db = $h; $kind = 'pdo'; break; }
    if ($h instanceof mysqli) { $db = $h; $kind = 'mysqli'; break; }
    $cls = strtolower(get_class($h));
    if (strpos($cls, 'mysqli') !== false) { $db = $h; $kind = 'mysqli'; break; }
  }
}

echo "== CABAL Marketplace | Servers Diag ==\n\n";

if (!$db) {
  echo "No DB connection detected.\n";
  echo "If your project uses a custom variable, edit this file and set \$db + \$kind manually near the top.\n";
  echo "Look for your init file (e.g., app/init.php) and set: \n";
  echo "  $db = <your PDO or MySQLi handle>;\n  $kind = 'pdo' or 'mysqli';\n\n";
  exit(0);
}

echo "Detected DB kind: {$kind}\n";

function col_exists($db, $kind, $table, $column) {
  if ($kind === 'pdo') {
    $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $stmt->execute(array($table, $column));
    return (bool)$stmt->fetchColumn();
  } else {
    $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    if (!$stmt) return false;
    $stmt->bind_param("ss", $table, $column);
    $stmt->execute(); $stmt->bind_result($c); $stmt->fetch(); $stmt->close();
    return (bool)$c;
  }
}

function run_sql($db, $kind, $sql) {
  if ($kind === 'pdo') {
    return $db->exec($sql);
  } else {
    return $db->query($sql);
  }
}

// 3) Check/ensure settings.servers_json
$has_settings = true;
try {
  if ($kind === 'pdo') {
    $db->query("SELECT 1 FROM `settings` LIMIT 1");
  } else {
    $db->query("SELECT 1 FROM `settings` LIMIT 1");
  }
} catch (Exception $e) { $has_settings = false; }

if (!$has_settings) {
  echo "Table `settings` not found. Your schema differs.\n";
  echo "Please tell me the actual settings table name.\n";
  exit(0);
}

$has_servers_json = col_exists($db, $kind, 'settings', 'servers_json');
echo "settings.servers_json exists: " . ($has_servers_json ? "yes" : "no") . "\n";
if (!$has_servers_json) {
  echo "Adding settings.servers_json ... ";
  @run_sql($db, $kind, "ALTER TABLE `settings` ADD COLUMN `servers_json` TEXT NULL");
  $has_servers_json = col_exists($db, $kind, 'settings', 'servers_json');
  echo ($has_servers_json ? "OK\n" : "FAILED\n");
}

// seed defaults
if ($has_servers_json) {
  echo "Seeding defaults if empty ... ";
  if ($kind === 'pdo') {
    $cnt = (int)$db->query("SELECT COUNT(*) FROM `settings`")->fetchColumn();
    if ($cnt === 0) {
      @run_sql($db, $kind, "INSERT INTO `settings` (`servers_json`) VALUES ('["EU","NA","SEA"]')");
    } else {
      @run_sql($db, $kind, "UPDATE `settings` SET `servers_json`='["EU","NA","SEA"]' WHERE `servers_json` IS NULL OR `servers_json`=''");
    }
  } else {
    $res = $db->query("SELECT COUNT(*) AS c FROM `settings`"); $row = $res ? $res->fetch_assoc() : array('c'=>0); $res && $res->close();
    if ((int)$row['c'] === 0) {
      @run_sql($db, $kind, "INSERT INTO `settings` (`servers_json`) VALUES ('["EU","NA","SEA"]')");
    } else {
      @run_sql($db, $kind, "UPDATE `settings` SET `servers_json`='["EU","NA","SEA"]' WHERE `servers_json` IS NULL OR `servers_json`=''");
    }
  }
  echo "done\n";
}

// 4) Check/ensure nfts.server
$has_nfts = true;
try {
  if ($kind === 'pdo') { $db->query("SELECT 1 FROM `nfts` LIMIT 1"); }
  else { $db->query("SELECT 1 FROM `nfts` LIMIT 1"); }
} catch (Exception $e) { $has_nfts = false; }

if (!$has_nfts) {
  echo "Table `nfts` not found. Your schema differs.\n";
  echo "Please tell me the actual nfts table name.\n";
  exit(0);
}

$has_server_col = col_exists($db, $kind, 'nfts', 'server');
echo "nfts.server exists: " . ($has_server_col ? "yes" : "no") . "\n";
if (!$has_server_col) {
  echo "Adding nfts.server ... ";
  @run_sql($db, $kind, "ALTER TABLE `nfts` ADD COLUMN `server` VARCHAR(64) NULL DEFAULT NULL");
  $has_server_col = col_exists($db, $kind, 'nfts', 'server');
  echo ($has_server_col ? "OK\n" : "FAILED\n");
}

if ($has_server_col) {
  echo "Ensuring index ... ";
  @run_sql($db, $kind, "CREATE INDEX `idx_nfts_server` ON `nfts` (`server`)");
  echo "done\n";
}

// 5) Show current servers list
echo "\nCurrent servers list: ";
$val = null;
if ($kind === 'pdo') {
  $val = $db->query("SELECT `servers_json` FROM `settings` LIMIT 1")->fetchColumn();
} else {
  $res = $db->query("SELECT `servers_json` AS v FROM `settings` LIMIT 1"); $row = $res ? $res->fetch_assoc() : null; $res && $res->close();
  $val = $row ? $row['v'] : null;
}
echo ($val ? $val : '(empty)') . "\n";

echo "\nDone. If your pages still 500, add at the very top of the failing page:\n";
echo "  ini_set('display_errors',1); ini_set('display_startup_errors',1); error_reporting(E_ALL);\n";
echo "and reload once to see the exact error.\n";
?>
