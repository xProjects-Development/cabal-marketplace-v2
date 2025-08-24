<?php
require_once __DIR__ . '/app/bootstrap.php';
require_login();
$db = db();
$me = current_user();
$my_id = (int)$me['id'];

$username = isset($_GET['u']) ? trim($_GET['u']) : null;
$target_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$nft_id = isset($_GET['nft_id']) ? (int)$_GET['nft_id'] : 0;

if ($username && !$target_id) {
  $stmt = $db->prepare("SELECT id FROM users WHERE username=? LIMIT 1");
  $stmt->bind_param('s', $username); $stmt->execute(); $res=$stmt->get_result();
  $row = $res->fetch_assoc(); $stmt->close();
  if ($row) $target_id = (int)$row['id'];
}

if (!$target_id || $target_id === $my_id) {
  // avoid self-DM or missing target
  redirect('/inbox.php');
  exit;
}

function conversations_table_exists(){
  return db()->query("SHOW TABLES LIKE 'conversations'")->num_rows ? true : false;
}

function ensure_conversation($a, $b, $nft_id = 0){
  $db = db();
  $u1 = min((int)$a, (int)$b);
  $u2 = max((int)$a, (int)$b);
  if (conversations_table_exists()) {
    // find existing (ignore nft if 0, otherwise prefer that nft)
    if ($nft_id > 0) {
      $stmt = $db->prepare("SELECT id FROM conversations WHERE buyer_user_id=? AND seller_user_id=? AND nft_id=? LIMIT 1");
      $stmt->bind_param('iii', $u1, $u2, $nft_id);
      $stmt->execute(); $res=$stmt->get_result(); $row=$res->fetch_assoc(); $stmt->close();
      if ($row) return (int)$row['id'];
    }
    // fallback any convo between same pair
    $stmt = $db->prepare("SELECT id FROM conversations WHERE buyer_user_id=? AND seller_user_id=? ORDER BY updated_at DESC LIMIT 1");
    $stmt->bind_param('ii', $u1, $u2);
    $stmt->execute(); $res=$stmt->get_result(); $row=$res->fetch_assoc(); $stmt->close();
    if ($row) return (int)$row['id'];
    // create
    $stmt = $db->prepare("INSERT INTO conversations (nft_id, buyer_user_id, seller_user_id, created_at, updated_at) VALUES (?,?,?,NOW(),NOW())");
    $stmt->bind_param('iii', $nft_id, $u1, $u2); $stmt->execute(); $cid = (int)$stmt->insert_id; $stmt->close();
    return $cid;
  } else {
    // No conversations table: synthesize an ID using messages table
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

$cid = ensure_conversation($my_id, $target_id, $nft_id);
redirect('/inbox.php?id='.$cid);
