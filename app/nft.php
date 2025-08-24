<?php
function nfts_list(?string $category, ?string $price_range, ?string $sort): array {
    $sql = "SELECT n.*, u.username AS creator_username FROM nfts n JOIN users u ON u.id=n.creator_user_id WHERE n.status IN ('approved','featured')";
    $params = []; $types = '';
    if ($category) { $sql .= " AND n.category=?"; $types .= 's'; $params[] = $category; }
    if ($price_range) {
        if ($price_range === '0-100') { $sql .= " AND n.price_alz BETWEEN 0 AND 100"; }
        elseif ($price_range === '100-500') { $sql .= " AND n.price_alz BETWEEN 100 AND 500"; }
        elseif ($price_range === '500-1000') { $sql .= " AND n.price_alz BETWEEN 500 AND 1000"; }
        elseif ($price_range == '1000+') { $sql .= " AND n.price_alz >= 1000"; }
    }
    if ($sort === 'oldest') $sql .= " ORDER BY n.created_at ASC";
    elseif ($sort === 'price-low') $sql .= " ORDER BY n.price_alz ASC";
    elseif ($sort === 'price-high') $sql .= " ORDER BY n.price_alz DESC";
    elseif ($sort === 'popular') $sql .= " ORDER BY n.status='featured' DESC, n.created_at DESC";
    else $sql .= " ORDER BY n.created_at DESC";
    $stmt = db()->prepare($sql . " LIMIT 100");
    if ($types) { $stmt->bind_param($types, ...$params); }
    $stmt->execute(); $res = $stmt->get_result(); $rows = $res->fetch_all(MYSQLI_ASSOC); $stmt->close(); return $rows;
}
function nft_create(int $user_id, string $title, string $description, float $price_alz, string $category, string $image_url): bool {
    $status = 'approved';
    $stmt = db()->prepare('INSERT INTO nfts (title, description, price_alz, category, image_path, creator_user_id, status) VALUES (?,?,?,?,?,?,?)');
    $stmt->bind_param('ssdssds', $title, $description, $price_alz, $category, $image_url, $user_id, $status);
    $ok = $stmt->execute(); $stmt->close(); return $ok;
}
function nfts_by_user(int $user_id): array {
    $stmt = db()->prepare('SELECT * FROM nfts WHERE creator_user_id=? ORDER BY created_at DESC LIMIT 100');
    $stmt->bind_param('i', $user_id);
    $stmt->execute(); $res = $stmt->get_result(); $rows = $res->fetch_all(MYSQLI_ASSOC); $stmt->close(); return $rows;
}
function admin_counts(): array {
    $db = db(); $counts = [];
    foreach (['users','nfts','shout_messages'] as $t) { $res = $db->query("SELECT COUNT(*) AS c FROM $t"); $counts[$t] = (int)$res->fetch_assoc()['c']; }
    return $counts;
}
function admin_users(): array {
    $res = db()->query("SELECT id, first_name, last_name, username, email, role, status, created_at FROM users ORDER BY created_at DESC LIMIT 50");
    return $res->fetch_all(MYSQLI_ASSOC);
}
function admin_nfts(): array {
    $res = db()->query("SELECT n.*, u.username AS creator_username FROM nfts n JOIN users u ON u.id=n.creator_user_id ORDER BY n.created_at DESC LIMIT 50");
    return $res->fetch_all(MYSQLI_ASSOC);
}
function admin_suspend_user(int $id, bool $suspend): bool {
    $status = $suspend ? 'suspended' : 'active';
    $stmt = db()->prepare('UPDATE users SET status=? WHERE id=? LIMIT 1');
    $stmt->bind_param('si', $status, $id); $ok = $stmt->execute(); $stmt->close(); return $ok;
}
function admin_delete_nft(int $id): bool {
    $stmt = db()->prepare('DELETE FROM nfts WHERE id=? LIMIT 1'); $stmt->bind_param('i', $id); $ok = $stmt->execute(); $stmt->close(); return $ok;
}
?>
