<?php
// In marketplace.php (after you include pages_servers_helpers.php):
$SERVERS = pages_servers();
$filterSrv = isset($_GET['server']) ? $_GET['server'] : '';
?>
<select name="server" class="form-control">
  <option value=""><?='All Servers'?></option>
  <?php foreach ($SERVERS as $s): ?>
    <option value="<?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?>" <?= $filterSrv===$s?'selected':'' ?>><?= htmlspecialchars($s) ?></option>
  <?php endforeach; ?>
</select>
