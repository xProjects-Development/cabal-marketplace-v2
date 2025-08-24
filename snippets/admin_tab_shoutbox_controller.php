<?php
// snippets/admin_tab_shoutbox_controller.php
// Put this inside your admin.php router/switch for ?tab=shoutbox
require_once __DIR__ . '/app/admin_shoutbox_lib.php';
admin_only();

if (is_post() && (($_POST['action'] ?? '') === 'del')) {
  verify_csrf();
  $hard = !empty($_POST['hard']);
  adminx_shout_delete((int)$_POST['id'], $hard);
  // Redirect to avoid resubmission
  $qs = $_GET; unset($qs['action']); $qstr = $qs ? ('?'.http_build_query($qs)) : '';
  header('Location: admin.php'.$qstr);
  exit;
}

$q = trim($_GET['q'] ?? '');
$p = (int)($_GET['p'] ?? 0);

// Optional heading; remove if your template already prints one.
echo '<h2 class="text-xl font-bold mb-3">Shoutbox</h2>';
echo '<p class="text-sm text-gray-600 mb-2">Tip: tick <b>Hard</b> to permanently delete. ';
echo 'If nothing shows, try <a class="underline" href="admin.php?tab=shoutbox&safe=1">Safe mode</a>.</p>';

adminx_shoutbox_render($q, 50, $p);
