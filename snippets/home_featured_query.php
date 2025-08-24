<?php
// snippets/home_featured_query.php
// Pulls up to 8 featured NFTs; falls back to newest if none.
$featured = [];
$res = db()->query("
  SELECT id, title, image_path, price_alz, category, created_at, is_featured
    FROM nfts
   WHERE is_featured = 1
   ORDER BY created_at DESC
   LIMIT 8
");
if ($res) { $featured = $res->fetch_all(MYSQLI_ASSOC); }

if (!$featured) {
  $res = db()->query("
    SELECT id, title, image_path, price_alz, category, created_at, 0 AS is_featured
      FROM nfts
     ORDER BY created_at DESC
     LIMIT 8
  ");
  if ($res) { $featured = $res->fetch_all(MYSQLI_ASSOC); }
}

$heroPicks = array_slice($featured, 0, 2);
?>
