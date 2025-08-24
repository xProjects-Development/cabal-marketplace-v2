<?php require_once __DIR__ . '/../app/bootstrap.php'; verify_csrf();
if (!current_user()) json_response(['ok'=>false,'error'=>'not_logged_in'], 401);
$payload = json_decode(file_get_contents('php://input'), true) ?: [];
$nft_id = (int)($payload['nft_id'] ?? 0);
$type = $payload['type'] ?? 'buy';
$amount = isset($payload['amount_alz']) ? (float)$payload['amount_alz'] : null;
$message = trim($payload['message'] ?? '');
if (!$nft_id) json_response(['ok'=>false,'error'=>'missing_nft_id'], 400);
if ($amount !== null && $amount > ALZ_MAX) json_response(['ok'=>false,'error'=>'amount_too_large'], 400);
$res = offer_create($nft_id, (int)current_user()['id'], $type, $amount, $message);
if (!$res['ok']) { $code = ($res['error']==='cannot_buy_own') ? 403 : 400; json_response($res, $code); }
$n = nft_find($nft_id);
$buyer_id = (int)current_user()['id'];
$seller_id = (int)$n['creator_user_id'];
$cid = conversation_ensure($buyer_id, $seller_id, (int)$n['id'], (int)$res['id']);
$msg = $type==='buy' ? ('Buy now request at ' . number_format((float)$n['price_alz'], 2) . ' ALZ.') : ('Offer: ' . number_format((float)$amount, 2) . ' ALZ.');
if (!empty($message)) { $msg .= ' ' . $message; }
message_send($cid, $buyer_id, $msg);
json_response(['ok'=>true, 'offer_id'=>$res['id'], 'conversation_id'=>$cid]);
