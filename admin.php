<?php require_once __DIR__ . '/app/bootstrap.php'; require_once __DIR__ . '/app/settings_servers.php'; admin_only();
require_once __DIR__ . '/app/admin_upgrades.php';
require_once __DIR__ . '/app/admin_extras.php';
require_once __DIR__ . '/app/admin_links.php';
// Optional: turn on verbose errors for admins only (comment these two lines if not needed)
ini_set('display_errors', '1'); error_reporting(E_ALL);
// Handle POST actions
if (is_post()) { verify_csrf();
  if (isset($_POST['user_suspend'])) adminx_user_suspend((int)$_POST['user_suspend'], true);
  if (isset($_POST['user_unsuspend'])) adminx_user_suspend((int)$_POST['user_unsuspend'], false);
  if (isset($_POST['user_role']) && isset($_POST['user_id'])) adminx_user_update_role((int)$_POST['user_id'], $_POST['user_role']);
  if (isset($_POST['nft_delete'])) adminx_nft_delete((int)$_POST['nft_delete']);
  if (isset($_POST['nft_feature'])) adminx_nft_feature((int)$_POST['nft_feature'], true);
  if (isset($_POST['nft_unfeature'])) adminx_nft_feature((int)$_POST['nft_unfeature'], false);
  if (isset($_POST['close_report'])) adminx_report_close((int)$_POST['close_report']);
  if (isset($_POST['save_settings'])){
    // …existing settings save code…

// NEW: Save servers CSV (optional)
if (isset($_POST['servers_csv']) && function_exists('settings_update_servers')) {
  $csv = trim((string)$_POST['servers_csv']);
  $arr = array_filter(array_map('trim', explode(',', $csv)), fn($x)=>$x!=='');
  settings_update_servers($arr);
}

    $a = (float)$_POST['alz_to_eur']; $t = (float)$_POST['transaction_fee']; $m = isset($_POST['maintenance_mode']) ? 1 : 0;
    settings_update($a,$t,$m);
    if (isset($_POST['cats_csv']) && function_exists('settings_update_categories')) {
      $csv = trim($_POST['cats_csv']);
      $arr = array_filter(array_map('trim', explode(',', $csv)), function($x){ return $x!==''; });
      settings_update_categories($arr);
    }
    if (isset($_POST['servers_csv']) && function_exists('settings_update_servers')) {
      $csv2 = trim($_POST['servers_csv']);
      $arr2 = array_filter(array_map('trim', explode(',', $csv2)), function($x){ return $x!==''; });
      settings_update_servers($arr2);
    }
    adminx_log('settings.update', ['alz_to_eur'=>$a,'transaction_fee'=>$t,'maintenance'=>$m]);
  }
  redirect('/admin.php');
}
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'overview';
$Q = isset($_GET['q']) ? trim($_GET['q']) : '';
$counts = adminx_counts();
$settings = settings_load();
$users = adminx_users($Q, 50, 0);
$nfts  = adminx_nfts($Q, 50, 0);
$offers = adminx_offers(25);
$reports_open = adminx_reports('open', 50);
include __DIR__ . '/app/views/partials/header.php'; ?>
<section class="max-w-7xl mx-auto px-4 py-12">
  <div class="flex items-center justify-between mb-6">
    <h2 class="text-3xl font-extrabold">Admin Panel</h2>
    <form method="get" class="flex items-center gap-2">
      <input type="hidden" name="tab" value="<?= e($tab) ?>">
      <input type="text" name="q" value="<?= e($Q) ?>" placeholder="Search users/NFTs..." class="px-4 py-2 border rounded-lg w-64">
      <button class="bg-gray-800 text-white px-4 py-2 rounded-lg">Search</button>
    </form>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
    <div class="bg-white rounded-xl p-4 shadow"><div class="text-sm text-gray-500">Users</div><div class="text-2xl font-bold"><?= (int)$counts['users'] ?></div></div>
    <div class="bg-white rounded-xl p-4 shadow"><div class="text-sm text-gray-500">NFTs</div><div class="text-2xl font-bold"><?= (int)$counts['nfts'] ?></div></div>
    <div class="bg-white rounded-xl p-4 shadow"><div class="text-sm text-gray-500">Offers</div><div class="text-2xl font-bold"><?= (int)$counts['offers'] ?></div></div>
    <div class="bg-white rounded-xl p-4 shadow"><div class="text-sm text-gray-500">Messages</div><div class="text-2xl font-bold"><?= (int)$counts['messages'] ?></div></div>
    <div class="bg-white rounded-xl p-4 shadow"><div class="text-sm text-gray-500">Reports (Open)</div><div class="text-2xl font-bold"><?= (int)$counts['open_reports'] ?></div></div>
  </div>

