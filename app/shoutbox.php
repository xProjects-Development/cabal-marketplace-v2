<?php
function shouts_fetch(int $limit = 50, ?int $after_id = null): array {
    if ($after_id) {
        $stmt = db()->prepare('SELECT s.id, s.message, s.created_at, u.username, u.first_name, u.last_name FROM shout_messages s JOIN users u ON u.id=s.user_id WHERE s.id > ? ORDER BY s.id ASC LIMIT ?');
        $stmt->bind_param('ii', $after_id, $limit);
    } else {
        $stmt = db()->prepare('SELECT s.id, s.message, s.created_at, u.username, u.first_name, u.last_name FROM shout_messages s JOIN users u ON u.id=s.user_id ORDER BY s.id DESC LIMIT ?');
        $stmt->bind_param('i', $limit);
    }
    $stmt->execute(); $res = $stmt->get_result(); $rows = $res->fetch_all(MYSQLI_ASSOC); $stmt->close();
    if (!$after_id) { $rows = array_reverse($rows); }
    return $rows;
}
function shouts_add(int $user_id, string $message): bool {
    $message = trim($message);
    if ($message === '' || mb_strlen($message) > 500) return false;
    $stmt = db()->prepare('INSERT INTO shout_messages (user_id, message) VALUES (?,?)');
    $stmt->bind_param('is', $user_id, $message); $ok = $stmt->execute(); $stmt->close(); return $ok;
}
?>
