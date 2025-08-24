<?php
// --- MARKETPLACE LIST (MySQLi) ---
$db = isset($mysqli) ? $mysqli : $conn;
$where = []; $bind = []; $types = "";

if (!empty($_GET['category'])) { $where[] = "n.category = ?"; $bind[] = $_GET['category']; $types .= "s"; }
if (!empty($_GET['server']))   { $where[] = "n.server = ?";   $bind[] = $_GET['server'];   $types .= "s"; }

$sql = "SELECT n.* FROM nfts n";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY n.created_at DESC";

$stmt = $db->prepare($sql);
if ($bind) { $stmt->bind_param($types, *$bind); } // If your PHP version doesn't support splat with bind_param, expand manually.
$stmt->execute();
$res = $stmt->get_result();
$nfts = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
