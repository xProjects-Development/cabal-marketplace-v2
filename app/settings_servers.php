<?php
/**
 * app/settings_servers.php (FIXED)
 * - Uses existing `settings` table
 * - Reads/writes `servers_json` (TEXT JSON)
 * - No assumptions about `id` being AUTO_INCREMENT
 * - Safe when the table is empty or when `id` is NOT NULL without default
 *
 * Public API:
 *   settings_servers(): array
 *   settings_update_servers(array|string $servers): bool
 */

// Try to get the app's DB handle.
if (!function_exists('db')) {
    @include_once __DIR__ . '/db.php';
}

function __ss_db() {
    if (function_exists('db')) { $h = @db(); if ($h instanceof mysqli) return $h; }
    if (isset($GLOBALS['mysqli']) && $GLOBALS['mysqli'] instanceof mysqli) return $GLOBALS['mysqli'];
    if (isset($GLOBALS['conn'])   && $GLOBALS['conn']   instanceof mysqli) return $GLOBALS['conn'];
    return null;
}

function __ss_defaults() { return array('EU','NA','SEA'); }

function __ss_has_col($db,$t,$c){
    $q = @$db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
    if(!$q) return false; $q->bind_param("ss",$t,$c); $q->execute(); $q->bind_result($n); $q->fetch(); $q->close(); return (bool)$n;
}

function __ss_table_has_rows($db){
    $c = 0; if ($r = @$db->query("SELECT COUNT(*) AS c FROM `settings`")) { $row = $r->fetch_assoc(); $c = (int)$row['c']; $r->close(); }
    return $c > 0;
}

function __ss_normalize_array($v){
    $out = array();
    if (is_array($v)) {
        foreach ($v as $x) { $x = trim((string)$x); if ($x !== '') $out[$x] = true; }
    } else {
        foreach (explode(',', (string)$v) as $x) { $x = trim($x); if ($x !== '') $out[$x] = true; }
    }
    $arr = array_keys($out);
    if (!$arr) $arr = __ss_defaults();
    return array_values($arr);
}

/**
 * Read servers as array
 */
if (!function_exists('settings_servers')) {
function settings_servers() {
    $db = __ss_db(); if (!$db) return __ss_defaults();

    // Ensure column exists gracefully
    if (!__ss_has_col($db,'settings','servers_json')) {
        @ $db->query("ALTER TABLE `settings` ADD COLUMN `servers_json` TEXT NULL");
    }

    $val = null;
    if ($res = @$db->query("SELECT `servers_json` AS v FROM `settings` WHERE `servers_json` IS NOT NULL AND `servers_json`<>'' ORDER BY id DESC LIMIT 1")) {
        $row = $res->fetch_assoc(); $res->close();
        $val = $row ? $row['v'] : null;
    }
    $arr = $val ? @json_decode($val, true) : null;
    if (!is_array($arr) || !$arr) $arr = __ss_defaults();
    return $arr;
}
}

/**
 * Save servers (CSV or array)
 */
if (!function_exists('settings_update_servers')) {
function settings_update_servers($servers) {
    $db = __ss_db(); if (!$db) return false;

    // Ensure column exists gracefully
    if (!__ss_has_col($db,'settings','servers_json')) {
        @ $db->query("ALTER TABLE `settings` ADD COLUMN `servers_json` TEXT NULL");
    }

    $arr = __ss_normalize_array($servers);
    $json = $db->real_escape_string(json_encode(array_values($arr)));

    // If the table already has rows, update them all.
    if (__ss_table_has_rows($db)) {
        @ $db->query("UPDATE `settings` SET `servers_json`='".$json."'");
        return true;
    }

    // Table is empty: insert a row. Some schemas have `id` NOT NULL (no default, not AI).
    $hasId = __ss_has_col($db, 'settings', 'id');
    if ($hasId) {
        // Insert with explicit id = 1. If a PK/unique exists, keep it consistent.
        @ $db->query("INSERT INTO `settings` (`id`, `servers_json`) VALUES (1, '".$json."')
                      ON DUPLICATE KEY UPDATE `servers_json`=VALUES(`servers_json`)");
    } else {
        @ $db->query("INSERT INTO `settings` (`servers_json`) VALUES ('".$json."')");
    }
    return true;
}
}
?>
