<?php
// snippets/home_alz_widget.php — place on index.php/homepage
require_once __DIR__ . '/../app/currency.php';
$one = alz_to_eur_precise(1);
$mil = alz_to_eur_precise(1000000);
?>
<style>
.alz-rate-card{background:#fff;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.06);padding:16px}
</style>
<div class="alz-rate-card">
  <div class="text-sm text-gray-600">Current ALZ rate</div>
  <div class="mt-1 text-xl font-bold">1 ALZ ≈ €<?= e($one) ?></div>
  <div class="mt-1 text-base text-gray-700">1 Mil ALZ ≈ €<?= e($mil) ?></div>
</div>
