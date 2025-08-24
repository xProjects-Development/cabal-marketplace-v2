<?php require_once __DIR__ . '/../app/bootstrap.php'; require_login();
$u = current_user();
$rows = conversations_for_user((int)$u['id']);
json_response(['items'=>$rows, 'unread'=>messages_unread_count((int)$u['id'])]);
