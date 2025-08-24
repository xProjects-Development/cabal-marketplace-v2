<?php
// snippets/market_select_with_featured.php
// Use this when you prepare your SELECT. Assumes $whereSql, $limit, $offset, $order already set.
$sql = "SELECT id,title,image_path,price_alz,category,is_featured,created_at,creator_username
          FROM nfts
          {$whereSql}
         ORDER BY {$order}
         LIMIT ? OFFSET ?";
$stmt = db()->prepare($sql);
$stmt->bind_param('ii', $limit, $offset);
$stmt->execute();
$nfts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
