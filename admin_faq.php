<?php
/**
 * Admin FAQ — drop this in your webroot as /admin_faq.php
 * Requires your existing /app/bootstrap.php and partial header/footer.
 * Uses mysqli-style db() helper from the project. Includes fallbacks.
 */

require_once __DIR__ . '/app/bootstrap.php';
if (function_exists('admin_only')) { admin_only(); }
if (!function_exists('e')) { function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); } }

require_once __DIR__ . '/app/models/faq.php'; // brings faq_* helpers and table ensure

// Small helpers that won't conflict with project if they already exist
if (!function_exists('is_post')) { function is_post(){ return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'; } }
if (!function_exists('redirect')) { function redirect($u){ header("Location: $u"); exit; } }
if (!function_exists('csrf_field')) {
  function csrf_field(){
    if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return '<input type="hidden" name="csrf" value="'.e($_SESSION['csrf']).'">';
  }
}
if (!function_exists('verify_csrf')) {
  function verify_csrf(){
    if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
    if (empty($_SESSION['csrf']) || empty($_POST['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
      http_response_code(400); die('Bad CSRF');
    }
  }
}

// Handle POST
$err = '';
if (is_post()) {
  if (function_exists('verify_csrf')) verify_csrf();

  $action = $_POST['action'] ?? '';
  if ($action === 'create') {
    $q = trim($_POST['question'] ?? '');
    $a = trim($_POST['answer'] ?? '');
    $pub = isset($_POST['is_published']) ? 1 : 0;
    if ($q === '' || $a === '') {
      $err = 'Question and answer are required.';
    } else {
      $ok = faq_create($q, $a, $pub);
      if (!$ok) $err = 'Create failed. See PHP error log.';
    }
  } elseif ($action === 'toggle') {
    $id = (int)($_POST['id'] ?? 0);
    $pub = (int)($_POST['to'] ?? 0);
    faq_publish($id, $pub);
  } elseif ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    faq_delete($id);
  } elseif ($action === 'reorder') {
    $id = (int)($_POST['id'] ?? 0);
    $dir = $_POST['dir'] ?? 'up'; // up|down
    faq_reorder($id, $dir === 'down');
  }
  redirect('/admin_faq.php');
}

$rows = faq_all(); // all rows for admin

// Header
$headerOk = @include __DIR__ . '/app/views/partials/header.php';
if (!$headerOk) {
  echo '<!doctype html><meta charset="utf-8"><link rel="stylesheet" href="https://cdn.tailwindcss.com"><div class="p-4"></div>';
}
?>
<main class="max-w-7xl mx-auto px-4 py-8">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-extrabold">Admin FAQ</h1>
    <div class="flex gap-2">
      <a href="/faq.php" class="px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200">👁️ View FAQ</a>
      <a href="/" class="px-4 py-2 rounded-lg bg-purple-600 text-white hover:bg-purple-700">← Back to site</a>
    </div>
  </div>

  <?php if ($err): ?>
    <div class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 border border-red-200">Error: <?= e($err) ?></div>
  <?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <section class="lg:col-span-2 bg-white rounded-xl shadow p-4">
      <h2 class="font-semibold mb-3">FAQ Items</h2>
      <?php if (!$rows): ?>
        <div class="text-gray-500">No FAQs yet.</div>
      <?php else: ?>
        <ul class="divide-y">
          <?php foreach ($rows as $r): ?>
            <li class="py-3 flex items-start justify-between gap-4">
              <div class="flex-1">
                <div class="font-semibold"><?= e($r['question']) ?></div>
                <div class="text-sm text-gray-600 whitespace-pre-line"><?= e($r['answer']) ?></div>
                <div class="text-xs text-gray-400 mt-1">#<?= (int)$r['id'] ?> • <?= $r['is_published'] ? 'Published' : 'Hidden' ?> • Sort: <?= (int)($r['sort_order'] ?? 0) ?></div>
              </div>
              <div class="flex flex-col items-end gap-2">
                <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="to" value="<?= $r['is_published'] ? 0 : 1 ?>"><button class="px-3 py-1 rounded text-sm <?= $r['is_published'] ? 'bg-yellow-500 text-white' : 'bg-green-600 text-white' ?>"><?= $r['is_published'] ? 'Unpublish' : 'Publish' ?></button></form>
                <div class="flex gap-1">
                  <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="reorder"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="dir" value="up"><button class="px-2 py-1 rounded bg-gray-100">↑</button></form>
                  <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="reorder"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="dir" value="down"><button class="px-2 py-1 rounded bg-gray-100">↓</button></form>
                </div>
                <form method="post" onsubmit="return confirm('Delete this FAQ?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="px-3 py-1 rounded text-sm bg-red-600 text-white">Delete</button></form>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>

    <aside class="bg-white rounded-xl shadow p-4">
      <h2 class="font-semibold mb-3">Add FAQ</h2>
      <form method="post" class="space-y-3">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create">
        <div>
          <label class="block text-sm mb-1">Question</label>
          <input name="question" class="w-full border rounded-lg px-3 py-2" required>
        </div>
        <div>
          <label class="block text-sm mb-1">Answer</label>
          <textarea name="answer" rows="6" class="w-full border rounded-lg px-3 py-2" required></textarea>
        </div>
        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_published" checked> Published</label>
        <div><button class="px-4 py-2 rounded-lg bg-emerald-600 text-white">➕ Create</button></div>
      </form>
    </aside>
  </div>
</main>
<?php
$footerOk = @include __DIR__ . '/app/views/partials/footer.php';
if (!$footerOk) { echo '</body></html>'; }
?>
