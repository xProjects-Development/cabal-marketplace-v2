<?php
// --- MARKETPLACE LIST (PDO) ---
$params = [];
$where  = [];

if (!empty($_GET['category'])) { $where[] = "n.category = :cat";  $params[':cat']  = $_GET['category']; }
if (!empty($_GET['server']))   { $where[] = "n.server   = :srv";  $params[':srv']  = $_GET['server']; }

$sql = "SELECT n.* FROM nfts n";
if ($where) { $sql .= " WHERE " . implode(" AND ", $where); }
$sql .= " ORDER BY n.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$nfts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
