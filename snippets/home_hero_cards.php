<?php
// snippets/home_hero_cards.php
// Renders the two angled cards in the hero using $heroPicks.
$heroPicks = is_array($heroPicks) ? $heroPicks : [];
?>
<div class="grid grid-cols-2 gap-4">
  <?php foreach ($heroPicks as $i => $n): ?>
    <a href="<?= e(BASE_URL) ?>/nft.php?id=<?= (int)$n['id'] ?>"
       class="bg-white rounded-xl p-4 transform <?= $i ? '-rotate-3 mt-8' : 'rotate-3' ?> block shadow hover:shadow-md transition">
      <img src="<?= e($n['image_path']) ?>" class="rounded-lg mb-3 w-full h-40 object-cover" alt="<?= e($n['title']) ?>">
      <h3 class="font-bold text-gray-800 truncate"><?= e($n['title']) ?></h3>
      <p class="text-purple-600 font-bold"><?= number_format((float)$n['price_alz'], 2) ?> ALZ</p>
    </a>
  <?php endforeach; ?>

  <?php if (count($heroPicks) < 2): ?>
    <?php for ($k = count($heroPicks); $k < 2; $k++): ?>
      <div class="bg-white rounded-xl p-4 transform <?= $k ? '-rotate-3 mt-8' : 'rotate-3' ?> opacity-70 shadow">
        <div class="rounded-lg mb-3 w-full h-40 bg-gray-200"></div>
        <h3 class="font-bold text-gray-500">Coming soon</h3>
        <p class="text-purple-400 font-bold">—</p>
      </div>
    <?php endfor; ?>
  <?php endif; ?>
</div>
