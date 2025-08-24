<?php require_once __DIR__ . '/app/bootstrap.php'; require_once __DIR__ . '/app/settings_servers.php'; require_once __DIR__ . '/app/nft_servers.php'; include __DIR__ . '/app/views/partials/header.php'; ?>
<section class="max-w-7xl mx-auto px-4 py-12">
  <div class="text-center mb-12">
    <h2 class="text-4xl font-bold mb-4">Marketplace</h2>
    <p class="text-xl text-gray-600">Discover amazing digital collectibles</p>
  </div>

  <form class="bg-white rounded-xl shadow-lg p-6 mb-8 grid grid-cols-1 md:grid-cols-4 gap-4" method="get">
    <select name="category" class="px-4 py-2 border rounded-lg">
      <option value="">All Categories</option>
      <?php $CATS = function_exists('settings_categories') ? settings_categories() : ['Art','Music','Photography','Gaming','Sports','Collectibles']; foreach ($CATS as $c): ?>
        <option value="<?= e($c) ?>" <?= (($_GET['category'] ?? '') === $c)?'selected':'' ?>><?= e($c) ?></option>
      <?php endforeach; ?>
    </select>

    <select name="server" class="px-4 py-2 border rounded-lg">
      <option value="">All Servers</option>
      <?php $SERVERS = function_exists('settings_servers') ? settings_servers() : ['EU','NA','SEA']; foreach ($SERVERS as $s): ?>
        <option value="<?= e($s) ?>" <?= (($_GET['server'] ?? '') === $s)?'selected':'' ?>><?= e($s) ?></option>
      <?php endforeach; ?>
    </select>

    <select name="price" class="px-4 py-2 border rounded-lg">
      <option value="">All Prices</option>
      <?php foreach (['0-100','100-500','500-1000','1000+'] as $p): ?>
        <option value="<?= e($p) ?>" <?= (($_GET['price'] ?? '') === $p)?'selected':'' ?>><?= e($p) ?></option>
      <?php endforeach; ?>
    </select>

    <select name="sort" class="px-4 py-2 border rounded-lg">
      <?php foreach ([ 'newest'=>'Newest','oldest'=>'Oldest','price-low'=>'Price: Low to High','price-high'=>'Price: High to Low','popular'=>'Most Popular'] as $k=>$v): ?>
        <option value="<?= e($k) ?>" <?= (($_GET['sort'] ?? 'newest') === $k)?'selected':'' ?>><?= e($v) ?></option>
      <?php endforeach; ?>
    </select>

    <button class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">Apply</button>
  </form>

  <?php
  // Fetch rows using your existing function
  $rows = nfts_list_with_server($_GET['category'] ?? null, $_GET['server'] ?? null, $_GET['price'] ?? null, $_GET['sort'] ?? null);

  // --- Featured-first fallback/sort (works even if nfts_list doesn't return is_featured) ---
  if (!empty($rows)) {
    // If the column isn't present, fetch it for visible items
    if (!array_key_exists('is_featured', $rows[0])) {
      $ids = implode(',', array_map('intval', array_column($rows, 'id')));
      if ($ids) {
        $map = [];
        $r = db()->query("SELECT id, is_featured FROM nfts WHERE id IN ($ids)");
        while ($r && ($row = $r->fetch_assoc())) $map[(int)$row['id']] = (int)$row['is_featured'];
        foreach ($rows as &$n) $n['is_featured'] = $map[(int)$n['id']] ?? 0;
        unset($n);
      }
    }
    // Sort: Featured first, then newest
    usort($rows, function($a,$b){
      $af = (int)($a['is_featured'] ?? 0);
      $bf = (int)($b['is_featured'] ?? 0);
      if ($af !== $bf) return $bf - $af; // Featured first
      return strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''); // newest
    });
  }
  ?>

  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
    <?php foreach ($rows as $nft): $eur = number_format($nft['price_alz'] * (float)$settings['alz_to_eur'], 2); ?>
      <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <a href="<?= e(BASE_URL) ?>/nft.php?id=<?= (int)$nft['id'] ?>" class="aspect-square overflow-hidden block relative">
          <img src="<?= e($nft['image_path']) ?>" alt="<?= e($nft['title']) ?>" class="w-full h-full object-cover">
          <?php if (!empty($nft['is_featured'])): ?>
            <span class="absolute top-2 left-2 inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26 6.91 1-5 4.87L18.18 22 12 18.56 5.82 22 7 14.13l-5-4.87 6.91-1z"/></svg>
              Featured
            </span>
          <?php endif; ?>
        </a>

        <div class="p-6">
          <div class="flex items-center justify-between mb-2">
            <span class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-sm font-medium"><?= e($nft['category']) ?></span>
            <span class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-sm font-medium ml-2"><?= e($nft['server'] ?? '—') ?></span>
          </div>

          <h3 class="font-bold text-xl mb-1">
            <a class="hover:underline" href="<?= e(BASE_URL) ?>/nft.php?id=<?= (int)$nft['id'] ?>"><?= e($nft['title']) ?></a>
          </h3>

          <p class="text-gray-600">by
            <a class="text-purple-600 hover:underline" href="<?= e(BASE_URL) ?>/profile.php?u=<?= e($nft['creator_username']) ?>">@<?= e($nft['creator_username']) ?></a>
          </p>

          <?php if (!empty($nft['description'])): ?>
            <p class="text-gray-600 mb-4" style="overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">
              <?= e(mb_substr($nft['description'], 0, 160)) ?><?= (mb_strlen($nft['description'])>160)?'…':'' ?>
            </p>
          <?php else: ?>
            <div class="mb-4"></div>
          <?php endif; ?>

          <div class="flex justify-between items-center mb-4">
            <div>
              <div class="text-2xl font-bold text-purple-600"><?= e($nft['price_alz']) ?> ALZ</div>
              <div class="text-sm text-gray-500">≈ €<?= e($eur) ?></div>
            </div>
            <div class="text-right text-sm text-gray-500">
              <div><i class="fas fa-eye mr-1"></i><?= e(date('M j', strtotime($nft['created_at']))) ?></div>
            </div>
          </div>

          <button type="button"
                  data-nft-id="<?= (int)$nft['id'] ?>"
                  data-title="<?= e($nft['title']) ?>"
                  data-price="<?= e($nft['price_alz']) ?>"
                  class="buy-now w-full inline-block text-center bg-gradient-to-r from-purple-600 to-pink-600 text-white py-3 rounded-lg font-semibold hover:from-purple-700 hover:to-pink-700 transition-all transform hover:scale-105">
            Buy Now
          </button>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- Buy/Offer Modal (unchanged) -->
