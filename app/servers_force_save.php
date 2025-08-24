<?php
/**
 * TEMPORARY TOOL — DELETE AFTER USE
 * app/servers_force_save.php?s=EU,BR,SEA
 * Writes the list to settings.servers_json (all rows).
 */
require_once __DIR__ . '/db.php';
header('Content-Type: text/plain; charset=utf-8');

if (!isset($_GET['s'])) { echo "Usage: servers_force_save.php?s=EU,BR,SEA\n"; exit; }

$raw = explode(',', (string)$_GET['s']);
$arr = array();
foreach ($raw as $x) { $x = trim($x); if ($x !== '') $arr[] = $x; }
if (!$arr) $arr = array('EU','NA','SEA');

$db = db();
if (!$db) { echo "No DB handle.\n"; exit; }

// ensure column
$stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'settings' AND COLUMN_NAME = 'servers_json'");
if ($stmt) { $stmt->execute(); $stmt->bind_result($c); $stmt->fetch(); $stmt->close(); if(!$c){ @$db->query("ALTER TABLE `settings` ADD COLUMN `servers_json` TEXT NULL"); } }

$v = $db->real_escape_string(json_encode($arr));
@$db->query("UPDATE `settings` SET `servers_json` = '".$v."'");
if ($db->affected_rows === 0) { @ $db->query("INSERT INTO `settings` (`servers_json`) VALUES ('".$v."')"); }

echo "Saved: ".json_encode($arr)."\n";
$res = $db->query("SELECT `servers_json` FROM `settings`");
while ($row = $res->fetch_assoc()) { echo "Row: ".$row['servers_json']."\n"; }
$res->close();
?>
