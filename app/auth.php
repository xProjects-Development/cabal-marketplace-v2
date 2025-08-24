<?php
function current_user(): ?array {
    if (!isset($_SESSION['user_id'])) return null;
    $id = (int)$_SESSION['user_id'];
    $stmt = db()->prepare('SELECT id, first_name, last_name, username, email, role, status FROM users WHERE id=? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    $stmt->close();
    return $user ?: null;
}
function login_user(int $id) { $_SESSION['user_id'] = $id; }
function logout_user() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}
function is_admin(): bool {
    $u = current_user();
    return $u && $u['role'] === 'admin' && $u['status'] === 'active';
}
?>
