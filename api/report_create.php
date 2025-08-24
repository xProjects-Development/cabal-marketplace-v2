<?php
require_once __DIR__ . '/../app/bootstrap.php';
verify_csrf();
if (!current_user()) json_response(['ok'=>false,'error'=>'not_logged_in'], 401);

$payload = $_POST;
if (empty($payload)) { $payload = json_decode(file_get_contents('php://input'), true) ?: []; }

$type = strtolower(trim($payload['type'] ?? ''));
$id   = (int)($payload['id'] ?? 0);
$reason = trim((string)($payload['reason'] ?? ''));

$allowed = ['nft','user','message','offer'];
if (!in_array($type, $allowed, true) || !$id) {
  json_response(['ok'=>false,'error'=>'bad_request'], 400);
}
if ($reason === '' || mb_strlen($reason) < 5) {
  json_response(['ok'=>false,'error'=>'reason_too_short'], 400);
}
if (mb_strlen($reason) > 255) $reason = mb_substr($reason, 0, 255);

$uid = (int)current_user()['id'];
$stmt = db()->prepare('INSERT INTO reports (target_type, target_id, reporter_user_id, reason) VALUES (?,?,?,?)');
$stmt->bind_param('siis', $type, $id, $uid, $reason);
$ok = $stmt->execute();
$rid = (int)$stmt->insert_id;
$stmt->close();

if (!$ok) json_response(['ok'=>false,'error'=>'db_error'], 500);
json_response(['ok'=>true,'report_id'=>$rid]);
