<?php require_once __DIR__ . '/../app/bootstrap.php'; require_login();
$u = current_user(); $cid = (int)($_GET['c'] ?? 0);
if (!$cid) json_response(['error'=>'missing_conversation'], 400);
$after = isset($_GET['after']) ? (int)$_GET['after'] : null;
$items = messages_list($cid, (int)$u['id'], 50, $after);
json_response(['items'=>$items]);
