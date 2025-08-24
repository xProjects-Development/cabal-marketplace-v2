<?php
// --- CREATE INSERT (MySQLi) ---
// Assumes you already validated/sanitized inputs and set $userId, $name, $desc, $price, $category.
$server = isset($_POST['server']) && $_POST['server'] !== '' ? $_POST['server'] : null;
$db = isset($mysqli) ? $mysqli : $conn;

// Ensure columns exist (no-op after first run)
cm_get_servers();

$stmt = $db->prepare("INSERT INTO nfts (user_id, name, description, price, category, server, created_at) VALUES (?,?,?,?,?,?,NOW())");
$stmt->bind_param("issdss", $userId, $name, $desc, $price, $category, $server);
$stmt->execute();
$stmt->close();
?>
