<?php
// Only use this if admin_extras.php doesn't already have adminx_nft_feature()
function adminx_nft_feature(int $id, bool $val) {
  admin_only();
  $id = (int)$id; $v = $val ? 1 : 0;
  $stmt = db()->prepare("UPDATE nfts SET is_featured = ? WHERE id = ?");
  $stmt->bind_param('ii', $v, $id);
  $ok = $stmt->execute();
  $stmt->close();
  return $ok;
}
