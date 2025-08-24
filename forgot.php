<?php require_once __DIR__ . '/app/bootstrap.php'; include __DIR__ . '/app/views/partials/header.php'; ?>
<section class="max-w-md mx-auto px-4 py-12">
  <div class="bg-white rounded-2xl shadow-2xl p-8">
    <div class="text-center mb-8">
      <div class="w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="fas fa-key text-3xl text-yellow-600"></i>
      </div>
      <h2 class="text-3xl font-bold text-gray-800 mb-2">Reset Password</h2>
      <p class="text-gray-600">Enter your email to receive reset instructions (demo)</p>
    </div>
    <form method="post" class="space-y-6">
      <?= csrf_field() ?>
      <input type="email" name="email" placeholder="Email Address" required class="w-full px-4 py-3 border-2 rounded-lg">
      <button class="w-full bg-gradient-to-r from-yellow-600 to-orange-600 text-white py-3 rounded-lg font-bold text-lg hover:from-yellow-700 hover:to-orange-700">Send Reset Link</button>
    </form>
  </div>
</section>
<?php include __DIR__ . '/app/views/partials/footer.php'; ?>
