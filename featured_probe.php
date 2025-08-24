<?php
// featured_probe.php — diagnostics (admin only)
require_once __DIR__ . '/app/bootstrap.php'; admin_only();
header('Content-Type: text/plain; charset=utf-8');

function has_col($t,$c){
  $t = db()->real_escape_string($t);
  $c = db()->real_escape_string($c);
  $r = db()->query("SHOW COLUMNS FROM `{$t}` LIKE '{$c}'");
  return $r && $r->num_rows > 0;
}

echo "Featured probe\n";
echo "Column is_featured exists: ".(has_col('nfts','is_featured')?'YES':'NO')."\n";

$r = db()->query("SELECT COUNT(*) c FROM nfts"); $total = $r?$r->fetch_assoc()['c']:0;
$r = db()->query("SELECT COUNT(*) c FROM nfts WHERE is_featured=1"); $feat = $r?$r->fetch_assoc()['c']:0;
echo "NFTs total: {$total}\n";
echo "Featured: {$feat}\n";

$q = db()->query("SELECT id,title,is_featured,created_at FROM nfts ORDER BY id DESC LIMIT 5");
$i=0; while($q && ($row=$q->fetch_assoc())){
  $i++; echo "#{$i} id={$row['id']} featured={$row['is_featured']} created={$row['created_at']} title=".substr((string)$row['title'],0,40)."\n";
}
if (!$i) echo "No rows.\n";
