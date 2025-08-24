<?php
require_once __DIR__ . '/app/bootstrap.php';
require_login();
require_once __DIR__ . '/app/profile_helpers.php';

$me = current_user();
$uid = (int)$me['id'];
$errors = []; $saved = false;

if (is_post()) {
  verify_csrf();
  $ok = profile_update($uid, $_POST, $_FILES);
  if ($ok) { $saved = true; }
  else { $errors[] = 'Could not save changes.'; }
}

$db = db();
$stmt = $db->prepare("SELECT first_name,last_name,username,email,avatar_path,banner_path,bio,location,website,twitter,telegram,is_profile_public,show_email FROM users WHERE id=? LIMIT 1");
$stmt->bind_param('i', $uid); $stmt->execute(); $res=$stmt->get_result(); $u=$res->fetch_assoc(); $stmt->close();

include __DIR__ . '/app/views/partials/header.php'; ?>
<section class="max-w-4xl mx-auto px-4 py-10">
  <h1 class="text-3xl font-bold mb-6">Edit Profile</h1>
  <?php if ($errors): ?><div class="bg-red-100 text-red-800 p-3 mb-4 rounded-lg"><?php foreach ($errors as $er) echo '<div>'.e($er).'</div>'; ?></div><?php endif; ?>
  <?php if ($saved): ?><div class="bg-green-100 text-green-800 p-3 mb-4 rounded-lg">Saved!</div><?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="space-y-6 bg-white p-6 rounded-2xl shadow">
    <?= csrf_field() ?>
    <div class="flex items-center gap-4">
      <div class="w-20 h-20 rounded-full overflow-hidden bg-gray-100 flex items-center justify-center">
        <?php if (!empty($u['avatar_path'])): ?>
          <img src="<?= e($u['avatar_path']) ?>" class="w-full h-full object-cover" alt="">
        <?php else: ?>
          <span class="text-2xl font-bold"><?= e(strtoupper($u['first_name'][0] ?? $u['username'][0])) ?></span>
        <?php endif; ?>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Avatar</label>
        <input type="file" name="avatar" accept="image/*" class="block text-sm">
        <p class="text-xs text-gray-500 mt-1">PNG, JPG, GIF, WEBP. Square looks best.</p>
      </div>
    <div>
      <label class="block text-sm font-medium mb-1">Profile banner</label>
      <?php if (!empty($u['banner_path'])): ?>
        <img src="<?= e($u['banner_path']) ?>" class="w-full h-28 md:h-32 object-cover rounded mb-2" alt="Banner preview">
      <?php endif; ?>
      <input type="file" name="banner" accept="image/*" class="block text-sm">
      <p class="text-xs text-gray-500 mt-1">Recommended ~1500×300 (JPG/PNG/WEBP). Wide images look best.</p>
    </div>

    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium mb-1">First name</label>
        <input type="text" value="<?= e($u['first_name']) ?>" class="w-full border rounded px-3 py-2 bg-gray-100" disabled>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Last name</label>
        <input type="text" value="<?= e($u['last_name']) ?>" class="w-full border rounded px-3 py-2 bg-gray-100" disabled>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Username</label>
        <input type="text" value="<?= e($u['username']) ?>" class="w-full border rounded px-3 py-2 bg-gray-100" disabled>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Email</label>
        <input type="email" value="<?= e($u['email']) ?>" class="w-full border rounded px-3 py-2 bg-gray-100" disabled>
      </div>
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Bio</label>
      <textarea name="bio" rows="4" class="w-full border rounded px-3 py-2" placeholder="Tell people about yourself..."><?= e($u['bio'] ?? '') ?></textarea>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div>
        <label class="block text-sm font-medium mb-1">Location</label>
        <input type="text" name="location" value="<?= e($u['location'] ?? '') ?>" class="w-full border rounded px-3 py-2" placeholder="City, Country">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Website</label>
        <input type="url" name="website" value="<?= e($u['website'] ?? '') ?>" class="w-full border rounded px-3 py-2" placeholder="https://...">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Twitter</label>
        <input type="text" name="twitter" value="<?= e($u['twitter'] ?? '') ?>" class="w-full border rounded px-3 py-2" placeholder="@handle">
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium mb-1">Telegram</label>
        <input type="text" name="telegram" value="<?= e($u['telegram'] ?? '') ?>" class="w-full border rounded px-3 py-2" placeholder="@username">
      </div>
      <div class="flex items-center gap-6 pt-6">
        <label class="inline-flex items-center">
          <input type="checkbox" name="is_profile_public" <?= !empty($u['is_profile_public'])?'checked':'' ?> class="mr-2">
          Public profile
        </label>
        <label class="inline-flex items-center">
          <input type="checkbox" name="show_email" <?= !empty($u['show_email'])?'checked':'' ?> class="mr-2">
          Show email on profile
        </label>
      </div>
    </div>

    <div class="pt-2">
      <button class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700">Save changes</button>
      <a href="<?= e(BASE_URL) ?>/profile.php" class="ml-3 text-gray-600 hover:underline">Cancel</a>
    </div>
  </form>
</section>
<?php include __DIR__ . '/app/views/partials/footer.php'; ?>
