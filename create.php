<?php require_once __DIR__ . '/app/bootstrap.php'; require_once __DIR__ . '/app/settings_servers.php'; require_once __DIR__ . '/app/nft_servers.php'; require_login(); include __DIR__ . '/app/views/partials/header.php'; 
$errors = []; $success = null;
if (is_post()) {
  verify_csrf();
  $title = trim($_POST['title'] ?? '');
  $desc  = trim($_POST['description'] ?? '');
  $price = (float)($_POST['price'] ?? 0);
  $cat   = $_POST['category'] ?? 'Art';
  $server = trim($_POST['server'] ?? '');
  if ($price > (defined('ALZ_MAX') ? ALZ_MAX : 140000000000)) { $price = (defined('ALZ_MAX') ? ALZ_MAX : 140000000000); }
  if (!$title || $price < 0) { $errors[] = 'Please provide a valid title and price.'; }
  $img = upload_image($_FILES['image'] ?? []);
  if (!$img) { $errors[] = 'Please upload a valid image.'; }
  if (!$errors) {
    if (nft_create_with_server((int)current_user()['id'], $title, $desc, $price, $cat, $server, $img)) { $success = 'NFT created successfully!'; }
    else { $errors[] = 'Failed to create NFT.'; }
  }
}
$CATS = function_exists('settings_categories') ? settings_categories() : ['Art','Music','Photography','Gaming','Sports','Collectibles'];
$SERVERS = function_exists('settings_servers') ? settings_servers() : ['EU','NA','SEA'];
$MAXA = defined('ALZ_MAX') ? ALZ_MAX : 140000000000;
?>
<section class="max-w-4xl mx-auto px-4 py-12">
  <div class="text-center mb-12">
    <h1 class="text-4xl font-bold mb-2">Create Your NFT</h1>
    <p class="text-gray-600">Turn your digital art into a unique collectible</p>
  </div>
  <div class="bg-white rounded-2xl shadow-xl p-8">
    <?php if ($errors): ?><div class="bg-red-100 text-red-800 p-3 mb-4 rounded-lg"><?php foreach ($errors as $er) echo '<div>'.e($er).'</div>'; ?></div><?php endif; ?>
    <?php if ($success): ?><div class="bg-green-100 text-green-800 p-3 mb-4 rounded-lg"><?= e($success) ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="space-y-8">
      <?= csrf_field() ?>
      <div>
        <label class="block text-lg font-semibold mb-4">Upload Your Creation *</label>
        <input type="file" name="image" accept="image/*" class="block w-full text-sm border rounded p-2" required>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div>
          <label class="block text-lg font-semibold mb-3">NFT Name *</label>
          <input type="text" name="title" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-purple-500 text-lg" required>
        </div>
        <div>
          <label class="block text-lg font-semibold mb-3">Price (ALZ) *</label>
          <input type="number" step="1" min="0" max="<?= (int)$MAXA ?>" name="price" id="nftPrice" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-purple-500 text-lg" placeholder="0" required>
          <p class="text-sm text-gray-500 mt-2">Max: <?= number_format($MAXA,0,'.',',') ?> ALZ • ≈ €<span id="eurEquivalent">0</span></p>
        </div>
      </div>

      <div>
        <label class="block text-lg font-semibold mb-3">Description</label>
        <textarea name="description" rows="4" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-purple-500 text-lg" placeholder="Tell buyers about this NFT"></textarea>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div>
          <label class="block text-lg font-semibold mb-3">Category</label>
          <select name="category" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-purple-500 text-lg">
            <?php foreach ($CATS as $c): ?>
              <option value="<?= e($c) ?>"><?= e($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-lg font-semibold mb-3">Server</label>
          <select name="server" class="w-full px-4 py-3 border rounded-lg focus:outline-none focus:border-purple-500 text-lg">
            <?php foreach ($SERVERS as $s): ?>
              <option value="<?= e($s) ?>" <?= (isset($_POST['server']) && $_POST['server']===$s)?'selected':'' ?>><?= e($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-lg font-semibold mb-3">Royalties (%)</label>
          <input type="number" min="0" max="10" step="0.1" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-purple-500 text-lg" placeholder="(demo only)">
        </div>
      </div>

      <div class="text-center pt-8">
        <button type="submit" class="bg-gradient-to-r from-purple-600 to-pink-600 text-white px-12 py-4 rounded-xl font-bold text-xl hover:from-purple-700 hover:to-pink-700 transition-all transform hover:scale-105">
          <i class="fas fa-magic mr-3"></i>Create NFT
        </button>
      </div>
    </form>
  </div>
</section>
<script>
document.addEventListener('DOMContentLoaded', function(){
  const priceInput=document.getElementById('nftPrice'); const out=document.getElementById('eurEquivalent');
  function upd(){ const v=parseFloat(priceInput.value||'0'); out.textContent=(v*(window.ALZ_TO_EUR||0)).toFixed(2); }
  priceInput.addEventListener('input', upd); upd();
});
</script>
<?php include __DIR__ . '/app/views/partials/footer.php'; ?>
