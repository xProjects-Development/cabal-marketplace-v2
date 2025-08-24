<?php
// snippets/market_featured_fallback.php
// Use right AFTER $nfts is fetched and BEFORE rendering cards.
if (!empty($nfts)) {
  // If query didn't include is_featured, backfill for visible items
  if (!array_key_exists('is_featured', $nfts[0])) {
    $ids = implode(',', array_map('intval', array_column($nfts, 'id')));
    if ($ids) {
      $map = [];
      $r = db()->query("SELECT id,is_featured FROM nfts WHERE id IN ($ids)");
      while ($r && ($row = $r->fetch_assoc())) $map[(int)$row['id']] = (int)$row['is_featured'];
      foreach ($nfts as &$n) $n['is_featured'] = $map[(int)$n['id']] ?? 0;
      unset($n);
    }
  }
  // Featured first, then newest
  usort($nfts, function($a,$b){
    $af = (int)($a['is_featured'] ?? 0);
    $bf = (int)($b['is_featured'] ?? 0);
    if ($af !== $bf) return $bf - $af;
    return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
  });
}
