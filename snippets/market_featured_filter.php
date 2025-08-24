<?php
// Featured filter UI (put above the marketplace grid)
$featured_only = isset($_GET['featured']) && $_GET['featured'] == '1';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$cat = isset($_GET['cat']) ? trim($_GET['cat']) : '';
?>
<form method="get" class="flex flex-wrap items-end gap-3 mb-4">
  <input type="hidden" name="tab" value="<?= e($_GET['tab'] ?? '') ?>">
  <div>
    <label class="inline-flex items-center gap-2">
      <input type="checkbox" name="featured" value="1" <?= $featured_only?'checked':'' ?>>
      <span class="text-sm">Featured only</span>
    </label>
  </div>
  <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= e($q) ?>"><?php endif; ?>
  <?php if ($cat !== ''): ?><input type="hidden" name="cat" value="<?= e($cat) ?>"><?php endif; ?>
  <button class="px-3 py-2 bg-gray-800 text-white rounded">Apply</button>
</form>
