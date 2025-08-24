<?php
require_once __DIR__ . '/app/bootstrap.php';

/** helpers to detect optional columns safely */
function column_exists($table, $col){
  $t = db()->real_escape_string($table);
  $c = db()->real_escape_string($col);
  $q = db()->query("SHOW COLUMNS FROM `{$t}` LIKE '{$c}'");
  return $q && $q->num_rows > 0;
}

/** map desired logical fields -> candidate DB column names (first one that exists wins) */
$colmap = [
  'bio'      => ['bio','about'],
  'avatar'   => ['avatar_path','avatar_url','avatar'],
  'banner'   => ['banner_path','banner_url','banner'],
  'location' => ['location','city','country'],
  'website'  => ['website','site_url','url'],
  'twitter'  => ['twitter'],
  'instagram'=> ['instagram'],
  'discord'  => ['discord'],
];

$optCols = [];                    // actual DB column names we’ll use
$select = 'id, first_name, last_name, username, email';  // required base columns
foreach ($colmap as $k => $choices){
  foreach ($choices as $c){
    if (column_exists('users', $c)){
      $optCols[$k] = $c;
      $select .= ', ' . $c;
      break;
    }
  }
}

/** load target user (by id or username) or current user */
$target = null;
if (isset($_GET['id']) || isset($_GET['u'])) {
  if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = db()->prepare("SELECT {$select}, banner_path FROM users WHERE id=? LIMIT 1");
    $stmt->bind_param('i', $id);
  } else {
    $u = trim($_GET['u']);
    $stmt = db()->prepare("SELECT {$select}, banner_path FROM users WHERE username=? LIMIT 1");
    $stmt->bind_param('s', $u);
  }
  $stmt->execute();
  $res = $stmt->get_result();
  $target = $res->fetch_assoc();
  $stmt->close();
  if (!$target) { http_response_code(404); die('Profile not found'); }
} else {
  if (!current_user()) { redirect('/profiles.php'); }
  $me = current_user();
  $stmt = db()->prepare("SELECT {$select}, banner_path FROM users WHERE id=? LIMIT 1");
  $stmt->bind_param('i', $me['id']);
  $stmt->execute();
  $res = $stmt->get_result();
  $target = $res->fetch_assoc();
  $stmt->close();
}

$target_id = (int)$target['id'];
$is_self = current_user() && (int)current_user()['id'] === $target_id;

/** optional values with graceful fallbacks */
$bio      = trim($target[$optCols['bio']      ?? ''] ?? '');
$avatar   = trim($target[$optCols['avatar']   ?? ''] ?? '');
$banner   = trim($target[$optCols['banner']   ?? ''] ?? '');
$website  = trim($target[$optCols['website']  ?? ''] ?? '');
$location = trim($target[$optCols['location'] ?? ''] ?? '');
$twitter  = trim($target[$optCols['twitter']  ?? ''] ?? '');
$instagram= trim($target[$optCols['instagram']?? ''] ?? '');
$discord  = trim($target[$optCols['discord']  ?? ''] ?? '');

/** existing data for NFTs & ratings */
$my_nfts = nfts_by_user($target_id);
$stats = function_exists('profile_feedback_stats') ? profile_feedback_stats($target_id) : ['avg'=>0,'count'=>0];

include __DIR__ . '/app/views/partials/header.php';
?>
<section class="max-w-7xl mx-auto px-4 py-12">

<?php
  // Prefer the mapped/alias-safe $banner, fall back to banner_path if needed
  $bannerUrl = $banner ?: ($target['banner_path'] ?? '');
  if (!empty($bannerUrl)):
?>
  <div class="relative rounded-2xl mb-6 overflow-hidden shadow">
    <img src="<?= e($bannerUrl) ?>"
         alt="Profile banner"
         class="w-full h-40 md:h-48 object-cover block"
         loading="lazy">
  </div>