<div id="buyModal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50">
  <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl p-6">
    <div class="flex items-start justify-between mb-4">
      <div>
        <h3 id="bmTitle" class="text-2xl font-bold">Buy NFT</h3>
        <p class="text-gray-500">Confirm your request below, or make an offer.</p>
      </div>
      <button id="bmClose" class="text-gray-500 hover:text-gray-800 text-2xl leading-none">&times;</button>
    </div>
    <div class="space-y-3 mb-4">
      <div class="flex justify-between"><span class="text-gray-600">Item</span> <span id="bmItem" class="font-semibold"></span></div>
      <div class="flex justify-between"><span class="text-gray-600">Price</span> <span id="bmPrice" class="font-semibold text-purple-600"></span></div>
      <div class="flex justify-between"><span class="text-gray-600">Approx.</span> <span id="bmEur" class="text-gray-700"></span></div>
    </div>
    <div class="border-t pt-4 space-y-3">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Optional message to seller</label>
        <textarea id="bmMsg" rows="3" class="w-full border rounded px-3 py-2" placeholder="Hi! I want to buy this at the listed price."></textarea>
      </div>
      <div class="flex items-center gap-2">
        <input id="bmOfferAmount" type="number" min="0" step="0.01" class="border rounded px-3 py-2 w-40" placeholder="Offer (ALZ)">
        <button id="bmSendOffer" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Send Offer</button>
        <div class="flex-1"></div>
        <button id="bmConfirm" class="px-5 py-2 rounded bg-purple-600 text-white hover:bg-purple-700">Confirm Buy Now</button>
      </div>
      <p class="text-xs text-gray-500">No on-chain payment here—this sends a request to the seller. You can negotiate using the inbox.</p>
    </div>
  </div>
</div>

<script>
(function(){
  const modal = document.getElementById('buyModal');
  const itemEl = document.getElementById('bmItem');
  const priceEl = document.getElementById('bmPrice');
  const eurEl = document.getElementById('bmEur');
  const msgEl = document.getElementById('bmMsg');
  const offerEl = document.getElementById('bmOfferAmount');

  function openModal(btn){
    const nftId = parseInt(btn.dataset.nftId, 10);
    const price = parseFloat(btn.dataset.price);
    const name = btn.dataset.title;
    itemEl.textContent = name;
    priceEl.textContent = price.toFixed(2) + ' ALZ';
    eurEl.textContent = '≈ €' + (price * (window.ALZ_TO_EUR||0)).toFixed(2);
    msgEl.value = 'Hi! I want to buy this at the listed price.';
    offerEl.value = '';
    modal.dataset.nftId = nftId;
    modal.dataset.price = price;
    modal.dataset.title = name;
    modal.classList.remove('hidden'); modal.classList.add('flex');
  }
  function closeModal(){ modal.classList.add('hidden'); modal.classList.remove('flex'); }
  document.getElementById('bmClose').addEventListener('click', closeModal);
  modal.addEventListener('click', (e)=>{ if(e.target===modal) closeModal(); });

  document.querySelectorAll('.buy-now').forEach(btn=> btn.addEventListener('click', ()=> openModal(btn)));

  async function postJSON(url, body){
    const res = await fetch(url, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF': window.CSRF}, body: JSON.stringify(body)});
    const json = await res.json().catch(()=>({}));
    if(!res.ok || !json.ok) throw new Error(json.error||'Request failed');
    return json;
  }

  document.getElementById('bmConfirm').addEventListener('click', async ()=>{
    try {
      await postJSON('api/offer_create.php', { nft_id: parseInt(modal.dataset.nftId,10), type:'buy', message: msgEl.value });
      closeModal(); (window.notify?notify('Buy request sent to the seller!','success'):alert('Buy request sent!'));
    } catch(e){ (window.notify?notify(e.message==='not_logged_in'?'Please log in.':e.message,'error'):alert(e.message||'Failed')); }
  });

  document.getElementById('bmSendOffer').addEventListener('click', async ()=>{
    const amt = parseFloat(offerEl.value||'0'); if (!amt || amt <= 0) { alert('Enter a valid offer amount.'); return; }
    try {
      await postJSON('api/offer_create.php', { nft_id: parseInt(modal.dataset.nftId,10), type:'offer', amount_alz: amt, message: msgEl.value || 'Offer' });
      closeModal(); (window.notify?notify('Offer sent to the seller!','success'):alert('Offer sent!'));
    } catch(e){ (window.notify?notify(e.message==='not_logged_in'?'Please log in.':e.message,'error'):alert(e.message||'Failed')); }
  });
})();
</script>
<?php include __DIR__ . '/app/views/partials/footer.php'; ?>
