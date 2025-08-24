<?php
// Featured NFTs section (homepage). Shows up to 8 featured items.
$res = db()->query("SELECT id, title, image_path, price_alz, category, is_featured, created_at, creator_username
                      FROM nfts
                     WHERE is_featured = 1
                     ORDER BY created_at DESC
                     LIMIT 8");
$feat = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
if ($feat):
?>
<section class="max-w-7xl mx-auto px-4 py-12">
  <div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-bold">Featured NFTs</h2>
    <a href="/marketplace.php?featured=1" class="text-sm text-purple-600 hover:text-purple-800">View all</a>
  </div>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
    <?php foreach ($feat as $n): ?>
      <a href="/nft.php?id=<?= (int)$n['id'] ?>" class="block bg-white rounded-xl shadow hover:shadow-md transition">
        <div class="relative">
          <img src="<?= e($n['image_path']) ?>" alt="<?= e($n['title']) ?>" class="w-full h-40 object-cover rounded-t-xl">
          <div class="absolute top-2 left-2">
            <?php if (!empty($n['is_featured'])) include __DIR__ . '/nft_card_featured_badge.php'; ?>
          </div>
        </div>
        <div class="p-3">
          <div class="font-semibold"><?= e($n['title']) ?></div>
          <div class="text-sm text-gray-500"><?= e($n['price_alz']) ?> ALZ • <?= e($n['category']) ?></div>
          <div class="text-xs text-gray-400">by @<?= e($n['creator_username']) ?></div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
