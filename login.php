<?php require_once __DIR__ . '/app/bootstrap.php';
if (is_post()) {
  verify_csrf();
  $user = user_find_for_login($_POST['email_or_username'] ?? '');
  if ($user && password_verify($_POST['password'] ?? '', $user['password_hash'])) { login_user((int)$user['id']); redirect('/index.php'); }
  else { $_SESSION['flash_error'] = 'Invalid credentials.'; }
}
include __DIR__ . '/app/views/partials/header.php'; ?>
<section class="max-w-md mx-auto px-4 py-12">
  <div class="bg-white rounded-2xl shadow-2xl p-8">
    <div class="text-center mb-8">
      <div class="w-20 h-20 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="fas fa-sign-in-alt text-3xl text-purple-600"></i>
      </div>
      <h2 class="text-3xl font-bold text-gray-800 mb-2">Welcome Back!</h2>
      <p class="text-gray-600">Sign in to your account</p>
    </div>
    <form method="post" class="space-y-6">
      <?= csrf_field() ?>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-2">Email or Username</label>
        <input type="text" name="email_or_username" required class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-purple-500 text-lg">
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
        <input type="password" name="password" required class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-purple-500 text-lg">
      </div>
      <button class="w-full bg-gradient-to-r from-purple-600 to-pink-600 text-white py-3 rounded-lg font-bold text-lg hover:from-purple-700 hover:to-pink-700">Sign In</button>
    </form>
    <div class="mt-8 text-center">
      <p class="text-gray-600">Don't have an account? <a href="<?= e(BASE_URL) ?>/register.php" class="text-purple-600 hover:text-purple-800 font-semibold">Create one</a></p>
    </div>
  </div>
</section>
<?php include __DIR__ . '/app/views/partials/footer.php'; ?>