<div class="mb-6 border-b">
  <nav class="-mb-px flex space-x-6">
    <?php
      $tabs = array(
        'overview'=>'Overview','users'=>'Users','nfts'=>'NFTs',
        'offers'=>'Offers','reports'=>'Reports','settings'=>'Settings','system'=>'System'
      );
      foreach ($tabs as $k=>$v): ?>
        <a href="?tab=<?= e($k) ?>"
           class="px-3 py-2 border-b-2 <?= $tab===$k ? 'border-purple-600 text-purple-600' : 'border-transparent text-gray-600 hover:text-gray-800' ?>">
           <?= e($v) ?>
        </a>
    <?php endforeach; ?>

    <!-- Shoutbox (standalone page) -->
    <a href="admin_shoutbox.php"
       class="px-3 py-2 border-b-2 border-transparent text-gray-600 hover:text-gray-800">
       Shoutbox
    </a>
  </nav>
</div>


  <?php if ($tab==='overview'): ?>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <div class="bg-white rounded-2xl shadow p-6">
        <h3 class="font-bold text-xl mb-4">Latest Offers</h3>
        <div class="space-y-3" style="max-height:24rem;overflow:auto;">
          <?php if (!$offers): ?><div class="text-gray-500">No offers yet.</div><?php endif; ?>
          <?php foreach ($offers as $o): ?>
            <div class="p-3 bg-gray-50 rounded flex items-center justify-between">
              <div>
                <div class="font-semibold">#<?= (int)$o['id'] ?> — <?= e(strtoupper($o['type'])) ?> <?= $o['amount_alz'] ? e(number_format($o['amount_alz'],2)) : '' ?> ALZ</div>
                <div class="text-sm text-gray-600">NFT: <?= e($o['title']) ?> • Seller @<?= e($o['seller_username']) ?> • Buyer @<?= e($o['buyer_username']) ?></div>
              </div>
              <div class="text-sm text-gray-500"><?= e(date('M j, H:i', strtotime($o['created_at']))) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="bg-white rounded-2xl shadow p-6">
        <h3 class="font-bold text-xl mb-4">Open Reports</h3>
        <div class="space-y-3" style="max-height:24rem;overflow:auto;">
          <?php $reports_open = $reports_open ?: array(); if (!$reports_open): ?><div class="text-gray-500">No reports.</div><?php endif; ?>
          <?php foreach ($reports_open as $r): ?>
            <div class="p-3 bg-gray-50 rounded flex items-center justify-between">
              <div>
                <div class="font-semibold">
  #<?= (int)$r['id'] ?> —
  <?php
    $isNft = strtolower((string)$r['target_type']) === 'nft' && (int)$r['target_id'] > 0;
    if ($isNft) {
      $url = function_exists('admin_frontend_url_for_nft')
        ? admin_frontend_url_for_nft((int)$r['target_id'])
        : ((@is_file($_SERVER['DOCUMENT_ROOT'].'/nft.php') ? '/nft.php?id=' : '/marketplace.php?nft=') . (int)$r['target_id']);
      ?>
      <a class="underline text-purple-700 hover:text-purple-900" target="_blank" href="<?= e($url) ?>">
        NFT <?= (int)$r['target_id'] ?>
      </a>
      <?php
    } else {
      echo e($r['target_type']).' '.(int)$r['target_id'];
    }
  ?>
</div>

                <div class="text-sm text-gray-600">by @<?= e($r['reporter_username'] ?? 'unknown') ?> — <?= e($r['reason']) ?></div>
              </div>
              <div class="flex items-center">
  <?php if (strtolower((string)$r['target_type']) === 'nft' && (int)$r['target_id'] > 0):
    $url = function_exists('admin_frontend_url_for_nft')
      ? admin_frontend_url_for_nft((int)$r['target_id'])
      : ((@is_file($_SERVER['DOCUMENT_ROOT'].'/nft.php') ? '/nft.php?id=' : '/marketplace.php?nft=') . (int)$r['target_id']);
  ?>
    <a class="text-sm border px-3 py-1 rounded mr-2 hover:bg-gray-100" target="_blank" href="<?= e($url) ?>">View NFT</a>
  <?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <button name="close_report" value="<?= (int)$r['id'] ?>" class="text-sm bg-green-600 text-white px-3 py-1 rounded">Close</button>
  </form>
