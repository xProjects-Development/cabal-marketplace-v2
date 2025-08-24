<?php
/**
 * app/settings_categories.php
 * Robust read/write for Platform "Categories (comma-separated)".
 * Stores in settings.categories (TEXT). Creates the column if missing.
 * Uses your app/db.php -> db(): mysqli
 */

require_once __DIR__ . '/db.php';

function settings_categories_defaults_csv() { return 'Equipment, Services, Items, Characters, Accessories, Pet Accessories, Runes, Alz, Others'; }

function settings__has_col($db, $table, $column) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    if (!$stmt) return false;
    $stmt->bind_param("ss", $table, $column);
    $stmt->execute(); $stmt->bind_result($c); $stmt->fetch(); $stmt->close();
    return (bool)$c;
}

function settings_categories_read_list() {
    $db = db();
    if (!$db) return array_map('trim', explode(',', settings_categories_defaults_csv()));

    if (!settings__has_col($db, 'settings', 'categories')) {
        // create empty column so UI can save later
        @$db->query("ALTER TABLE `settings` ADD COLUMN `categories` TEXT NULL");
    }

    $orderBy = settings__has_col($db, 'settings', 'id') ? " ORDER BY id DESC " : "";
    $val = null;
    $res = $db->query("SELECT `categories` AS v FROM `settings` WHERE `categories` IS NOT NULL AND `categories` <> ''" . $orderBy . " LIMIT 1");
    $row = $res ? $res->fetch_assoc() : null; $res && $res->close();
    $val = $row ? $row['v'] : null;
    if (!$val) {
        $res = $db->query("SELECT `categories` AS v FROM `settings` " . $orderBy . " LIMIT 1");
        $row = $res ? $res->fetch_assoc() : null; $res && $res->close();
        $val = $row ? $row['v'] : null;
    }
    if (!$val) $val = settings_categories_defaults_csv();
    $arr = array();
    foreach (explode(',', $val) as $x) { $x = trim($x); if ($x !== '') $arr[] = $x; }
    if (!$arr) $arr = array_map('trim', explode(',', settings_categories_defaults_csv()));
    return $arr;
}

function settings_categories_write_list($arr) {
    $db = db();
    if (!$db) return false;

    if (!settings__has_col($db, 'settings', 'categories')) {
        @$db->query("ALTER TABLE `settings` ADD COLUMN `categories` TEXT NULL");
    }

    // Normalize and write to ALL rows for consistency
    $clean = array(); foreach ($arr as $x) { $x = trim($x); if ($x!=='') $clean[] = $x; }
    if (!$clean) $clean = array_map('trim', explode(',', settings_categories_defaults_csv()));
    $csv = implode(', ', $clean);
    $csv_esc = $db->real_escape_string($csv);
    @$db->query("UPDATE `settings` SET `categories` = '".$csv_esc."'");
    if ($db->affected_rows === 0) {
        @ $db->query("INSERT INTO `settings` (`categories`) VALUES ('".$csv_esc."')");
    }
    return true;
}
?>
