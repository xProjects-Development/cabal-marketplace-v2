<?php
// app/nft_servers.php — add Server field support for create/list
// Uses mysqli via db(). Compatible with older PHP 7.x style.

if (!function_exists('nft_ensure_server_column')) {
    function nft_ensure_server_column(): void {
        // add column if missing
        $sql = "SELECT 1 FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='nfts' AND COLUMN_NAME='server'";
        $res = @db()->query($sql);
        if (!$res || !$res->fetch_row()) {
            @db()->query("ALTER TABLE `nfts` ADD COLUMN `server` VARCHAR(64) NULL DEFAULT NULL");
            // best-effort index (older MySQL doesn't support IF NOT EXISTS; ignore errors)
            @db()->query("CREATE INDEX idx_nfts_server ON `nfts` (`server`)");
        }
        if ($res) { $res->close(); }
    }
}

if (!function_exists('nft_create_with_server')) {
    function nft_create_with_server(int $user_id, string $title, string $description, float $price_alz, string $category, string $server, string $image_url): bool {
        nft_ensure_server_column();
        $status = 'approved';
        // empty -> NULL for bind convenience
        $server = trim($server) === '' ? null : $server;
        if ($server === null) {
            $stmt = db()->prepare('INSERT INTO nfts (title, description, price_alz, category, server, image_path, creator_user_id, status)
                                   VALUES (?,?,?,?,NULL,?,?,?)');
            $stmt->bind_param('ssdssds', $title, $description, $price_alz, $category, $image_url, $user_id, $status);
        } else {
            $stmt = db()->prepare('INSERT INTO nfts (title, description, price_alz, category, server, image_path, creator_user_id, status)
                                   VALUES (?,?,?,?,?,?,?,?)');
            $stmt->bind_param('ssdsssds', $title, $description, $price_alz, $category, $server, $image_url, $user_id, $status);
        }
        $ok = $stmt->execute(); $stmt->close();
        return (bool)$ok;
    }
}

if (!function_exists('nfts_list_with_server')) {
    function nfts_list_with_server(?string $category, ?string $server, ?string $price_range, ?string $sort): array {
        nft_ensure_server_column();
        $sql = "SELECT n.*, u.username AS creator_username
                FROM nfts n
                JOIN users u ON u.id = n.creator_user_id
                WHERE n.status IN ('approved','featured')";
        $params = []; $types = '';

        if ($category) { $sql .= " AND n.category=?"; $types .= 's'; $params[] = $category; }
        if ($server)   { $sql .= " AND (n.server=? OR (n.server IS NULL AND ?=''))"; $types .= 'ss'; $params[] = $server; $params[] = $server; }

        if ($price_range) {
            if ($price_range === '0-100')       { $sql .= " AND n.price_alz BETWEEN 0 AND 100"; }
            elseif ($price_range === '100-500') { $sql .= " AND n.price_alz BETWEEN 100 AND 500"; }
            elseif ($price_range === '500-1000'){ $sql .= " AND n.price_alz BETWEEN 500 AND 1000"; }
            elseif ($price_range === '1000+')   { $sql .= " AND n.price_alz >= 1000"; }
        }

        if     ($sort === 'oldest')     $sql .= " ORDER BY n.created_at ASC";
        elseif ($sort === 'price-low')  $sql .= " ORDER BY n.price_alz ASC";
        elseif ($sort === 'price-high') $sql .= " ORDER BY n.price_alz DESC";
        elseif ($sort === 'popular')    $sql .= " ORDER BY (n.status='featured') DESC, n.created_at DESC";
        else                            $sql .= " ORDER BY n.created_at DESC";

        $stmt = db()->prepare($sql . " LIMIT 100");
        if ($types) { $stmt->bind_param($types, ...$params); }
        $stmt->execute(); $res = $stmt->get_result();
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $rows;
    }
}
?>