<?php
require_once __DIR__ . '/app/bootstrap.php';
include __DIR__ . '/app/views/partials/header.php';
?>
<section class="max-w-4xl mx-auto px-4 py-12">
  <h1 class="text-3xl font-extrabold mb-6">Terms of Service</h1>
  <p class="text-gray-600 mb-8">Last updated: <?= e(date('F j, Y')) ?></p>
  <div class="prose max-w-none">
    <h2>1. Introduction</h2>
    <p>By accessing or using this website (the “Service”), you agree to these Terms of Service (“Terms”). If you do not agree, do not use the Service.</p>
    <h2>2. Accounts</h2>
    <ul><li>Provide accurate info and keep your account secure.</li><li>You are responsible for all activity under your account.</li></ul>
    <h2>3. Listings & Transactions</h2>
    <ul><li>No illegal, infringing, or harmful content.</li><li>Rates (e.g., ALZ→EUR) may change.</li></ul>
    <h2>4. Prohibited Use</h2>
    <p>No spam, scraping, fraud, harassment, or security bypass attempts.</p>
    <h2>5. Intellectual Property</h2>
    <p>You retain rights; you grant us a license to host and display.</p>
    <h2>6. Termination</h2>
    <p>We may suspend/terminate accounts that violate these Terms.</p>
    <h2>7. Disclaimers & Liability</h2>
    <p>Service is provided “as is” without warranties; no liability for indirect damages.</p>
    <h2>8. Changes</h2>
    <p>We may update these Terms; continued use means acceptance.</p>
    <h2>9. Contact</h2>
    <p>Email <a class="underline" href="mailto:letters@<?= e($_SERVER['HTTP_HOST']) ?>">letters@<?= e($_SERVER['HTTP_HOST']) ?></a>.</p>
  </div>
</section>
<?php include __DIR__ . '/app/views/partials/footer.php'; ?>
