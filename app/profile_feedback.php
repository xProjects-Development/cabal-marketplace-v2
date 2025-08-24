<?php
function profile_feedback_stats(int $profile_user_id): array {
    $stmt = db()->prepare('SELECT COUNT(*) AS cnt, AVG(rating) AS avg_rating FROM profile_feedback WHERE profile_user_id=?');
    $stmt->bind_param('i', $profile_user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return ['count'=>(int)($res['cnt'] ?? 0), 'avg'=>round((float)($res['avg_rating'] ?? 0), 2)];
}

function profile_feedback_list(int $profile_user_id, int $limit = 30, ?int $after_id = null): array {
    if ($after_id) {
        $stmt = db()->prepare('SELECT pf.*, u.username, u.first_name, u.last_name FROM profile_feedback pf JOIN users u ON u.id=pf.rater_user_id WHERE pf.profile_user_id=? AND pf.id > ? ORDER BY pf.id ASC LIMIT ?');
        $stmt->bind_param('iii', $profile_user_id, $after_id, $limit);
    } else {
        $stmt = db()->prepare('SELECT pf.*, u.username, u.first_name, u.last_name FROM profile_feedback pf JOIN users u ON u.id=pf.rater_user_id WHERE pf.profile_user_id=? ORDER BY pf.id DESC LIMIT ?');
        $stmt->bind_param('ii', $profile_user_id, $limit);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    if (!$after_id) { $rows = array_reverse($rows); }
    return $rows;
}

function profile_feedback_add(int $profile_user_id, int $rater_user_id, int $rating, string $comment): array {
    if ($profile_user_id === $rater_user_id) { return ['ok'=>false, 'error'=>'cannot_rate_self']; }
    $rating = max(1, min(5, $rating));
    $comment = trim($comment);
    if (mb_strlen($comment) > 1000) { $comment = mb_substr($comment, 0, 1000); }
    // Ensure target user exists and is active
    $stmt = db()->prepare('SELECT id FROM users WHERE id=? LIMIT 1');
    $stmt->bind_param('i', $profile_user_id);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$exists) { return ['ok'=>false, 'error'=>'profile_not_found']; }

    // Insert or update on duplicate rating from the same rater
    $stmt = db()->prepare('INSERT INTO profile_feedback (profile_user_id, rater_user_id, rating, comment) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE rating=VALUES(rating), comment=VALUES(comment), updated_at=CURRENT_TIMESTAMP');
    $stmt->bind_param('iiis', $profile_user_id, $rater_user_id, $rating, $comment);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok ? ['ok'=>true] : ['ok'=>false, 'error'=>'db_error'];
}
?>
