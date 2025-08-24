<?php
/**
 * app/cm_settings.php
 * Helpers for saving/reading Categories & Servers.
 * - Uses app/db.php -> db(): mysqli
 * - Ensures columns: settings.categories (TEXT), settings.servers_json (TEXT)
 * - Reads newest non-empty row; writes to ALL rows for consistency
 * - PHP 5 compatible
 */
require_once __DIR__ . '/db.php';

function cm_db() {
    if (function_exists('db')) { $h = @db(); if ($h instanceof mysqli) return $h; }
    if (isset($GLOBALS['mysqli']) && $GLOBALS['mysqli'] instanceof mysqli) return $GLOBALS['mysqli'];
    if (isset($GLOBALS['conn'])   && $GLOBALS['conn']   instanceof mysqli) return $GLOBALS['conn'];
    return null;
}

function cm_has_col($db, $table, $column) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    if (!$stmt) return false;
    $stmt->bind_param("ss", $table, $column);
    $stmt->execute(); $stmt->bind_result($c); $stmt->fetch(); $stmt->close();
    return (bool)$c;
}
function cm_order_by_id($db) { return cm_has_col($db,'settings','id') ? " ORDER BY id DESC " : ""; }
function cm_ensure_schema($db) {
    if (!cm_has_col($db,'settings','categories'))   { @$db->query("ALTER TABLE `settings` ADD COLUMN `categories` TEXT NULL"); }
    if (!cm_has_col($db,'settings','servers_json')) { @$db->query("ALTER TABLE `settings` ADD COLUMN `servers_json` TEXT NULL"); }
}

function cm_defaults_categories_csv() {
    return 'Equipment, Services, Items, Characters, Accessories, Pet Accessories, Runes, Alz, Others';
}
function cm_defaults_servers_array() { return array('EU','NA','SEA'); }

function cm_norm_csv_to_array($csv, $fallback_csv) {
    $out = array();
    foreach (explode(',', (string)$csv) as $x) { $x = trim($x); if ($x !== '') $out[] = $x; }
    if (!$out) { foreach (explode(',', (string)$fallback_csv) as $x) { $x = trim($x); if ($x !== '') $out[] = $x; } }
    return $out;
}

function cm_settings_read_categories($db) {
    cm_ensure_schema($db);
    $order = cm_order_by_id($db);
    $val = null;
    $res = $db->query("SELECT `categories` AS v FROM `settings` WHERE `categories` IS NOT NULL AND `categories`<>''".$order." LIMIT 1");
    $row = $res ? $res->fetch_assoc() : null; $res && $res->close(); $val = $row ? $row['v'] : null;
    if (!$val) { $res = $db->query("SELECT `categories` AS v FROM `settings` ".$order." LIMIT 1"); $row = $res ? $res->fetch_assoc() : null; $res && $res->close(); $val = $row ? $row['v'] : null; }
    return cm_norm_csv_to_array($val, cm_defaults_categories_csv());
}
function cm_settings_read_servers($db) {
    cm_ensure_schema($db);
    $order = cm_order_by_id($db);
    $val = null;
    $res = $db->query("SELECT `servers_json` AS v FROM `settings` WHERE `servers_json` IS NOT NULL AND `servers_json`<>''".$order." LIMIT 1");
    $row = $res ? $res->fetch_assoc() : null; $res && $res->close(); $val = $row ? $row['v'] : null;
    if (!$val) { $res = $db->query("SELECT `servers_json` AS v FROM `settings` ".$order." LIMIT 1"); $row = $res ? $res->fetch_assoc() : null; $res && $res->close(); $val = $row ? $row['v'] : null; }
    $arr = $val ? @json_decode($val, true) : null;
    if (!is_array($arr) || !$arr) $arr = cm_defaults_servers_array();
    return $arr;
}
function cm_settings_write($db, $categories_csv_or_array, $servers_array_or_csv) {
    cm_ensure_schema($db);
    if (is_array($categories_csv_or_array)) {
        $tmp = array(); foreach ($categories_csv_or_array as $x){ $x=trim($x); if($x!=='') $tmp[]=$x; }
        $cats_csv = $tmp ? implode(', ',$tmp) : cm_defaults_categories_csv();
    } else {
        $cats_csv = implode(', ', cm_norm_csv_to_array($categories_csv_or_array, cm_defaults_categories_csv()));
    }
    if (!is_array($servers_array_or_csv)) {
        $servers_array_or_csv = cm_norm_csv_to_array($servers_array_or_csv, implode(', ', cm_defaults_servers_array()));
    }
    $cats_csv_esc = $db->real_escape_string($cats_csv);
    $srv_json = $db->real_escape_string(json_encode(array_values($servers_array_or_csv)));
    @$db->query("UPDATE `settings` SET `categories`='".$cats_csv_esc."', `servers_json`='".$srv_json."'");
    if ($db->affected_rows === 0) { @ $db->query("INSERT INTO `settings` (`categories`,`servers_json`) VALUES ('".$cats_csv_esc."','".$srv_json."')"); }
}
function cm_servers_select($name, $selected, $placeholder, $class) {
    if ($placeholder === null) $placeholder = 'All Servers';
    if ($class === null) $class = 'form-control';
    $db = cm_db(); $servers = $db ? cm_settings_read_servers($db) : cm_defaults_servers_array();
    $name_esc = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $class_esc = htmlspecialchars($class, ENT_QUOTES, 'UTF-8');
    $h = '<select name="'.$name_esc.'" class="'.$class_esc.'">';
    $h .= '<option value="">'.htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8').'</option>';
    foreach ($servers as $s) { $sel = ($selected === $s) ? ' selected' : ''; $s2 = htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); $h .= '<option value="'.$s2.'"'.$sel.'>'.$s2.'</option>'; }
    $h .= '</select>'; return $h;
}
?>