<?php endif; ?>


  <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
    <div class="flex flex-col md:flex-row items-center md:items-start gap-6">
      <div class="w-32 h-32 rounded-full overflow-hidden relative bg-gradient-to-br from-purple-400 to-pink-400 flex items-center justify-center text-white text-4xl font-bold">
        <?php if ($avatar): ?>
          <img src="<?= e($avatar) ?>" class="absolute inset-0 w-full h-full object-cover" alt="<?= e($target['username']) ?>">
        <?php else: ?>
          <?= e(strtoupper($target['first_name'][0] ?? $target['username'][0] ?? 'U')) ?>
        <?php endif; ?>
      </div>

      <div class="flex-1 text-center md:text-left">
        <h1 class="text-3xl md:text-4xl font-bold mb-1"><?= e($target['first_name'] . ' ' . $target['last_name']) ?></h1>
        <p class="text-gray-600">@<?= e($target['username']) ?></p>

        <?php if ($bio): ?>
          <p class="text-gray-700 mt-3 whitespace-pre-line"><?= nl2br(e($bio)) ?></p>
        <?php endif; ?>

        <div class="flex flex-wrap gap-4 text-sm text-gray-500 mt-3">
          <?php if ($location): ?><span><i class="fas fa-map-marker-alt mr-1"></i><?= e($location) ?></span><?php endif; ?>
          <?php if ($website):  // normalize website ?>
            <?php $w = (stripos($website,'http')===0) ? $website : ('https://' . $website); ?>
            <a href="<?= e($w) ?>" target="_blank" rel="noopener" class="hover:underline">
              <i class="fas fa-link mr-1"></i><?= e(parse_url($w, PHP_URL_HOST) ?: $website) ?>
            </a>
          <?php endif; ?>
        </div>

        <div class="flex items-center gap-3 mt-2">
          <?php if ($twitter):   ?><a target="_blank" rel="noopener" class="text-blue-500 hover:text-blue-600" href="https://twitter.com/<?= e(ltrim($twitter,'@')) ?>"><i class="fab fa-twitter"></i></a><?php endif; ?>
          <?php if ($instagram): ?><a target="_blank" rel="noopener" class="text-pink-500 hover:text-pink-600" href="https://instagram.com/<?= e(ltrim($instagram,'@')) ?>"><i class="fab fa-instagram"></i></a><?php endif; ?>
          <?php if ($discord):   ?><span class="text-indigo-500"><i class="fab fa-discord mr-1"></i><?= e($discord) ?></span><?php endif; ?>
        </div>

        <?php if (function_exists('profile_feedback_stats')): ?>
          <div class="flex items-center gap-3 justify-center md:justify-start mt-4">
            <div class="flex items-center">
              <div class="text-yellow-500 mr-2">
                <?php $full = (int)floor($stats['avg']); for ($i=1;$i<=5;$i++): ?>
                  <i class="fas fa-star<?= $i <= $full ? '' : '-o' ?>"></i>
                <?php endfor; ?>
              </div>
              <div class="text-gray-700 font-semibold"><?= $stats['avg'] ? number_format($stats['avg'], 2) : '—' ?>/5</div>
            </div>
            <div class="text-gray-500">(<?= (int)$stats['count'] ?> ratings)</div>
          </div>
        <?php endif; ?>
      </div>

      <?php if ($is_self): ?>
        <a href="<?= e(BASE_URL) ?>/create.php" class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition-all">
          <i class="fas fa-plus mr-2"></i>Create NFT
        </a>
      <?php endif; ?>
      <?php include __DIR__ . '/profile_edit_button_snippet.php'; ?>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Collection -->
    <div class="lg:col-span-2">
      <?php if (!$my_nfts): ?>
        <div class="bg-white rounded-2xl shadow p-8 text-center text-gray-600">No NFTs yet.</div>
      <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <?php foreach ($my_nfts as $nft): $eur = number_format($nft['price_alz'] * (float)($settings['alz_to_eur'] ?? 0), 2); ?>
            <div class="bg-white border rounded-2xl shadow-sm overflow-hidden">
              <a href="<?= e(BASE_URL) ?>/nft.php?id=<?= (int)$nft['id'] ?>" class="block">
                <img src="<?= e($nft['image_path']) ?>" class="w-full h-64 object-cover" alt="">
              </a>
              <div class="p-4">
                <div class="flex justify-between items-center">
                  <h4 class="font-bold"><a class="hover:underline" href="<?= e(BASE_URL) ?>/nft.php?id=<?= (int)$nft['id'] ?>"><?= e($nft['title']) ?></a></h4>
                  <span class="text-sm px-3 py-1 rounded-full bg-gray-100"><?= e($nft['category']) ?></span>
                </div>
                <?php if (!empty($nft['description'])): ?>
                  <p class="text-gray-600 mt-1" style="overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">
                    <?= e(mb_substr($nft['description'], 0, 160)) ?><?= (mb_strlen($nft['description'])>160)?'…':'' ?>
                  </p>
                <?php endif; ?>
                <div class="mt-2 text-purple-600 font-semibold">
                  <?= e($nft['price_alz']) ?> ALZ <span class="text-gray-500 text-sm">≈ €<?= e($eur) ?></span>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Reviews panel -->
    <div>
      <div class="bg-white rounded-2xl shadow-xl p-6">
        <h3 class="text-2xl font-bold mb-4">Comments & Ratings</h3>
        <div id="feedbackList" class="space-y-3 max-h-80 overflow-y-auto mb-4"></div>

        <?php if (current_user() && !$is_self && function_exists('profile_feedback_add')): ?>
          <div class="border-t pt-4">
            <div class="mb-2 font-semibold">Leave a rating</div>
            <div class="flex items-center gap-2 mb-3">
              <select id="ratingSelect" class="border rounded px-3 py-2">
                <option value="5">★★★★★ (5)</option>
                <option value="4">★★★★☆ (4)</option>
                <option value="3">★★★☆☆ (3)</option>
                <option value="2">★★☆☆☆ (2)</option>
                <option value="1">★☆☆☆☆ (1)</option>
              </select>
            </div>
            <textarea id="commentInput" rows="3" class="w-full border rounded px-3 py-2 mb-3" placeholder="Say something nice (or helpful)"></textarea>
            <button onclick="submitFeedback(<?= (int)$target_id ?>)" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">Post</button>
            <p class="text-xs text-gray-500 mt-2">Your newest rating/comment replaces your previous one.</p>
          </div>
        <?php elseif (!$is_self): ?>
          <p class="text-sm text-gray-500">Log in to leave a comment & rating.</p>
        <?php else: ?>
          <p class="text-sm text-gray-500">This is your profile. Others can leave you feedback.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php if (function_exists('profile_feedback_list')): ?>
