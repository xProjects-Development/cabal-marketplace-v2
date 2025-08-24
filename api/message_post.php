<?php
require_once __DIR__ . '/../app/bootstrap.php';
verify_csrf();
if (!current_user()) json_response(['ok'=>false,'error'=>'not_logged_in'], 401);
$payload = json_decode(file_get_contents('php://input'), true) ?: [];
$cid = (int)($payload['conversation_id'] ?? 0);
$body = trim((string)($payload['body'] ?? ''));
if (!$cid || $body==='') json_response(['ok'=>false,'error'=>'bad_request'], 400);

// Ensure participant and compute other user
$db = db();
$uid = (int)current_user()['id'];
$ok = false; $other_id = 0;
if ($db->query("SHOW TABLES LIKE 'conversations'")->num_rows) {
  $stmt = $db->prepare("SELECT buyer_user_id, seller_user_id FROM conversations WHERE id=? LIMIT 1");
  $stmt->bind_param('i', $cid); $stmt->execute(); $res=$stmt->get_result(); $c=$res->fetch_assoc(); $stmt->close();
  if ($c) { $ok = ($uid===(int)$c['buyer_user_id'] || $uid===(int)$c['seller_user_id']); $other_id = ($uid===(int)$c['buyer_user_id'])?(int)$c['seller_user_id']:(int)$c['buyer_user_id']; }
} else {
  $ok = true; // fallback assume ok
}
if (!$ok) json_response(['ok'=>false,'error'=>'forbidden'], 403);

// Send message using existing helper if present
if (function_exists('message_send')) {
  $ok = message_send($cid, $uid, $body);
} else {
  // fall back: insert directly
  $has_is_read = $db->query("SHOW COLUMNS FROM messages LIKE 'is_read'")->num_rows ? true : false;
  $col = $has_is_read ? ', is_read' : '';
  $val = $has_is_read ? ', 0' : '';
  $stmt = $db->prepare("INSERT INTO messages (conversation_id, sender_user_id, recipient_user_id, body{$col}) VALUES (?,?,?,?{$val})");
  $stmt->bind_param('iiis', $cid, $uid, $other_id, $body); $ok = $stmt->execute(); $stmt->close();
}
// Touch conversation updated_at if table exists
if ($ok && $db->query("SHOW TABLES LIKE 'conversations'")->num_rows) {
  $stmt = $db->prepare("UPDATE conversations SET updated_at=NOW() WHERE id=?"); $stmt->bind_param('i', $cid); @$stmt->execute(); $stmt->close();
}
json_response(['ok'=>(bool)$ok]);
