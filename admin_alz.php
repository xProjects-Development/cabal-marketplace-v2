<?php
require_once __DIR__ . '/app/bootstrap.php'; admin_only();
require_once __DIR__ . '/app/settings_kv.php';
require_once __DIR__ . '/app/currency.php';

$errors=[]; $saved=false;
if(is_post()){ verify_csrf();
  $rate=(float)($_POST['alz_to_eur'] ?? 0);
  $amax=(float)($_POST['alz_max'] ?? 0);
  if($rate<=0) $errors[]='Rate must be greater than 0.';
  if($amax<=0) $errors[]='ALZ Maximum must be greater than 0.';
  if(!$errors){ settings_write_kv('alz_to_eur',(string)$rate); settings_write_kv('alz_max',(string)$amax); $saved=true; }
}
$rate=alz_rate(); $amax=alz_max();
include __DIR__ . '/app/views/partials/header.php'; ?>
<section class="max-w-3xl mx-auto px-4 py-10">
  <h1 class="text-3xl font-bold mb-2">ALZ Rate & Limits</h1>
  <p class="text-gray-600 mb-6">Set ALZ→EUR and the global ALZ maximum.</p>
  <?php if ($errors): ?><div class="bg-red-100 text-red-800 p-3 mb-4 rounded-lg"><?php foreach($errors as $er) echo '<div>'.e($er).'</div>'; ?></div><?php endif; ?>
  <?php if ($saved): ?><div class="bg-green-100 text-green-800 p-3 mb-4 rounded-lg">Saved!</div><?php endif; ?>

  <form method="post" class="bg-white rounded-2xl shadow p-6 space-y-4">
    <?= csrf_field() ?>
    <div>
      <label class="block text-sm font-medium mb-1">ALZ → EUR rate</label>
      <input type="number" name="alz_to_eur" step="0.00000001" value="<?= e($rate) ?>" class="w-full border rounded px-3 py-2">
      <p class="text-xs text-gray-500 mt-1">Example: 0.25 means 1 ALZ = €0.25</p>
    </div>
    <div>
      <label class="block text-sm font-medium mb-1">ALZ Maximum</label>
      <input type="number" name="alz_max" step="1" value="<?= e($amax) ?>" class="w-full border rounded px-3 py-2">
      <p class="text-xs text-gray-500 mt-1">Default 140,000,000,000 ALZ.</p>
    </div>
    <div class="pt-2">
      <button class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700">Save</button>
      <a href="/admin.php" class="ml-3 text-gray-600 hover:underline">Back</a>
    </div>
  </form>

  <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="bg-white rounded-2xl shadow p-4">
      <div class="text-sm text-gray-600">Preview</div>
      <div class="text-lg font-semibold mt-1">1 ALZ ≈ €<?= e(alz_to_eur_precise(1)) ?></div>
      <div class="text-lg font-semibold">1,000,000 ALZ ≈ €<?= e(alz_to_eur_precise(1000000)) ?></div>
    </div>
  </div>
</section>
<?php include __DIR__ . '/app/views/partials/footer.php'; ?>
