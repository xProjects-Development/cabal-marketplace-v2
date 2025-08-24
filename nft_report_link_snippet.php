<?php // Include near the NFT action buttons
if (current_user() && isset($n) && !empty($n['id'])): ?>
  <a href="<?= e(BASE_URL) ?>/report.php?type=nft&id=<?= (int)$n['id'] ?>"
     class="inline-flex items-center gap-2 text-sm text-red-700 hover:underline">
     <i class="fas fa-flag"></i> Report
  </a>
<?php endif; ?>
