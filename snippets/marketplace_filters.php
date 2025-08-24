<?php
// ---- marketplace.php snippet ----
require_once __DIR__ . '/app/cm_market_helpers.php';
$CATEGORIES = cm_market_categories();
$SERVERS    = cm_market_servers();
$filterCat = isset($_GET['category']) ? $_GET['category'] : '';
$filterSrv = isset($_GET['server'])   ? $_GET['server']   : '';
?>
<div class="col">
  <select name="category" class="form-control">
    <option value="">All Categories</option>
    <?php foreach ($CATEGORIES as $c): ?>
      <option value="<?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?>" <?= $filterCat===$c?'selected':'' ?>><?= htmlspecialchars($c) ?></option>
    <?php endforeach; ?>
  </select>
</div>
<div class="col">
  <?= cm_servers_select('server', $filterSrv, 'All Servers', 'form-control'); ?>
</div>
