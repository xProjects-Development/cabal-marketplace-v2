<?php
/**
 * servers_bootstrap_v2.php
 * Safer version:
 *  - No PHP 7-only features (no Throwable typehints, no arrow functions)
 *  - Detects many possible DB variable names
 *  - Allows explicit set via cm_servers_set_db($handle, 'pdo'|'mysqli')
 *  - Never fatals if DB can't be found; it simply skips schema, and cm_get_servers() returns defaults
 */

if (defined('CM_SERVERS_BOOTSTRAP_V2')) return;
define('CM_SERVERS_BOOTSTRAP_V2', 1);

// --- Optional debugging (uncomment while diagnosing 500s) ---
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

$GLOBALS['__cm_servers_kind'] = null;
$GLOBALS['__cm_servers_db']   = null;

function cm_servers_set_db($handle, $kind /* 'pdo' or 'mysqli' */) {
    $GLOBALS['__cm_servers_db']   = $handle;
    $GLOBALS['__cm_servers_kind'] = $kind;
}

function cm__detect_db_handle() {
    if ($GLOBALS['__cm_servers_db']) return array($GLOBALS['__cm_servers_db'], $GLOBALS['__cm_servers_kind']);

    $candidates = array('pdo','db','dbh','database','mysqli','conn','connection','link');
    foreach ($candidates as $name) {
        if (isset($GLOBALS[$name]) && is_object($GLOBALS[$name])) {
            $h = $GLOBALS[$name];
            // Detect PDO
            $isPdo = false;
            if (class_exists('PDO', false)) {
                $isPdo = ($h instanceof PDO);
            } else {
                // Fallback: PDO often has method 'prepare' returning PDOStatement with execute()
                $isPdo = method_exists($h, 'prepare') && method_exists($h, 'query');
            }
            if ($isPdo) { return array($h, 'pdo'); }

            // Detect MySQLi
            if ($h instanceof mysqli) { return array($h, 'mysqli'); }
            $cls = strtolower(get_class($h));
            if (strpos($cls, 'mysqli') !== false) { return array($h, 'mysqli'); }
        }
    }
    return array(null, null);
}

function cm__column_exists($db, $kind, $table, $column) {
    if (!$db) return false;
    if ($kind === 'pdo') {
        $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $stmt->execute(array($table, $column));
        return (bool)$stmt->fetchColumn();
    } else if ($kind === 'mysqli') {
        $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        if (!$stmt) return false;
        $stmt->bind_param("ss", $table, $column);
        $stmt->execute();
        $stmt->bind_result($c);
        $stmt->fetch();
        $stmt->close();
        return (bool)$c;
    }
    return false;
}

function cm__ensure_schema() {
    list($db, $kind) = cm__detect_db_handle();
    if (!$db) return; // can't alter without DB

    // settings.servers_json
    if (!cm__column_exists($db, $kind, 'settings', 'servers_json')) {
        if ($kind === 'pdo') {
            @$db->exec("ALTER TABLE `settings` ADD COLUMN `servers_json` TEXT NULL");
        } else {
            @$db->query("ALTER TABLE `settings` ADD COLUMN `servers_json` TEXT NULL");
        }
    }

    // Seed defaults if empty
    try {
        if ($kind === 'pdo') {
            $row = $db->query("SELECT COUNT(*) FROM `settings`")->fetchColumn();
            if ((int)$row === 0) {
                @$db->exec("INSERT INTO `settings` (`servers_json`) VALUES ('["EU","NA","SEA"]')");
            } else {
                @$db->exec("UPDATE `settings` SET `servers_json`='["EU","NA","SEA"]' WHERE `servers_json` IS NULL OR `servers_json`=''");
            }
        } else if ($kind === 'mysqli') {
            $res = $db->query("SELECT COUNT(*) AS c FROM `settings`");
            $row = $res ? $res->fetch_assoc() : array('c'=>0);
            $res && $res->close();
            if ((int)$row['c'] === 0) {
                @$db->query("INSERT INTO `settings` (`servers_json`) VALUES ('["EU","NA","SEA"]')");
            } else {
                @$db->query("UPDATE `settings` SET `servers_json`='["EU","NA","SEA"]' WHERE `servers_json` IS NULL OR `servers_json`=''");
            }
        }
    } catch (Exception $e) {
        // ignore
    }

    // nfts.server
    if (!cm__column_exists($db, $kind, 'nfts', 'server')) {
        if ($kind === 'pdo') {
            @$db->exec("ALTER TABLE `nfts` ADD COLUMN `server` VARCHAR(64) NULL DEFAULT NULL");
            try { @$db->exec("CREATE INDEX `idx_nfts_server` ON `nfts` (`server`)"); } catch (Exception $e) {}
        } else if ($kind === 'mysqli') {
            @$db->query("ALTER TABLE `nfts` ADD COLUMN `server` VARCHAR(64) NULL DEFAULT NULL");
            @$db->query("CREATE INDEX `idx_nfts_server` ON `nfts` (`server`)");
        }
    }
}

function cm_get_servers() {
    cm__ensure_schema();
    list($db, $kind) = cm__detect_db_handle();
    if (!$db) return array("EU","NA","SEA");
    $val = null;
    if ($kind === 'pdo') {
        $q = $db->query("SELECT `servers_json` FROM `settings` LIMIT 1");
        if ($q) $val = $q->fetchColumn();
    } else if ($kind === 'mysqli') {
        $res = $db->query("SELECT `servers_json` AS v FROM `settings` LIMIT 1");
        $row = $res ? $res->fetch_assoc() : null;
        $res && $res->close();
        $val = $row ? $row['v'] : null;
    }
    if (!$val) return array("EU","NA","SEA");
    $arr = json_decode($val, true);
    if (!is_array($arr) || !$arr) $arr = array("EU","NA","SEA");
    return $arr;
}

function cm_servers_select($name, $selected, $placeholder, $class) {
    if ($placeholder === null) $placeholder = 'All Servers';
    if ($class === null) $class = 'form-control';
    $servers = cm_get_servers();
    $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $sel  = htmlspecialchars((string)$selected, ENT_QUOTES, 'UTF-8');
    $h = '<select name="'.$name.'" class="'.htmlspecialchars($class, ENT_QUOTES, 'UTF-8').'">';
    $h .= '<option value="">'.htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8').'</option>';
    foreach ($servers as $s) {
        $s2 = htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
        $s3 = ($selected === $s) ? ' selected' : '';
        $h .= '<option value="'.$s2.'"'.$s3.'>'.$s2.'</option>';
    }
    $h .= '</select>';
    return $h;
}
?>
