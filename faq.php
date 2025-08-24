<?php
/**
 * Public FAQ — drop this in your webroot as /faq.php
 */
require_once __DIR__ . '/app/bootstrap.php';
if (!function_exists('e')) { function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); } }
require_once __DIR__ . '/app/models/faq.php';

$rows = faq_all_published();

$headerOk = @include __DIR__ . '/app/views/partials/header.php';
if (!$headerOk) {
  echo '<!doctype html><meta charset="utf-8"><script src="https://cdn.tailwindcss.com"></script><div class="p-4"></div>';
}
?>
<main class="max-w-5xl mx-auto px-4 py-10">
  <h1 class="text-3xl font-extrabold mb-6">Frequently Asked Questions</h1>
  <?php if (!$rows): ?>
    <div class="text-gray-600">No FAQs have been published yet.</div>
  <?php else: ?>
    <div class="space-y-3" id="faq">
      <?php foreach ($rows as $r): ?>
        <div class="border rounded-xl">
          <button class="w-full text-left px-4 py-3 font-semibold flex items-center justify-between faq-toggle">
            <span><?= e($r['question']) ?></span>
            <span class="text-xl">+</span>
          </button>
          <div class="px-4 pb-4 hidden faq-answer">
            <div class="text-gray-700 whitespace-pre-line"><?= e($r['answer']) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <script>
      document.querySelectorAll('#faq .faq-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
          const ans = btn.parentElement.querySelector('.faq-answer');
          ans.classList.toggle('hidden');
        });
      });
    </script>
  <?php endif; ?>
</main>
<?php
$footerOk = @include __DIR__ . '/app/views/partials/footer.php';
if (!$footerOk) { echo '</body></html>'; }
?>
