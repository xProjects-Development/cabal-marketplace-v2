<?php
/**
 * servers_bootstrap.php
 * Drop this file into /app and include it near the top of:
 *   - marketplace.php
 *   - create.php
 *   - admin.php
 *
 * Example:
 *   require_once __DIR__ . '/app/servers_bootstrap.php';
 *
 * What it does:
 *  - Detects PDO or MySQLi automatically
 *  - Ensures DB schema exists: settings.servers_json and nfts.server
 *  - Seeds default servers ["EU","NA","SEA"] if empty
 *  - Exposes helpers:
 *      cm_get_servers()                 -> array of servers
 *      cm_servers_select($name, $sel)   -> HTML <select> you can echo
 */

if (defined('CM_SERVERS_BOOTSTRAP')) { return; }
define('CM_SERVERS_BOOTSTRAP', 1);

function cm__db_kind() {
    // Try to detect PDO or MySQLi
    if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) return 'pdo';
    if (isset($GLOBALS['mysqli'])) return 'mysqli';
    if (isset($GLOBALS['conn'])) return 'mysqli';
    return null;
}
function cm__db() {
    $k = cm__db_kind();
    if ($k === 'pdo') return $GLOBALS['pdo'];
    if ($k === 'mysqli') return isset($GLOBALS['mysqli']) ? $GLOBALS['mysqli'] : $GLOBALS['conn'];
    return null;
}

function cm__column_exists($table, $column) {
    $kind = cm__db_kind(); $db = cm__db();
    if (!$db) return false;
    if ($kind === 'pdo') {
        $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $stmt->execute([$table, $column]);
        return (bool)$stmt->fetchColumn();
    } else {
        $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $stmt->bind_param("ss", $table, $column);
        $stmt->execute(); $stmt->bind_result($c); $stmt->fetch(); $stmt->close();
        return (bool)$c;
    }
}

function cm__ensure_schema() {
    $kind = cm__db_kind(); $db = cm__db();
    if (!$db) { return; }

    // settings.servers_json
    if (!cm__column_exists('settings', 'servers_json')) {
        if ($kind === 'pdo') {
            $db->exec("ALTER TABLE `settings` ADD COLUMN `servers_json` TEXT NULL");
        } else {
            $db->query("ALTER TABLE `settings` ADD COLUMN `servers_json` TEXT NULL");
        }
    }

    // seed defaults if empty
    try {
        if ($kind === 'pdo') {
            $exists = $db->query("SELECT COUNT(*) FROM `settings`")->fetchColumn();
            if ((int)$exists === 0) {
                $db->exec("INSERT INTO `settings` (`servers_json`) VALUES ('["EU","NA","SEA"]')");
            } else {
                $db->exec("UPDATE `settings` SET `servers_json`='["EU","NA","SEA"]' WHERE `servers_json` IS NULL OR `servers_json`=''");
            }
        } else {
            $res = $db->query("SELECT COUNT(*) AS c FROM `settings`");
            $row = $res ? $res->fetch_assoc() : ['c'=>0];
            $res and $res->close();
            if ((int)$row['c'] === 0) {
                $db->query("INSERT INTO `settings` (`servers_json`) VALUES ('["EU","NA","SEA"]')");
            } else {
                $db->query("UPDATE `settings` SET `servers_json`='["EU","NA","SEA"]' WHERE `servers_json` IS NULL OR `servers_json`=''");
            }
        }
    } catch (Throwable $e) {
        // do nothing; a 500 here would defeat the patch's purpose
    }

    // nfts.server
    if (!cm__column_exists('nfts', 'server')) {
        if ($kind === 'pdo') {
            $db->exec("ALTER TABLE `nfts` ADD COLUMN `server` VARCHAR(64) NULL DEFAULT NULL");
            try { $db->exec("CREATE INDEX `idx_nfts_server` ON `nfts` (`server`)"); } catch (Throwable $e) {}
        } else {
            $db->query("ALTER TABLE `nfts` ADD COLUMN `server` VARCHAR(64) NULL DEFAULT NULL");
            @$db->query("CREATE INDEX `idx_nfts_server` ON `nfts` (`server`)");
        }
    }
}

function cm_get_servers() {
    cm__ensure_schema();
    $kind = cm__db_kind(); $db = cm__db();
    if (!$db) return ["EU","NA","SEA"];
    if ($kind === 'pdo') {
        $val = $db->query("SELECT `servers_json` FROM `settings` LIMIT 1")->fetchColumn();
    } else {
        $res = $db->query("SELECT `servers_json` AS v FROM `settings` LIMIT 1");
        $row = $res ? $res->fetch_assoc() : null;
        $res and $res->close();
        $val = $row ? $row['v'] : null;
    }
    if (!$val) return ["EU","NA","SEA"];
    $arr = json_decode($val, true);
    if (!is_array($arr) || !$arr) $arr = ["EU","NA","SEA"];
    return $arr;
}

function cm_servers_select($name, $selected = '', $placeholder = null, $class = 'form-control') {
    $servers = cm_get_servers();
    if ($placeholder === null) $placeholder = $name === 'server' ? 'All Servers' : '(choose server)';
    $h = '<select name="'.htmlspecialchars($name).'" class="'.htmlspecialchars($class).'">';
    $h .= '<option value="">'.htmlspecialchars($placeholder).'</option>';
    foreach ($servers as $s) {
        $sel = ($selected === $s) ? ' selected' : '';
        $h .= '<option value="'.htmlspecialchars($s).'"'.$sel.'>'.htmlspecialchars($s).'</option>';
    }
    $h .= '</select>';
    return $h;
}
?>
