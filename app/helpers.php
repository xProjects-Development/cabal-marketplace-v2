<?php
function redirect(string $path) {
    $base = BASE_URL;
    if ($base && str_starts_with($path, '/')) { $path = $base . $path; }
    header('Location: ' . $path);
    exit;
}
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function json_response($data, int $code = 200) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($code);
    echo json_encode($data);
    exit;
}
function is_post(): bool { return $_SERVER['REQUEST_METHOD'] === 'POST'; }
function upload_image(array $file): ?string {
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return null;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['png','jpg','jpeg','gif','webp','svg'];
    if (!in_array($ext, $allowed, true)) return null;
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0775, true);
    $basename = bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = UPLOAD_DIR . $basename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) return null;
    return UPLOAD_URL . $basename;
}
?>
