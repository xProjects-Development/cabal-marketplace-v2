<?php require_once __DIR__ . '/../app/bootstrap.php';
$after = isset($_GET['after']) ? (int)$_GET['after'] : null;
$items = shouts_fetch(50, $after);
json_response(['items'=>$items]);
