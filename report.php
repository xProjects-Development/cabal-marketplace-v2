<?php
require_once __DIR__ . '/app/bootstrap.php';
require_login();
$type = strtolower(trim($_GET['type'] ?? ''));
$id = (int)($_GET['id'] ?? 0);
$allowed = ['nft','user','message','offer'];
if (!in_array($type, $allowed, true) || !$id) {
  http_response_code(400);
  die('Bad request.');
}

$errors = []; $success = false;
if (is_post()) {
  verify_csrf();
  $reason = trim((string)($_POST['reason'] ?? ''));
  if ($reason === '' || mb_strlen($reason) < 5) $errors[] = 'Please describe the issue (at least 5 characters).';
  if (!$errors) {
    $stmt = db()->prepare('INSERT INTO reports (target_type, target_id, reporter_user_id, reason) VALUES (?,?,?,?)');
    $uid = (int)current_user()['id'];
    $stmt->bind_param('siis', $type, $id, $uid, $reason);
    $ok = $stmt->execute();
    $stmt->close();
    if ($ok) $success = true; else $errors[] = 'Failed to submit report. Try again.';
  }
}

include __DIR__ . '/app/views/partials/header.php'; ?>
<section class="max-w-xl mx-auto px-4 py-10">
  <h1 class="text-3xl font-bold mb-2">Report <?= e(strtoupper($type)) ?> #<?= (int)$id ?></h1>
  <p class="text-gray-600 mb-6">Tell us what’s wrong. An admin will review it.</p>

  <?php if ($errors): ?><div class="bg-red-100 text-red-800 p-3 mb-4 rounded-lg"><?php foreach ($errors as $er) echo '<div>'.e($er).'</div>'; ?></div><?php endif; ?>
  <?php if ($success): ?><div class="bg-green-100 text-green-800 p-3 mb-4 rounded-lg">Report sent. Thank you!</div><?php endif; ?>

  <form method="post" class="bg-white p-6 rounded-2xl shadow space-y-4">
    <?= csrf_field() ?>
    <input type="hidden" name="type" value="<?= e($type) ?>">
    <input type="hidden" name="id" value="<?= (int)$id ?>">
    <label class="block text-sm font-semibold">Reason</label>
    <textarea name="reason" rows="4" class="w-full border rounded px-3 py-2" placeholder="Spam, fraud, offensive content, etc..." required></textarea>
    <div class="text-right">
      <a href="javascript:history.back()" class="mr-3 text-gray-600 hover:underline">Cancel</a>
      <button class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700">Send report</button>
    </div>
  </form>

  <p class="text-xs text-gray-500 mt-4">Abuse of this feature may lead to account action.</p>
</section>
<?php include __DIR__ . '/app/views/partials/footer.php'; ?>
