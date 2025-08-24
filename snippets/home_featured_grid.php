<?php
// snippets/home_featured_grid.php
// Grid of Featured NFTs (falls back to newest via $featured); expects $settings['alz_to_eur'].
?>
<section class="py-16 bg-white">
  <div class="max-w-7xl mx-auto px-4">
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-3xl font-bold">Featured NFTs</h2>
      <a href="<?= e(BASE_URL) ?>/marketplace.php" class="text-sm text-purple-600 hover:text-purple-800 font-semibold">View marketplace</a>
    </div>

    <?php if (!empty($featured)): ?>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php foreach ($featured as $n): $eur = number_format((float)$n['price_alz'] * (float)($settings['alz_to_eur'] ?? 0), 2); ?>
          <a href="<?= e(BASE_URL) ?>/nft.php?id=<?= (int)$n['id'] ?>"
             class="block bg-white rounded-xl shadow hover:shadow-md transition">
            <div class="relative">
              <img src="<?= e($n['image_path']) ?>" alt="<?= e($n['title']) ?>" class="w-full h-44 object-cover rounded-t-xl">
              <?php if (!empty($n['is_featured'])): ?>
                <span class="absolute top-2 left-2 inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26 6.91 1-5 4.87L18.18 22 12 18.56 5.82 22 7 14.13l-5-4.87 6.91-1z"/></svg>
                  Featured
                </span>
              <?php endif; ?>
            </div>
            <div class="p-3">
              <div class="font-semibold truncate" title="<?= e($n['title']) ?>"><?= e($n['title']) ?></div>
              <?php if (!empty($n['category'])): ?>
                <div class="text-xs inline-block mt-1 bg-purple-100 text-purple-700 rounded-full px-2 py-0.5"><?= e($n['category']) ?></div>
              <?php endif; ?>
              <div class="mt-2 flex items-baseline gap-2">
                <div class="font-bold text-purple-600"><?= number_format((float)$n['price_alz'], 2) ?> ALZ</div>
                <div class="text-xs text-gray-500">≈ €<?= e($eur) ?></div>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="p-6 bg-gray-50 rounded-xl text-gray-600">No featured items yet.</div>
    <?php endif; ?>
  </div>
</section>
