<?php
require_once __DIR__ . '/app/bootstrap.php';

if (is_post()) {
  verify_csrf();

  $first = trim($_POST['first_name'] ?? '');
  $last = trim($_POST['last_name'] ?? '');
  $username = trim($_POST['username'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $pass = $_POST['password'] ?? '';
  $confirm = $_POST['confirm_password'] ?? '';
  $accept_terms = !empty($_POST['accept_terms']); // NEW

  // Enforce Terms checkbox first
  if (!$accept_terms) {
    $_SESSION['flash_error'] = 'You must agree to the Terms to create an account.';
  } elseif (!$first || !$last || !$username || !$email || strlen($pass) < 8 || $pass !== $confirm) {
    $_SESSION['flash_error'] = 'Please fill all fields, use a strong password (>=8), and confirm it.';
  } else {
    $res = user_create($first, $last, $username, $email, $pass);
    if ($res['ok']) {
      // Record acceptance timestamp if column exists (safe no-op otherwise)
      $helper = __DIR__ . '/app/terms_helpers.php';
      if (file_exists($helper)) {
        require_once $helper;
        if (function_exists('terms_store_acceptance')) {
          terms_store_acceptance((int)$res['id']);
        }
      } else {
        // fallback: try to set it directly (ok if column exists; ignore if not)
        @db()->query("UPDATE `users` SET `accepted_terms_at` = NOW() WHERE `id` = " . (int)$res['id'] . " LIMIT 1");
      }

      login_user((int)$res['id']);
      redirect('/index.php');
    } else {
      $_SESSION['flash_error'] = $res['error'];
    }
  }
}

include __DIR__ . '/app/views/partials/header.php';
?>
<section class="max-w-md mx-auto px-4 py-12">
  <div class="bg-white rounded-2xl shadow-2xl p-8">
    <div class="text-center mb-8">
      <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="fas fa-user-plus text-3xl text-green-600"></i>
      </div>
      <h2 class="text-3xl font-bold text-gray-800 mb-2">Join the Community!</h2>
      <p class="text-gray-600">Create your account</p>
    </div>

    <form method="post" class="space-y-4">
      <?= csrf_field() ?>
      <div class="grid grid-cols-2 gap-4">
        <input type="text" name="first_name" placeholder="First Name" required class="px-4 py-3 border-2 rounded-lg">
        <input type="text" name="last_name" placeholder="Last Name" required class="px-4 py-3 border-2 rounded-lg">
      </div>
      <input type="text" name="username" placeholder="Username" required class="w-full px-4 py-3 border-2 rounded-lg">
      <input type="email" name="email" placeholder="Email Address" required class="w-full px-4 py-3 border-2 rounded-lg">
      <input type="password" name="password" placeholder="Password (min 8 chars)" required class="w-full px-4 py-3 border-2 rounded-lg">
      <input type="password" name="confirm_password" placeholder="Confirm Password" required class="w-full px-4 py-3 border-2 rounded-lg">

      <!-- NEW: Terms checkbox -->
      <div class="mt-2">
        <label class="inline-flex items-start gap-2">
          <input type="checkbox" name="accept_terms" value="1" required class="mt-1">
          <span class="text-sm text-gray-700">
            I agree to the
            <a href="/terms.php" target="_blank" class="text-purple-600 underline">Terms of Service</a>
          </span>
        </label>
      </div>

      <button class="w-full bg-gradient-to-r from-green-600 to-blue-600 text-white py-3 rounded-lg font-bold text-lg hover:from-green-700 hover:to-blue-700">
        Create Account
      </button>
    </form>

    <div class="mt-8 text-center">
      <p class="text-gray-600">Already have an account? <a href="<?= e(BASE_URL) ?>/login.php" class="text-purple-600 hover:text-purple-800 font-semibold">Sign in</a></p>
    </div>
  </div>
</section>
<?php include __DIR__ . '/app/views/partials/footer.php'; ?>
