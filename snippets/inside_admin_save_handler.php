<?php
// Place this INSIDE your existing settings save handler (right before redirect header)
if (isset($_POST['servers_csv'])) {
    require_once __DIR__ . '/app/db.php';
    $db = db();
    $raw = explode(',', (string)$_POST['servers_csv']);
    $arr = array();
    foreach ($raw as $x) { $x = trim($x); if ($x !== '') $arr[] = $x; }
    if (!$arr) $arr = array('EU','NA','SEA');
    // ensure column
    $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'settings' AND COLUMN_NAME = 'servers_json'");
    if ($stmt) { $stmt->execute(); $stmt->bind_result($c); $stmt->fetch(); $stmt->close(); if(!$c){ @$db->query("ALTER TABLE `settings` ADD COLUMN `servers_json` TEXT NULL"); } }
    $v = $db->real_escape_string(json_encode($arr));
    @$db->query("UPDATE `settings` SET `servers_json` = '".$v."'");
    if ($db->affected_rows === 0) { @ $db->query("INSERT INTO `settings` (`servers_json`) VALUES ('".$v."')"); }
}
?>
