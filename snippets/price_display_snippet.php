<?php
// snippets/price_display_snippet.php
require_once __DIR__ . '/../app/currency.php';
$alz = (float)$nft['price_alz'];
$eur = alz_to_eur($alz);
?>
<div class="text-sm text-gray-700">
  <strong><?= fmt_alz($alz) ?> ALZ</strong>
  <span class="text-gray-400">(~€<?= fmt_eur($eur) ?>)</span>
</div>
