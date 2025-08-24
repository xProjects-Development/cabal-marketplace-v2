<?php
function user_create(string $first, string $last, string $username, string $email, string $password): array {
    $stmt = db()->prepare('SELECT id FROM users WHERE email=? OR username=? LIMIT 1');
    $stmt->bind_param('ss', $email, $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) { $stmt->close(); return ['ok'=>false, 'error'=>'Email or username already exists']; }
    $stmt->close();
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $role = 'user';
    $stmt = db()->prepare('INSERT INTO users (first_name, last_name, username, email, password_hash, role) VALUES (?,?,?,?,?,?)');
    $stmt->bind_param('ssssss', $first, $last, $username, $email, $hash, $role);
    $ok = $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    return $ok ? ['ok'=>true, 'id'=>$id] : ['ok'=>false, 'error'=>'Failed to create user'];
}
function user_find_for_login(string $email_or_username): ?array {
    $stmt = db()->prepare('SELECT id, password_hash FROM users WHERE email=? OR username=? LIMIT 1');
    $stmt->bind_param('ss', $email_or_username, $email_or_username);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}
?>
