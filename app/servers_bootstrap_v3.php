<?php
/**
 * app/servers_bootstrap_v3.php  (READ FIX)
 * Read `servers_json` from the newest non-empty row, falling back safely.
 */

if (defined('CM_SERVERS_BOOTSTRAP_V3')) return;
define('CM_SERVERS_BOOTSTRAP_V3', 1);

function cm__servers_defaults() { return array('EU','NA','SEA'); }

function cm__get_db() {
    if (function_exists('db')) { $h = @db(); if ($h instanceof mysqli) return array($h, 'mysqli'); }
    if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli) return array($GLOBALS['conn'], 'mysqli');
    if (isset($GLOBALS['mysqli']) && $GLOBALS['mysqli'] instanceof mysqli) return array($GLOBALS['mysqli'], 'mysqli');
    if (isset($GLOBALS['pdo'])) return array($GLOBALS['pdo'], 'pdo');
    return array(null, null);
}

function cm__has_col($db, $kind, $table, $column) {
    if ($kind === 'mysqli') {
        $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        if (!$stmt) return false;
        $stmt->bind_param("ss",$table,$column); $stmt->execute(); $stmt->bind_result($c); $stmt->fetch(); $stmt->close();
        return (bool)$c;
    } else if ($kind === 'pdo') {
        $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $stmt->execute(array($table,$column)); return (bool)$stmt->fetchColumn();
    }
    return false;
}

function cm__column_exists($db, $kind, $table, $column) { return cm__has_col($db,$kind,$table,$column); }
function cm__ensure_servers_schema() {
    list($db, $kind) = cm__get_db();
    if (!$db) return;
    if (!cm__column_exists($db,$kind,'settings','servers_json')) {
        if ($kind==='pdo') { @$db->exec("ALTER TABLE `settings` ADD COLUMN `servers_json` TEXT NULL"); }
        else { @$db->query("ALTER TABLE `settings` ADD COLUMN `servers_json` TEXT NULL"); }
    }
    if (!cm__column_exists($db,$kind,'nfts','server')) {
        if ($kind==='pdo') { @$db->exec("ALTER TABLE `nfts` ADD COLUMN `server` VARCHAR(64) NULL DEFAULT NULL"); @ $db->exec("CREATE INDEX `idx_nfts_server` ON `nfts` (`server`)"); }
        else { @$db->query("ALTER TABLE `nfts` ADD COLUMN `server` VARCHAR(64) NULL DEFAULT NULL"); @ $db->query("CREATE INDEX `idx_nfts_server` ON `nfts` (`server`)"); }
    }
}

function cm_get_servers() {
    cm__ensure_servers_schema();
    list($db, $kind) = cm__get_db();
    if (!$db) return cm__servers_defaults();

    $orderBy = cm__has_col($db,$kind,'settings','id') ? " ORDER BY id DESC " : "";
    $val = null;

    if ($kind === 'pdo') {
        $q = $db->query("SELECT `servers_json` FROM `settings` WHERE `servers_json` IS NOT NULL AND `servers_json`<>''". $orderBy ." LIMIT 1");
        $val = $q ? $q->fetchColumn() : null;
        if (!$val) {
            $q = $db->query("SELECT `servers_json` FROM `settings` ". $orderBy ." LIMIT 1");
            $val = $q ? $q->fetchColumn() : null;
        }
    } else {
        $res = $db->query("SELECT `servers_json` AS v FROM `settings` WHERE `servers_json` IS NOT NULL AND `servers_json`<>''". $orderBy ." LIMIT 1");
        $row = $res ? $res->fetch_assoc() : null; $res && $res->close();
        $val = $row ? $row['v'] : null;
        if (!$val) {
            $res = $db->query("SELECT `servers_json` AS v FROM `settings` ". $orderBy ." LIMIT 1");
            $row = $res ? $res->fetch_assoc() : null; $res && $res->close();
            $val = $row ? $row['v'] : null;
        }
    }

    if (!$val) return cm__servers_defaults();
    $arr = @json_decode($val, true);
    if (!is_array($arr) || !$arr) return cm__servers_defaults();
    return $arr;
}

function cm_servers_select($name, $selected, $placeholder, $class) {
    if ($placeholder === null) $placeholder = 'All Servers';
    if ($class === null) $class = 'form-control';
    $servers = cm_get_servers();
    $name_esc = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $class_esc = htmlspecialchars($class, ENT_QUOTES, 'UTF-8');
    $h = '<select name="'.$name_esc.'" class="'.$class_esc.'">';
    $h .= '<option value="">'.htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8').'</option>';
    foreach ($servers as $s) {
        $sel = ($selected === $s) ? ' selected' : '';
        $s2  = htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
        $h  .= '<option value="'.$s2.'"'.$sel.'>'.$s2.'</option>';
    }
    $h .= '</select>';
    return $h;
}
?>
