<?php
// In create.php (after you include pages_servers_helpers.php):
$SERVERS = pages_servers();
$selectedSrv = isset($_POST['server']) ? $_POST['server'] : '';
?>
<select name="server" class="form-control">
  <option value=""><?='(choose server)'?></option>
  <?php foreach ($SERVERS as $s): ?>
    <option value="<?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedSrv===$s?'selected':'' ?>><?= htmlspecialchars($s) ?></option>
  <?php endforeach; ?>
</select>
