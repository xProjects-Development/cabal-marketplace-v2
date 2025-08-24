<?php
require_once __DIR__ . '/../app/bootstrap.php';
if (!current_user()) json_response(['ok'=>false,'error'=>'not_logged_in'], 401);
$uid = (int)current_user()['id'];
$cid = (int)($_GET['id'] ?? 0);
$after = (int)($_GET['after'] ?? 0);
if (!$cid) json_response(['ok'=>false,'error'=>'missing_conversation'], 400);

$db = db();
$has_is_read = $db->query("SHOW COLUMNS FROM messages LIKE 'is_read'")->num_rows ? true : false;

// Auth: ensure user is participant
$ok = false;
if ($db->query("SHOW TABLES LIKE 'conversations'")->num_rows) {
  $stmt = $db->prepare("SELECT buyer_user_id, seller_user_id FROM conversations WHERE id=? LIMIT 1");
  $stmt->bind_param('i', $cid); $stmt->execute(); $res=$stmt->get_result(); $c=$res->fetch_assoc(); $stmt->close();
  if ($c) $ok = ($uid===(int)$c['buyer_user_id'] || $uid===(int)$c['seller_user_id']);
} else {
  $stmt = $db->prepare("SELECT 1 FROM messages WHERE conversation_id=? AND (sender_user_id=? OR recipient_user_id=?) LIMIT 1");
  $stmt->bind_param('iii', $cid, $uid, $uid); $stmt->execute(); $res=$stmt->get_result(); $ok=(bool)$res->num_rows; $stmt->close();
}
if (!$ok) json_response(['ok'=>false,'error'=>'forbidden'], 403);

// Fetch new messages
if ($after>0){
  $stmt = $db->prepare("SELECT m.id, m.body, m.created_at, m.sender_user_id FROM messages m WHERE m.conversation_id=? AND m.id>? ORDER BY m.id ASC");
  $stmt->bind_param('ii', $cid, $after);
} else {
  $stmt = $db->prepare("SELECT m.id, m.body, m.created_at, m.sender_user_id FROM messages m WHERE m.conversation_id=? ORDER BY m.id ASC LIMIT 200");
  $stmt->bind_param('i', $cid);
}
$stmt->execute(); $res = $stmt->get_result(); $items = []; while ($row=$res->fetch_assoc()) { $items[] = $row; } $stmt->close();

// Mark as read if column exists
if ($has_is_read) {
  $stmt = $db->prepare("UPDATE messages SET is_read=1 WHERE conversation_id=? AND recipient_user_id=? AND is_read=0");
  $stmt->bind_param('ii', $cid, $uid); @$stmt->execute(); $stmt->close();
}

json_response(['ok'=>true,'items'=>$items]);
