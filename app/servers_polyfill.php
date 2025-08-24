<?php
/**
 * app/servers_polyfill.php
 * Non-destructive polyfill. Safe to include multiple places.
 * Defines:
 *   - settings_servers(): array
 *   - settings_update_servers(array|string): bool
 * ONLY if they do not already exist.
 * Uses the existing `settings` table, column `servers_json` (TEXT JSON).
 * Falls back to ['EU','NA','SEA'].
 */

if (!function_exists('settings_servers') || !function_exists('settings_update_servers')) {
    // Try to get a DB handle from your app
    if (!function_exists('db')) {
        // If your project exposes $mysqli or $conn, we will use that
    }
    function __sp_db() {
        if (function_exists('db')) { $h = @db(); if ($h instanceof mysqli) return $h; }
        if (isset($GLOBALS['mysqli']) && $GLOBALS['mysqli'] instanceof mysqli) return $GLOBALS['mysqli'];
        if (isset($GLOBALS['conn'])   && $GLOBALS['conn']   instanceof mysqli) return $GLOBALS['conn'];
        return null;
    }
    function __sp_has_col($db,$t,$c){
        $q=$db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        if(!$q) return false; $q->bind_param("ss",$t,$c); $q->execute(); $q->bind_result($n); $q->fetch(); $q->close(); return (bool)$n;
    }
    function __sp_ensure($db){
        if (!__sp_has_col($db,'settings','servers_json')) { @ $db->query("ALTER TABLE `settings` ADD COLUMN `servers_json` TEXT NULL"); }
    }
    function __sp_defaults(){ return array('EU','NA','SEA'); }

    if (!function_exists('settings_servers')) {
        function settings_servers() {
            $db = __sp_db(); if (!$db) return __sp_defaults();
            __sp_ensure($db);
            $val = null;
            if ($r=$db->query("SELECT `servers_json` AS v FROM `settings` WHERE `servers_json` IS NOT NULL AND `servers_json`<>'' ORDER BY id DESC LIMIT 1")) {
                $row = $r->fetch_assoc(); $r->close();
                $val = $row ? $row['v'] : null;
            }
            $arr = $val ? @json_decode($val, true) : null;
            if (!is_array($arr) || !$arr) $arr = __sp_defaults();
            return $arr;
        }
    }

    if (!function_exists('settings_update_servers')) {
        function settings_update_servers($servers) {
            $db = __sp_db(); if (!$db) return false;
            __sp_ensure($db);
            if (!is_array($servers)) {
                $tmp = array(); foreach (explode(',', (string)$servers) as $x) { $x = trim($x); if ($x!=='') $tmp[] = $x; } $servers = $tmp;
            } else {
                $tmp = array(); foreach ($servers as $x) { $x = trim((string)$x); if ($x!=='') $tmp[] = $x; } $servers = $tmp;
            }
            if (!$servers) $servers = __sp_defaults();
            $json = $db->real_escape_string(json_encode(array_values($servers)));

            // Write to all rows; if table empty, insert one
            @ $db->query("UPDATE `settings` SET `servers_json`='".$json."'");
            if ($db->affected_rows === 0) { @ $db->query("INSERT INTO `settings` (`servers_json`) VALUES ('".$json."')"); }
            return true;
        }
    }
}
?>
