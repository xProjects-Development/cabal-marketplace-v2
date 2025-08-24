<?php
require_once __DIR__ . '/../app/bootstrap.php';
verify_csrf();
if (!current_user()) json_response(['ok'=>false,'error'=>'not_logged_in'], 401);
$me = current_user();
$my_id = (int)$me['id'];
$payload = json_decode(file_get_contents('php://input'), true) ?: [];
$username = isset($payload['username']) ? trim($payload['username']) : null;
$to_id = isset($payload['to_user_id']) ? (int)$payload['to_user_id'] : 0;
$nft_id = isset($payload['nft_id']) ? (int)$payload['nft_id'] : 0;

if ($username && !$to_id) {
  $stmt = db()->prepare("SELECT id FROM users WHERE username=? LIMIT 1");
  $stmt->bind_param('s', $username); $stmt->execute(); $res=$stmt->get_result();
  $row = $res->fetch_assoc(); $stmt->close();
  if ($row) $to_id = (int)$row['id'];
}

if (!$to_id || $to_id === $my_id) json_response(['ok'=>false,'error'=>'bad_target'], 400);

function conversations_table_exists(){
  return db()->query("SHOW TABLES LIKE 'conversations'")->num_rows ? true : false;
}

function ensure_conversation_api($a, $b, $nft_id = 0){
  $db = db();
  $u1 = min((int)$a, (int)$b);
  $u2 = max((int)$a, (int)$b);
  if (conversations_table_exists()) {
    if ($nft_id > 0) {
      $stmt = $db->prepare("SELECT id FROM conversations WHERE buyer_user_id=? AND seller_user_id=? AND nft_id=? LIMIT 1");
      $stmt->bind_param('iii', $u1, $u2, $nft_id);
      $stmt->execute(); $res=$stmt->get_result(); $row=$res->fetch_assoc(); $stmt->close();
      if ($row) return (int)$row['id'];
    }
    $stmt = $db->prepare("SELECT id FROM conversations WHERE buyer_user_id=? AND seller_user_id=? ORDER BY updated_at DESC LIMIT 1");
    $stmt->bind_param('ii', $u1, $u2);
    $stmt->execute(); $res=$stmt->get_result(); $row=$res->fetch_assoc(); $stmt->close();
    if ($row) return (int)$row['id'];
    $stmt = $db->prepare("INSERT INTO conversations (nft_id, buyer_user_id, seller_user_id, created_at, updated_at) VALUES (?,?,?,NOW(),NOW())");
    $stmt->bind_param('iii', $nft_id, $u1, $u2); $stmt->execute(); $cid = (int)$stmt->insert_id; $stmt->close();
    return $cid;
  } else {
    $has_messages = $db->query("SHOW TABLES LIKE 'messages'")->num_rows ? true : false;
    if ($has_messages) {
      $r = $db->query("SELECT COALESCE(MAX(conversation_id),0) AS m FROM messages"); $max = 0;
      if ($r && $r->num_rows) { $max = (int)($r->fetch_assoc()['m'] ?? 0); }
      return $max + 1;
    } else {
      return (int)(time()) * 1000 + rand(10,99);
    }
  }
}

$cid = ensure_conversation_api($my_id, $to_id, $nft_id);
json_response(['ok'=>true,'conversation_id'=>$cid, 'redirect'=> BASE_URL . '/inbox.php?id=' . $cid]);
