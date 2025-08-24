  <?php include __DIR__ . '/cookie_banner.php'; ?>
  <?php include __DIR__ . '/report_injector.php'; ?>
  </main>
  <div id="notifications" class="fixed top-20 right-4 z-50 space-y-2"></div>
  <script src="<?= e(BASE_URL) ?>/assets/app.js"></script>
  <style>
    .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .nav-tab { transition: all 0.3s ease; }
    .nav-tab.active { background: #667eea; color: white; transform: translateY(-2px); }
    .nav-tab:hover { background: #f3f4f6; }
    .nav-tab.active:hover { background: #5a67d8; }
  </style>
  <?php
// Auto "Report" button on nft.php
$script = basename($_SERVER['SCRIPT_NAME'] ?? '');
if ($script === 'nft.php') {
  $nid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  if ($nid > 0) {
    $href = (defined('BASE_URL') ? BASE_URL : '') . '/report.php?type=nft&id=' . $nid;
    ?>
    <style>
      .report-fab{position:fixed;right:18px;bottom:18px;z-index:1000;background:#fee2e2;color:#991b1b;
        border-radius:9999px;padding:10px 14px;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,.15);cursor:pointer}
      .report-fab:hover{background:#fecaca}
      .report-fab i{margin-right:.4rem}
    </style>
    <a class="report-fab" href="<?= e($href) ?>"><i class="fas fa-flag"></i>Report</a>
    <?php
  }
}
?>
<footer class="bg-gray-800 text-white py-6 mt-12">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex flex-col md:flex-row justify-between items-center">
            <!-- Logo or Branding -->
            <div class="mb-4 md:mb-0">
                <a href="/" class="text-2xl font-bold text-white">CABAL Marketplace | EU, NA, BR</a>
            </div>
            
            <!-- Links Section -->
            <div class="flex gap-8 mb-4 md:mb-0">
                <a href="../about.php" class="text-gray-400 hover:text-white">About</a>
                <a href="../contact.php" class="text-gray-400 hover:text-white">Contact</a>
                <a href="../terms.php" class="text-gray-400 hover:text-white">Privacy Policy</a>
                <a href="../terms.php" class="text-gray-400 hover:text-white">Terms of Service</a>
                <a href="../cookies.php" class="text-gray-400 hover:text-white">Cookies</a>
                <a href="../faq.php" class="text-gray-400 hover:text-white">FAQ</a>
            </div>

            <!-- Social Media Section -->
            <div class="flex gap-6">
                <a href="https://twitter.com" class="text-gray-400 hover:text-white" target="_blank">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="https://facebook.com" class="text-gray-400 hover:text-white" target="_blank">
                    <i class="fab fa-facebook"></i>
                </a>
                <a href="https://instagram.com" class="text-gray-400 hover:text-white" target="_blank">
                    <i class="fab fa-instagram"></i>
                </a>
                <a href="https://discord.com" class="text-gray-400 hover:text-white" target="_blank">
                    <i class="fab fa-discord"></i>
                </a>
            </div>
        </div>
        <div class="mt-6 border-t border-gray-700 pt-6 text-center text-gray-400">
            <p>&copy; <?= date('Y') ?> CABAL Marketplace | EU, NA, BR. All rights reserved.</p>
        </div>
    </div>
</footer>

</body>
</html>
