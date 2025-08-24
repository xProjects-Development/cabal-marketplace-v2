<?php
function conversation_ensure(int $buyer_id, int $seller_id, ?int $nft_id = null, ?int $offer_id = null): int {
    // Try to find existing conversation for this buyer/seller and nft
    $stmt = db()->prepare('SELECT id FROM conversations WHERE buyer_user_id=? AND seller_user_id=? AND ((nft_id IS NULL AND ? IS NULL) OR nft_id=?) LIMIT 1');
    $stmt->bind_param('iiii', $buyer_id, $seller_id, $nft_id, $nft_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($res) { return (int)$res['id']; }
    // Create new conversation
    $stmt = db()->prepare('INSERT INTO conversations (buyer_user_id, seller_user_id, nft_id, offer_id, last_message, last_message_at) VALUES (?,?,?,?,?, NOW())');
    $empty = '';
    $stmt->bind_param('iiiis', $buyer_id, $seller_id, $nft_id, $offer_id, $empty);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    return (int)$id;
}

function conversations_for_user(int $user_id): array {
    $sql = "SELECT c.*, 
      CASE WHEN c.buyer_user_id=? THEN c.seller_user_id ELSE c.buyer_user_id END AS other_user_id,
      u.username AS other_username, u.first_name AS other_first, u.last_name AS other_last,
      (SELECT COUNT(*) FROM messages m WHERE m.conversation_id=c.id AND m.sender_user_id<>? AND m.read_at IS NULL) AS unread_count
      FROM conversations c
      JOIN users u ON u.id = CASE WHEN c.buyer_user_id=? THEN c.seller_user_id ELSE c.buyer_user_id END
      WHERE c.buyer_user_id=? OR c.seller_user_id=?
      ORDER BY c.updated_at DESC, c.last_message_at DESC";
    $stmt = db()->prepare($sql);
    $stmt->bind_param('iiiii', $user_id, $user_id, $user_id, $user_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function messages_unread_count(int $user_id): int {
    $sql = "SELECT COUNT(*) AS c FROM messages m 
            JOIN conversations c ON c.id=m.conversation_id
            WHERE (c.buyer_user_id=? OR c.seller_user_id=?) AND m.sender_user_id<>? AND m.read_at IS NULL";
    $stmt = db()->prepare($sql);
    $stmt->bind_param('iii', $user_id, $user_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($res['c'] ?? 0);
}

function conversation_get(int $conversation_id, int $user_id): ?array {
    $stmt = db()->prepare('SELECT * FROM conversations WHERE id=? AND (buyer_user_id=? OR seller_user_id=?) LIMIT 1');
    $stmt->bind_param('iii', $conversation_id, $user_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res ?: null;
}

function messages_list(int $conversation_id, int $user_id, int $limit = 50, ?int $after_id = null): array {
    if (!conversation_get($conversation_id, $user_id)) return [];
    if ($after_id) {
        $stmt = db()->prepare('SELECT * FROM messages WHERE conversation_id=? AND id>? ORDER BY id ASC LIMIT ?');
        $stmt->bind_param('iii', $conversation_id, $after_id, $limit);
    } else {
        $stmt = db()->prepare('SELECT * FROM messages WHERE conversation_id=? ORDER BY id DESC LIMIT ?');
        $stmt->bind_param('ii', $conversation_id, $limit);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    if (!$after_id) { $rows = array_reverse($rows); }
    // Mark messages addressed to the user as read
    $stmt = db()->prepare('UPDATE messages SET read_at=NOW() WHERE conversation_id=? AND sender_user_id<>? AND read_at IS NULL');
    $stmt->bind_param('ii', $conversation_id, $user_id);
    $stmt->execute();
    $stmt->close();
    return $rows;
}

function message_send(int $conversation_id, int $sender_user_id, string $body): bool {
    $body = trim($body);
    if ($body === '') return false;
    $stmt = db()->prepare('INSERT INTO messages (conversation_id, sender_user_id, body) VALUES (?,?,?)');
    $stmt->bind_param('iis', $conversation_id, $sender_user_id, $body);
    $ok = $stmt->execute();
    $stmt->close();
    if ($ok) {
        $stmt = db()->prepare('UPDATE conversations SET last_message=?, last_message_at=NOW(), updated_at=NOW() WHERE id=?');
        $stmt->bind_param('si', $body, $conversation_id);
        $stmt->execute();
        $stmt->close();
    }
    return $ok;
}
?>
