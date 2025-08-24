<?php
$u = current_user();
$active = basename($_SERVER['SCRIPT_NAME']);

// Safe access for window variables
$__uid = $u ? (int)$u['id'] : null;

// Load ALZ->EUR rate safely
$__alz_rate = 0.0;
if (isset($settings) && isset($settings['alz_to_eur'])) {
  $__alz_rate = (float)$settings['alz_to_eur'];
} elseif (function_exists('settings_load')) {
  $tmp = settings_load();
  if (isset($tmp['alz_to_eur'])) $__alz_rate = (float)$tmp['alz_to_eur'];
}

// Load precise converter if available
$__perAlz = null; $__perMil = null;
$__currency_loaded = @require_once __DIR__ . '/../../currency.php';
if (function_exists('alz_to_eur_precise')) {
  $__perAlz = alz_to_eur_precise(1);
  $__perMil = alz_to_eur_precise(1000000);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(APP_NAME) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/styles.css">
  <script>
    window.CSRF = "<?= e(csrf_token()) ?>";
    window.ALZ_TO_EUR = <?= json_encode($__alz_rate) ?>;
    window.BASE_URL = "<?= e(BASE_URL) ?>";
    window.MY_UID = <?= json_encode($__uid) ?>;
  </script>
</head>
<body class="bg-gray-50">
  <nav class="bg-white shadow-lg sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4">
      <div class="flex justify-between items-center py-4">
        <div class="flex items-center space-x-8">
          <a href="<?= e(BASE_URL) ?>/index.php" class="logo-text text-2xl md:text-3xl font-extrabold leading-none inline-block">CABAL Marketplace | EU, NA, BR</a>
          <div class="hidden md:flex space-x-1">
            <a href="<?= e(BASE_URL) ?>/index.php" class="nav-tab px-6 py-3 rounded-lg font-medium <?= $active==='index.php'?'active':'' ?>"><i class="fas fa-home mr-2"></i>Home</a>
            <a href="<?= e(BASE_URL) ?>/marketplace.php" class="nav-tab px-6 py-3 rounded-lg font-medium <?= $active==='marketplace.php'?'active':'' ?>"><i class="fas fa-store mr-2"></i>Marketplace</a>
            <a href="<?= e(BASE_URL) ?>/profiles.php" class="nav-tab px-6 py-3 rounded-lg font-medium <?= $active==='profiles.php'?'active':'' ?>"><i class="fas fa-users mr-2"></i>Profiles</a>
            <a href="<?= e(BASE_URL) ?>/create.php" class="nav-tab px-6 py-3 rounded-lg font-medium <?= $active==='create.php'?'active':'' ?>"><i class="fas fa-plus mr-2"></i>Create</a>
            <a href="https://discord.gg/HwXwn249HV" class="nav-tab px-6 py-3 rounded-lg font-medium <?= $active==='discord.php'?'active':'' ?>"><i class="fab fa-discord mr-2"></i>EU Discord (No RMT)</a>
            <?php if ($u): ?>
 
            <?php endif; ?>

          </div>
        </div>
        <div class="flex items-center space-x-4">
          <div class="hidden lg:flex items-center space-x-4 text-sm">
            <div class="text-center">
              <div class="font-bold text-purple-600"><?php echo (int)db()->query('SELECT COUNT(*) c FROM nfts')->fetch_assoc()['c']; ?></div>
              <div class="text-gray-500">NFTs</div>
            </div>
            <div class="text-center">
              <div class="flex items-center gap-1 px-3 py-1.5 rounded-xl">
                <span class="text-xs font-semibold uppercase text-gray-600">ALZ→EUR</span>
                <span class="font-bold text-green-600">
                  €<?= e($__perAlz !== null ? $__perAlz : number_format($__alz_rate, 8, '.', '')) ?>
                </span>
                <span class="text-[11px] text-gray-400" title="1,000,000 ALZ ≈ €<?= e($__perMil !== null ? $__perMil : number_format(1000000*$__alz_rate, 2, '.', '')) ?>">/ ALZ</span>
              </div>
            </div>
          </div>
          <?php if (!$u): ?>
            <div class="flex items-center space-x-3">
              <a href="<?= e(BASE_URL) ?>/login.php" class="text-purple-600 hover:text-purple-800 font-medium px-4 py-2 rounded-lg hover:bg-purple-50 transition-all"><i class="fas fa-sign-in-alt mr-2"></i>Login</a>
              <a href="<?= e(BASE_URL) ?>/register.php" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition-all transform hover:scale-105"><i class="fas fa-user-plus mr-2"></i>Register</a>
            </div>
          <?php else: ?>
            <?php
              // Badges for Inbox and Helpdesk
              $uc = function_exists('messages_unread_count') ? (int)messages_unread_count((int)$u['id']) : 0;
              $ticketsOpen = 0; $adminOpen = 0;
              $dbHdr = db();
              $hasTickets = $dbHdr && $dbHdr->query("SHOW TABLES LIKE 'tickets'")->num_rows > 0;
              if ($hasTickets) {
                $q1 = $dbHdr->query("SELECT COUNT(*) c FROM tickets WHERE user_id=".(int)$u['id']." AND status IN ('open','pending')");
                if ($q1) { $ticketsOpen = (int)($q1->fetch_assoc()['c'] ?? 0); }
                if (is_admin()) {
                  $q2 = $dbHdr->query("SELECT COUNT(*) c FROM tickets WHERE status IN ('open','pending')");
                  if ($q2) { $adminOpen = (int)($q2->fetch_assoc()['c'] ?? 0); }
                }
              }
            ?>
            <div class="relative">
              <button id="userMenuBtn"
                      class="flex items-center gap-3 px-2 py-1 rounded-lg hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-purple-500"
                      aria-haspopup="true" aria-expanded="false">
                <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                  <span class="text-purple-600 font-bold"><?php echo e(strtoupper($u['first_name'][0] ?? 'U')); ?></span>
                </div>
                <div class="hidden lg:block text-left">
                  <div class="font-semibold text-gray-800 leading-tight"><?php echo e($u['first_name'] . ' ' . $u['last_name']); ?></div>
                  <div class="text-xs text-gray-500 leading-tight">@<?php echo e($u['username']); ?></div>
                </div>
                <i class="fas fa-chevron-down text-gray-500 ml-1"></i>
              </button>

              <div id="userMenu"
                   class="absolute right-0 mt-2 w-64 bg-white border border-gray-200 rounded-xl shadow-lg py-2 hidden z-50"
                   role="menu" aria-labelledby="userMenuBtn">
                <a href="<?= e(BASE_URL) ?>/profile.php" class="flex items-center justify-between px-4 py-2 hover:bg-gray-50" role="menuitem">
                  <span><i class="fas fa-user mr-2 text-gray-500"></i>My Profile</span>
                </a>

                <a href="<?= e(BASE_URL) ?>/inbox.php" class="flex items-center justify-between px-4 py-2 hover:bg-gray-50" role="menuitem">
                  <span><i class="fas fa-envelope mr-2 text-gray-500"></i>Inbox</span>
                  <?php if ($uc>0): ?><span class="text-xs bg-red-600 text-white rounded-full px-2 py-0.5"><?php echo $uc; ?></span><?php endif; ?>
                </a>

                <a href="<?= e(BASE_URL) ?>/helpdesk.php" class="flex items-center justify-between px-4 py-2 hover:bg-gray-50" role="menuitem">
                  <span><i class="fas fa-life-ring mr-2 text-gray-500"></i>Helpdesk</span>
                  <?php if ($ticketsOpen>0): ?><span class="text-xs bg-purple-600 text-white rounded-full px-2 py-0.5"><?php echo $ticketsOpen; ?></span><?php endif; ?>
                </a>

                <?php if (is_admin()): ?>
                  <div class="my-2 border-t border-gray-100"></div>
                  <a href="<?= e(BASE_URL) ?>/admin.php" class="flex items-center justify-between px-4 py-2 hover:bg-gray-50" role="menuitem">
                    <span><i class="fas fa-cog mr-2 text-gray-500"></i>Admin Panel</span>
                  </a>
                <div class="my-2 border-t border-gray-100"></div>
<a href="<?= e(BASE_URL) ?>/admin_helpdesk.php" class="flex items-center justify-between px-4 py-2 hover:bg-gray-50" role="menuitem">
  <span><i class="fas fa-headset mr-2 text-gray-500"></i>Admin Helpdesk</span>
  <?php if ($adminOpen>0): ?><span class="text-xs bg-blue-600 text-white rounded-full px-2 py-0.5"><?= $adminOpen ?></span><?php endif; ?>
</a>
<a href="<?= e(BASE_URL) ?>/admin_faq.php" class="flex items-center justify-between px-4 py-2 hover:bg-gray-50" role="menuitem">
  <span><i class="fas fa-headset mr-2 text-gray-500"></i>Admin FAQ</span>
  <?php if ($adminOpen>0): ?><span class="text-xs bg-blue-600 text-white rounded-full px-2 py-0.5"><?= $adminOpen ?></span><?php endif; ?>
</a>

                <?php endif; ?>

                <div class="my-2 border-t border-gray-100"></div>
                <a href="<?= e(BASE_URL) ?>/logout.php" class="flex items-center justify-between px-4 py-2 hover:bg-gray-50 text-red-600" role="menuitem">
                  <span><i class="fas fa-sign-out-alt mr-2"></i>Logout</span>
                </a>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </nav>

  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="max-w-7xl mx_auto px-4 mt-4">
      <div class="bg-red-100 text-red-800 p-3 rounded-lg"><?php echo e($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
    </div>
  <?php endif; ?>

  <main>
<style>
  .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }

  /* New: gradient text that works cross-browser */
  .logo-text{
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    display: inline-block;
  }

  .nav-tab { transition: all 0.3s ease; }
  .nav-tab.active { background: #667eea; color: white; transform: translateY(-2px); }
  .nav-tab:hover { background: #f3f4f6; }
  .nav-tab.active:hover { background: #5a67d8; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const btn = document.getElementById('userMenuBtn');
  const menu = document.getElementById('userMenu');
  if (!btn || !menu) return;

  function openMenu()  { menu.classList.remove('hidden'); btn.setAttribute('aria-expanded','true'); }
  function closeMenu() { menu.classList.add('hidden');    btn.setAttribute('aria-expanded','false'); }

  btn.addEventListener('click', function (e) {
    e.stopPropagation();
    const isHidden = menu.classList.contains('hidden');
    isHidden ? openMenu() : closeMenu();
  });

  document.addEventListener('click', function (e) {
    if (!menu.contains(e.target) && e.target !== btn) closeMenu();
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeMenu();
  });
});
</script>