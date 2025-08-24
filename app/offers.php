<?php
function nft_find(int $nft_id): ?array {
    $stmt = db()->prepare('SELECT n.*, u.username AS seller_username FROM nfts n JOIN users u ON u.id=n.creator_user_id WHERE n.id=? LIMIT 1');
    $stmt->bind_param('i', $nft_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}
function offer_create(int $nft_id, int $buyer_user_id, string $type, ?float $amount_alz, string $message): array {
    $n = nft_find($nft_id);
    if (!$n) return ['ok'=>false, 'error'=>'nft_not_found'];
    $seller_id = (int)$n['creator_user_id'];
    if ($seller_id === $buyer_user_id) return ['ok'=>false, 'error'=>'cannot_buy_own'];
    $offer_type = ($type === 'offer') ? 'offer' : 'buy';
    $amount = ($offer_type === 'offer' && $amount_alz !== null) ? max(0, (float)$amount_alz) : (float)$n['price_alz'];
    $msg = trim($message ?: ($offer_type==='buy' ? 'Buy now request' : 'Offer'));
    $stmt = db()->prepare('INSERT INTO offers (nft_id, buyer_user_id, seller_user_id, offer_type, amount_alz, message) VALUES (?,?,?,?,?,?)');
    $stmt->bind_param('iiisds', $nft_id, $buyer_user_id, $seller_id, $offer_type, $amount, $msg);
    $ok = $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    return $ok ? ['ok'=>true, 'id'=>$id, 'seller_id'=>$seller_id, 'amount'=>$amount] : ['ok'=>false, 'error'=>'db_error'];
}
?>
