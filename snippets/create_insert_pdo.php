<?php
// --- CREATE INSERT (PDO) ---
// Assumes you already validated/sanitized your inputs and set $userId, $name, $desc, $price, $category.
$server = isset($_POST['server']) && $_POST['server'] !== '' ? $_POST['server'] : null;

// Ensure columns exist (no-op after first run)
cm_get_servers();

$sql = "INSERT INTO nfts (user_id, name, description, price, category, server, created_at)
        VALUES (:uid, :name, :desc, :price, :cat, :server, NOW())";
$stmt = $pdo->prepare($sql);
$stmt->execute([
  ':uid'    => $userId,
  ':name'   => $name,
  ':desc'   => $desc,
  ':price'  => $price,
  ':cat'    => $category,
  ':server' => $server,
]);
?>
