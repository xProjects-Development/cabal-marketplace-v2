<?php require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/settings_servers.php';
require_once __DIR__ . '/../app/nft_servers.php';
$items = nfts_list_with_server($_GET['category'] ?? null, $_GET['server'] ?? null, $_GET['price'] ?? null, $_GET['sort'] ?? null);
json_response(['items'=>$items]);
