<?php require_once __DIR__ . '/../app/bootstrap.php'; verify_csrf();
if (!current_user()) json_response(['ok'=>false,'error'=>'not_logged_in'], 401);
$payload = json_decode(file_get_contents('php://input'), true) ?: [];
$uid = (int)($payload['user_id'] ?? 0);
$rating = (int)($payload['rating'] ?? 0);
$comment = trim($payload['comment'] ?? '');
$res = profile_feedback_add($uid, (int)current_user()['id'], $rating, $comment);
if (!$res['ok']) { json_response($res, 400); }
json_response(['ok'=>true]);
