<?php require_once __DIR__ . '/../app/bootstrap.php';
$uid = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if (!$uid) { json_response(['error'=>'missing_user_id'], 400); }
$after = isset($_GET['after']) ? (int)$_GET['after'] : null;
$items = profile_feedback_list($uid, 30, $after);
$stats = profile_feedback_stats($uid);
json_response(['items'=>$items, 'stats'=>$stats]);
