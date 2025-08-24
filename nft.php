<?php require_once __DIR__ . '/app/bootstrap.php';
$id = (int)($_GET['id'] ?? 0);
if (!$id) { http_response_code(404); die('NFT not found'); }
if (!function_exists('nft_find')) { require_once __DIR__ . '/app/offers.php'; }
$n = nft_find($id);
if (!$n) { http_response_code(404); die('NFT not found'); }
include __DIR__ . '/app/views/partials/header.php'; ?>
<section class="max-w-6xl mx-auto px-4 py-12">
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <div class="bg-white rounded-2xl shadow overflow-hidden">
      <img src="<?= e($n['image_path']) ?>" alt="<?= e($n['title']) ?>" class="w-full h-auto object-cover">
    </div>
    <div class="bg-white rounded-2xl shadow p-8">
      <div class="flex items-center justify-between mb-3">
        <span class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-sm font-medium"><?= e($n['category']) ?></span>
        <span class="text-sm text-gray-500"><?= e(date('M j, Y', strtotime($n['created_at']))) ?></span>
      </div>
      <h1 class="text-3xl font-bold mb-2"><?= e($n['title']) ?></h1>
      <p class="mb-4 text-gray-600">by <a class="text-purple-600 hover:underline" href="<?= e(BASE_URL) ?>/profile.php?u=<?= e($n['seller_username']) ?>">@<?= e($n['seller_username']) ?></a></p>

      <?php if (!empty($n['description'])): ?>
      <div class="prose max-w-none mb-6">
        <p style="white-space:pre-wrap"><?= nl2br(e($n['description'])) ?></p>
      </div>
      <?php endif; ?>

      <div class="flex items-center justify-between mb-6">
        <div>
          <div class="text-3xl font-extrabold text-purple-600"><?= e($n['price_alz']) ?> ALZ</div>
          <div class="text-sm text-gray-500">≈ €<?= number_format($n['price_alz'] * (float)$settings['alz_to_eur'], 2) ?></div>
        </div>
        <div>
          <button type="button" data-nft-id="<?= (int)$n['id'] ?>" data-title="<?= e($n['title']) ?>" data-price="<?= e($n['price_alz']) ?>" class="buy-now inline-block text-center bg-gradient-to-r from-purple-600 to-pink-600 text-white px-6 py-3 rounded-lg font-semibold hover:from-purple-700 hover:to-pink-700 transition-all transform hover:scale-105">Buy Now</button>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Buy/Offer Modal (reused) -->
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
  const btn = document.querySelector('.buy-now');
  let nftId = btn ? parseInt(btn.dataset.nftId, 10) : null;
  let price = btn ? parseFloat(btn.dataset.price) : 0;
  let name = btn ? btn.dataset.title : '';

  function openModal(){
    if (!btn) return;
    itemEl.textContent = name;
    priceEl.textContent = price.toFixed(2) + ' ALZ';
    eurEl.textContent = '≈ €' + (price * (window.ALZ_TO_EUR||0)).toFixed(2);
    msgEl.value = 'Hi! I want to buy this at the listed price.';
    offerEl.value = '';
    modal.classList.remove('hidden'); modal.classList.add('flex');
  }
  function closeModal(){ modal.classList.add('hidden'); modal.classList.remove('flex'); }
  document.getElementById('bmClose').addEventListener('click', closeModal);
  modal.addEventListener('click', (e)=>{ if(e.target===modal) closeModal(); });
  if (btn) btn.addEventListener('click', openModal);

  async function postJSON(url, body){
    const res = await fetch(url, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF': window.CSRF}, body: JSON.stringify(body)});
    const json = await res.json().catch(()=>({}));
    if(!res.ok || !json.ok) throw new Error(json.error||'Request failed');
    return json;
  }
  document.getElementById('bmConfirm').addEventListener('click', async ()=>{
    try { await postJSON('api/offer_create.php', { nft_id: nftId, type:'buy', message: msgEl.value }); closeModal(); alert('Buy request sent!'); }
    catch(e){ alert(e.message||'Failed'); }
  });
  document.getElementById('bmSendOffer').addEventListener('click', async ()=>{
    const amt = parseFloat(offerEl.value||'0'); if (!amt || amt<=0) { alert('Enter a valid offer amount.'); return; }
    try { await postJSON('api/offer_create.php', { nft_id: nftId, type:'offer', amount_alz: amt, message: msgEl.value || 'Offer' }); closeModal(); alert('Offer sent!'); }
    catch(e){ alert(e.message||'Failed'); }
  });
})();
</script>
<?php include __DIR__ . '/app/views/partials/footer.php'; ?>
