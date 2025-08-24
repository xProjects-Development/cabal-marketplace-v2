<?php require_once __DIR__ . '/../app/bootstrap.php'; verify_csrf();
if (!current_user()) json_response(['ok'=>false,'error'=>'not_logged_in'], 401);
$payload = json_decode(file_get_contents('php://input'), true) ?: [];
$msg = trim($payload['message'] ?? '');
if (!$msg) json_response(['ok'=>false,'error'=>'empty'], 400);
$ok = shouts_add((int)current_user()['id'], $msg);
json_response(['ok'=>$ok]);
