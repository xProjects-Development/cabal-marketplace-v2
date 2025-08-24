<?php
// ---- create.php snippet ----
require_once __DIR__ . '/app/cm_market_helpers.php';
$CATEGORIES = cm_market_categories();
$SERVERS    = cm_market_servers();
$selectedCat = isset($_POST['category']) ? $_POST['category'] : '';
$selectedSrv = isset($_POST['server'])   ? $_POST['server']   : '';
?>
<!-- Category -->
<select name="category" class="form-control">
  <?php foreach ($CATEGORIES as $c): ?>
    <option value="<?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedCat===$c?'selected':'' ?>><?= htmlspecialchars($c) ?></option>
  <?php endforeach; ?>
</select>
<!-- Server -->
<?= cm_servers_select('server', $selectedSrv, '(choose server)', 'form-control'); ?>
