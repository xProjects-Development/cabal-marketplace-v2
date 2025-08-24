<?php require_once __DIR__ . '/app/bootstrap.php'; ?>
<?php include __DIR__ . '/app/views/partials/header.php'; ?>

<?php // Load featured data (and fallback to newest) for hero + grid ?>
<?php include __DIR__ . '/snippets/home_featured_query.php'; ?>

<!-- HERO -->
<section class="gradient-bg text-white py-16">
  <div class="max-w-7xl mx-auto px-4 grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
    <div>
      <h2 class="text-5xl font-bold mb-6 leading-tight">
        Discover, Create & Trade <span class="text-yellow-300">Nevareth items</span>
      </h2>
      <p class="text-xl mb-8 opacity-90 leading-relaxed">
        Buy, sell, and create unique Nevareth items with ALZ currency.
      </p>
      <div class="flex flex-col sm:flex-row gap-4">
        <a href="<?= e(BASE_URL) ?>/marketplace.php"
           class="bg-white text-purple-600 px-8 py-4 rounded-lg font-bold text-lg hover:bg-gray-100 transition-all transform hover:scale-105">
          <i class="fas fa-rocket mr-2"></i>Explore
        </a>
        <a href="<?= e(BASE_URL) ?>/create.php"
           class="border-2 border-white text-white px-8 py-4 rounded-lg font-bold text-lg hover:bg-white hover:text-purple-600 transition-all">
          <i class="fas fa-paint-brush mr-2"></i>Create
        </a>
      </div>
    </div>

    <!-- Two angled cards: now dynamic (featured-first, else newest) -->
    <div class="relative">
      <?php include __DIR__ . '/snippets/home_hero_cards.php'; ?>
    </div>
  </div>
</section>

<!-- Featured NFTs grid -->
<?php include __DIR__ . '/snippets/home_featured_grid.php'; ?>

<!-- Stats -->
<section class="py-16 bg-white">
  <div class="max-w-7xl mx-auto px-4 grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
    <div>
      <div class="text-4xl font-bold text-purple-600 mb-2">
        <?php echo (int)db()->query('SELECT COUNT(*) c FROM nfts')->fetch_assoc()['c']; ?>
      </div>
      <div class="text-gray-600 font-medium">Total</div>
    </div>
    <div>
      <div class="text-4xl font-bold text-blue-600 mb-2">
        <?php echo (int)db()->query('SELECT COUNT(*) c FROM users')->fetch_assoc()['c']; ?>
      </div>
      <div class="text-gray-600 font-medium">Members</div>
    </div>
    <div>
      <div class="text-4xl font-bold text-green-600 mb-2">
        <?php echo (int)db()->query('SELECT COUNT(*) c FROM nfts')->fetch_assoc()['c']; ?>
      </div>
      <div class="text-gray-600 font-medium">Listings</div>
    </div>
    <div>
      <div class="text-4xl font-bold text-pink-600 mb-2">
        ALZ→EUR <?= number_format((float)($settings['alz_to_eur'] ?? 0), 2) ?>
      </div>
      <div class="text-gray-600 font-medium">Rate</div>
    </div>
  </div>
</section>

<!-- Community / Shoutbox -->
<section class="py-16 bg-white">
  <div class="max-w-7xl mx-auto px-4 grid grid-cols-1 lg:grid-cols-2 gap-12">
    <div>
      <h2 class="text-4xl font-bold mb-6">Join Our Community</h2>
      <p class="text-xl text-gray-600 mb-8">Chat in real-time with the shoutbox.</p>
      <ul class="space-y-3 text-gray-700 list-disc pl-5">
        <li>Active community</li>
        <li>Creator-friendly platform</li>
        <li>Fast & secure</li>
      </ul>
    </div>
    <div class="bg-gray-50 rounded-2xl p-6">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-2xl font-bold">Community Shoutbox</h3>
        <div class="flex items-center space-x-2">
          <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
          <span class="text-sm text-gray-600">Live</span>
        </div>
      </div>
      <div id="shoutbox" class="max-h-80 overflow-y-auto space-y-3 mb-4 bg-white p-3 rounded-lg shadow-inner"></div>
      <div class="flex space-x-2">
        <input type="text" id="shoutInput" placeholder="Share your thoughts..."
               class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
        <button onclick="sendShout()"
                class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition-colors">
          <i class="fas fa-paper-plane"></i>
        </button>
      </div>
      <p class="text-xs text-gray-500 mt-2">You must be logged in to post.</p>
    </div>
  </div>
</section>

<?php include __DIR__ . '/app/views/partials/footer.php'; ?>

