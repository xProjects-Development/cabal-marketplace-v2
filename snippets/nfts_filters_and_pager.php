<?php
$cat = $_GET['cat'] ?? '';
$featured = $_GET['featured'] ?? '';
$per = isset($_GET['per']) ? max(5,(int)$_GET['per']) : 25;
$page = isset($_GET['page'])? max(1,(int)$_GET['page']) : 1;
$total_nfts = adminu_nfts_total($Q, $cat, $featured);
$offset = ($page-1) * $per;
$nfts = adminx_nfts($Q, $per, $offset); // reuse your existing fetch
?>
<form method="get" class="flex flex-wrap items-end gap-3 mb-4">
  <input type="hidden" name="tab" value="nfts">
  <div>
    <label class="block text-xs text-gray-600 mb-1">Category</label>
    <select name="cat" class="border rounded px-2 py-1">
      <option value="">All</option>
      <?php if (function_exists('settings_categories')): foreach (settings_categories() as $c): ?>
        <option value="<?= e($c) ?>" <?= $cat===$c?'selected':'' ?>><?= e($c) ?></option>
      <?php endforeach; endif; ?>
    </select>
  </div>
  <div>
    <label class="block text-xs text-gray-600 mb-1">Featured</label>
    <select name="featured" class="border rounded px-2 py-1">
      <option value="">All</option>
      <option value="1" <?= $featured==='1'?'selected':'' ?>>Featured</option>
      <option value="0" <?= $featured==='0'?'selected':'' ?>>Not featured</option>
    </select>
  </div>
  <div>
    <label class="block text-xs text-gray-600 mb-1">Per page</label>
    <select name="per" class="border rounded px-2 py-1">
      <?php foreach ([25,50,100,200] as $pp): ?>
        <option value="<?= $pp ?>" <?= $per===$pp?'selected':'' ?>><?= $pp ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <button class="bg-gray-800 text-white px-3 py-2 rounded">Apply</button>
  <a class="px-3 py-2 rounded bg-gray-100" href="admin_export.php?type=nfts&<?= http_build_query(['q'=>$Q,'cat'=>$cat,'featured'=>$featured]) ?>">Export CSV</a>
</form>
<?php adminu_pager($total_nfts, $per, $page); ?>