<script>
let lastFeedbackId = null;
async function loadFeedback(uid){
  try {
    const res = await fetch('api/profile_feedback_fetch.php?user_id='+encodeURIComponent(uid)+(lastFeedbackId?('&after='+lastFeedbackId):''), {headers:{'X-CSRF': window.CSRF}});
    if(!res.ok) return;
    const data = await res.json();
    const list = document.getElementById('feedbackList');
    if (!lastFeedbackId) list.innerHTML='';
    if (data.items && data.items.length){
      data.items.forEach(it=>{
        const div = document.createElement('div');
        div.className = 'p-3 bg-gray-50 rounded-lg';
        const stars = '★'.repeat(it.rating) + '☆'.repeat(5-it.rating);
        div.innerHTML = `<div class="text-sm text-gray-600 mb-1"><a class="font-semibold text-purple-700 hover:underline" href="profile.php?u=${encodeURIComponent(it.username)}">${it.first_name} ${it.last_name}</a> • <span>${new Date(it.updated_at||it.created_at).toLocaleString()}</span></div>
                         <div class="text-yellow-500 mb-1">${stars}</div>
                         <div class="text-gray-800 whitespace-pre-wrap"></div>`;
        div.querySelector('div.text-gray-800').textContent = it.comment || '';
        list.appendChild(div);
        lastFeedbackId = it.id;
        list.scrollTop = list.scrollHeight;
      });
    }
  } catch(e){}
}
async function submitFeedback(uid){
  const rating = parseInt(document.getElementById('ratingSelect').value, 10) || 5;
  const comment = document.getElementById('commentInput').value.trim();
  if (!comment){ alert('Please write a short comment.'); return; }
  const res = await fetch('api/profile_feedback_post.php', {
    method:'POST',
    headers: {'Content-Type':'application/json','X-CSRF': window.CSRF},
    body: JSON.stringify({user_id: uid, rating: rating, comment: comment})
  });
  const data = await res.json();
  if (data && data.ok){ document.getElementById('commentInput').value=''; lastFeedbackId=null; loadFeedback(uid); }
  else { alert((data && data.error) || 'Failed to post'); }
}
document.addEventListener('DOMContentLoaded', ()=>{ loadFeedback(<?= (int)$target_id ?>); setInterval(()=>loadFeedback(<?= (int)$target_id ?>), 8000); });
</script>
<?php endif; ?>
<?php include __DIR__ . '/app/views/partials/footer.php'; ?>
