<?php
// snippets/market_featured_force.php
// Drop this NEAR THE TOP of your marketplace page, BEFORE you render $nfts.
// It backfills missing `is_featured` on $nfts and sorts Featured-first.

if (isset($nfts) && is_array($nfts) && $nfts) {
  // 1) If current query didn't include is_featured, fetch it for visible items
  $need = false;
  foreach ($nfts as $n) { if (!array_key_exists('is_featured', $n)) { $need = true; break; } }
  if ($need) {
    $ids = array_map(function($x){ return (int)($x['id'] ?? 0); }, $nfts);
    $ids = array_values(array_unique(array_filter($ids)));
    if ($ids) {
      $in = implode(',', $ids);
      $res = db()->query("SELECT id, is_featured FROM nfts WHERE id IN ({$in})");
      $map = [];
      while ($res && ($row = $res->fetch_assoc())) { $map[(int)$row['id']] = (int)$row['is_featured']; }
      foreach ($nfts as &$n) { $n['is_featured'] = $map[(int)($n['id'] ?? 0)] ?? 0; }
      unset($n);
    }
  }

  // 2) Sort Featured DESC, then created_at DESC (fallback to id DESC)
  usort($nfts, function($a, $b){
    $af = (int)($a['is_featured'] ?? 0);
    $bf = (int)($b['is_featured'] ?? 0);
    if ($af !== $bf) return $bf - $af;
    $ad = isset($a['created_at']) ? strtotime($a['created_at']) : 0;
    $bd = isset($b['created_at']) ? strtotime($b['created_at']) : 0;
    if ($ad !== $bd) return $bd <=> $ad;
    return (int)($b['id'] ?? 0) <=> (int)($a['id'] ?? 0);
  });
}
?>
