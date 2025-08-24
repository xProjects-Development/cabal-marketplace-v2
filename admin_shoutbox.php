<?php
// admin_shoutbox.php (v5 wrapper)
require_once __DIR__ . '/app/bootstrap.php'; admin_only();
require_once __DIR__ . '/app/admin_shoutbox_lib.php';

if (is_post() && (($_POST['action'] ?? '') === 'del')) {
  verify_csrf();
  $hard = !empty($_POST['hard']);
  adminx_shout_delete((int)$_POST['id'], $hard);
  $qs=$_GET; unset($qs['action']); $qstr=$qs?('?'.http_build_query($qs)) : '';
  header('Location: '.$_SERVER['PHP_SELF'].$qstr);
  exit;
}

$q = trim($_GET['q'] ?? ''); $p = (int)($_GET['p'] ?? 0); $limit=50;
include __DIR__ . '/app/views/partials/header.php'; ?>
<section class="max-w-5xl mx-auto px-4 py-8">
  <h1 class="text-2xl font-bold mb-4">Shoutbox</h1>
  <p class="text-sm text-gray-600 mb-2">
    Tip: tick <b>Hard</b> to permanently delete. If nothing shows, try
    <a class="underline" href="?tab=shoutbox&safe=1">Safe mode</a> or open <a class="underline" href="/shoutbox_probe.php" target="_blank">/shoutbox_probe.php</a> for diagnostics.
  </p>
  <?php adminx_shoutbox_render($q, $limit, $p); ?>
</section>
<?php include __DIR__ . '/app/views/partials/footer.php'; ?>