</div>

            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php elseif ($tab==='users'): ?>
    <div class="bg-white rounded-2xl shadow p-6">
      <h3 class="font-bold text-xl mb-4">Users</h3>
      <div class="space-y-3" style="max-height:70vh;overflow:auto;">
        <?php foreach ($users as $us): ?>
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
            <div>
              <div class="font-semibold"><?= e($us['first_name'].' '.$us['last_name']) ?> (@<?= e($us['username']) ?>)</div>
              <div class="text-sm text-gray-500"><?= e($us['email']) ?> • <?= e($us['role']) ?> • <?= e($us['status']) ?></div>
            </div>
            <form method="post" class="flex items-center gap-2"><?= csrf_field() ?>
              <?php if ($us['status']!=='suspended'): ?>
                <button name="user_suspend" value="<?= (int)$us['id'] ?>" class="bg-red-500 text-white px-3 py-1 rounded text-sm">Suspend</button>
              <?php else: ?>
                <button name="user_unsuspend" value="<?= (int)$us['id'] ?>" class="bg-green-500 text-white px-3 py-1 rounded text-sm">Unsuspend</button>
              <?php endif; ?>
              <input type="hidden" name="user_id" value="<?= (int)$us['id'] ?>">
              <select name="user_role" class="border rounded px-2 py-1 text-sm">
                <option value="user" <?= $us['role']==='user'?'selected':'' ?>>user</option>
                <option value="admin" <?= $us['role']==='admin'?'selected':'' ?>>admin</option>
              </select>
              <button class="bg-gray-700 text-white px-3 py-1 rounded text-sm">Save</button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php elseif ($tab==='nfts'): ?>
    <div class="bg-white rounded-2xl shadow p-6">
      <h3 class="font-bold text-xl mb-4">NFTs</h3>
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4" style="max-height:70vh;overflow:auto;">
        <?php foreach ($nfts as $n): ?>
          <div class="flex gap-3 p-3 bg-gray-50 rounded">
            <img src="<?= e($n['image_path']) ?>" class="w-16 h-16 object-cover rounded">
            <div class="flex-1">
              <div class="font-semibold"><?= e($n['title']) ?></div>
              <div class="text-sm text-gray-500"><?= e($n['price_alz']) ?> ALZ • @<?= e($n['creator_username']) ?> • <?= e($n['category']) ?></div>
              <div class="text-xs text-gray-400"><?= e(date('M j, Y H:i', strtotime($n['created_at']))) ?></div>
            </div>
            <form method="post" class="flex flex-col gap-2 items-end"><?= csrf_field() ?>
              <?php if ((int)$n['is_featured']===1): ?>
                <button name="nft_unfeature" value="<?= (int)$n['id'] ?>" class="bg-yellow-500 text-white px-3 py-1 rounded text-sm">Unfeature</button>
              <?php else: ?>
                <button name="nft_feature" value="<?= (int)$n['id'] ?>" class="bg-blue-600 text-white px-3 py-1 rounded text-sm">Feature</button>
              <?php endif; ?>
              <button name="nft_delete" value="<?= (int)$n['id'] ?>" class="bg-red-600 text-white px-3 py-1 rounded text-sm" onclick="return confirm('Delete this NFT?');">Delete</button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php elseif ($tab==='offers'): ?>
    <div class="bg-white rounded-2xl shadow p-6">
      <h3 class="font-bold text-xl mb-4">Recent Offers</h3>
      <div class="space-y-3" style="max-height:70vh;overflow:auto;">
        <?php $rows = $offers; if (!$rows): ?><div class="text-gray-500">No offers.</div><?php endif; ?>
        <?php foreach ($rows as $o): ?>
          <div class="p-3 bg-gray-50 rounded flex items-center justify-between">
            <div>
              <div class="font-semibold">#<?= (int)$o['id'] ?> — <?= e(strtoupper($o['type'])) ?> <?= $o['amount_alz'] ? e(number_format($o['amount_alz'],2)) : '' ?> ALZ</div>
              <div class="text-sm text-gray-600">NFT: <?= e($o['title']) ?> • Seller @<?= e($o['seller_username']) ?> • Buyer @<?= e($o['buyer_username']) ?></div>
            </div>
            <div class="text-sm text-gray-500"><?= e(date('M j, H:i', strtotime($o['created_at']))) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php elseif ($tab==='reports'): ?>
    <div class="bg-white rounded-2xl shadow p-6">
      <h3 class="font-bold text-xl mb-4">Reports (Open)</h3>
      <div class="space-y-3" style="max-height:70vh;overflow:auto;">
        <?php if (!$reports_open): ?><div class="text-gray-500">No open reports.</div><?php endif; ?>
        <?php foreach ($reports_open as $r): ?>
          <div class="p-3 bg-gray-50 rounded flex items-center justify-between">
            <div>
              <div class="font-semibold">
  #<?= (int)$r['id'] ?> —
  <?php
    $tt  = strtolower((string)($r['target_type'] ?? ''));
    $tid = (int)($r['target_id'] ?? 0);

    if ($tt === 'nft' && $tid > 0) {
        // Build URL to the NFT detail (uses helper if available, else fallback)
        $url = function_exists('admin_frontend_url_for_nft')
            ? admin_frontend_url_for_nft($tid)
            : ((@is_file($_SERVER['DOCUMENT_ROOT'].'/nft.php') ? '/nft.php?id=' : '/marketplace.php?nft=') . $tid);
        ?>
        <a class="underline text-purple-700 hover:text-purple-900" target="_blank" href="<?= e($url) ?>">
          NFT <?= $tid ?>
        </a>
        <?php
    } else {
        echo e($r['target_type']) . ' ' . $tid;
    }
  ?>
