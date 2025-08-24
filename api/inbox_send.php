<?php require_once __DIR__ . '/../app/bootstrap.php'; require_login(); verify_csrf();
$u = current_user(); $payload = json_decode(file_get_contents('php://input'), true) ?: [];
$cid = (int)($payload['conversation_id'] ?? 0); $body = trim($payload['body'] ?? '');
if (!$cid || !$body) json_response(['ok'=>false,'error'=>'missing'], 400);
if (!conversation_get($cid, (int)$u['id'])) json_response(['ok'=>false,'error'=>'forbidden'], 403);
$ok = message_send($cid, (int)$u['id'], $body);
json_response(['ok'=>$ok]);