</div>

              <div class="text-sm text-gray-600">by @<?= e($r['reporter_username'] ?? 'unknown') ?> — <?= e($r['reason']) ?> • <?= e(date('M j, H:i', strtotime($r['created_at']))) ?></div>
            </div>
            <form method="post"><?= csrf_field() ?><button name="close_report" value="<?= (int)$r['id'] ?>" class="text-sm bg-green-600 text-white px-3 py-1 rounded">Close</button></form>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php elseif ($tab==='settings'): ?>
    <div class="bg-white rounded-2xl shadow-xl p-8">
      <h3 class="text-2xl font-bold mb-6">Platform Settings</h3>
      <form method="post" class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?= csrf_field() ?>
        <div><label class="block text-sm font-medium mb-2">ALZ to EUR Rate</label><input type="number" name="alz_to_eur" step="0.01" value="<?= e($settings['alz_to_eur']) ?>" class="w-full px-3 py-2 border rounded-lg"></div>
        <div><label class="block text-sm font-medium mb-2">Transaction Fee (%)</label><input type="number" name="transaction_fee" step="0.1" value="<?= e($settings['transaction_fee']) ?>" class="w-full px-3 py-2 border rounded-lg"></div>
        <div class="flex items-end"><label class="inline-flex items-center"><input type="checkbox" name="maintenance_mode" <?= $settings['maintenance_mode']?'checked':'' ?> class="w-4 h-4 text-purple-600"><span class="ml-2">Maintenance Mode</span></label></div>
        <div class="md:col-span-3"><label class="block text-sm font-medium mb-2">Categories (comma-separated)</label><input type="text" name="cats_csv" value="<?php echo function_exists('settings_categories') ? e(implode(', ', settings_categories())) : 'Art, Music, Photography, Gaming, Sports, Collectibles'; ?>" class="w-full px-3 py-2 border rounded-lg"></div>
        <div class="md:col-span-3"><div class="md:col-span-3"><label class="block text-sm font-medium mb-2">Servers (comma-separated)</label><input name="servers_csv" value="<?= e(implode(', ', function_exists('settings_servers')?settings_servers():['EU','NA','SEA'])) ?>" class="w-full px-3 py-2 border rounded-lg"></div>
        <button name="save_settings" value="1" class="bg-green-600 text-white px-8 py-3 rounded-lg"><i class="fas fa-save mr-2"></i>Save Settings</button></div>
      </form>
    </div>
  <?php elseif ($tab==='system'): ?>
    <div class="bg-white rounded-2xl shadow p-6">
      <h3 class="font-bold text-xl mb-4">System Check</h3>
      <ul class="space-y-2 text-sm">
        <li>PHP: <strong><?= e(PHP_VERSION) ?></strong></li>
        <li>max_upload_size: <strong><?= e(ini_get('upload_max_filesize')) ?></strong></li>
        <li>post_max_size: <strong><?= e(ini_get('post_max_size')) ?></strong></li>
        <li>GD extension: <strong><?= extension_loaded('gd') ? 'OK' : 'MISSING' ?></strong></li>
        <li>MySQL: <strong><?php $v=db()->server_info; echo e(is_string($v)?$v:json_encode($v)); ?></strong></li>
        <li>Writable uploads/: <strong><?php $ok=is_writable(__DIR__.'/uploads'); echo $ok?'OK':'NOT WRITABLE'; ?></strong></li>
        <li>Error log path: <strong><?php echo e(__DIR__ . '/error_log'); ?></strong> (check last lines if 500 persists)</li>
      </ul>
    </div>
    
  <?php endif; ?>
</section>
<?php include __DIR__ . '/app/views/partials/footer.php'; ?>
