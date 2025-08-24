<?php
// ---- Inline patch for Admin → Reports (make "nft 31" clickable) ----
function _adm_guess_nft_id(array $r): ?int {
    foreach (['target_id', 'nft_id', 'item_id', 'object_id'] as $k) {
        if (isset($r[$k]) && is_scalar($r[$k]) && ctype_digit((string)$r[$k])) {
            return (int)$r[$k];
        }
    }
    // parse from subject like "nft 31"
    $text = $r['subject'] ?? ($r['title'] ?? '');
    if ($text && preg_match('~\bnft\s*#?\s*(\d+)\b~i', $text, $m)) {
        return (int)$m[1];
    }
    return null;
}

$rid   = isset($r['id']) ? (int)$r['id'] : 0;
$nftId = _adm_guess_nft_id($r);

if ($nftId !== null) {
    // If you ALWAYS use /nft.php?id=, you can hardcode this line:
    // $url = "/nft.php?id={$nftId}";
    $url = is_file(__DIR__ . '/nft.php') ? "/nft.php?id={$nftId}" : "/marketplace.php?nft={$nftId}";
    $titleHtml = '#' . $rid . ' — ' .
        '<a class="link" target="_blank" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">nft ' . $nftId . '</a>';
} else {
    $subject = $r['subject'] ?? ($r['title'] ?? 'report');
    $titleHtml = '#' . $rid . ' — ' . htmlspecialchars((string)$subject, ENT_QUOTES, 'UTF-8');
}
?>

<!-- IMPORTANT: do NOT wrap $titleHtml in htmlspecialchars again -->
<div class="report-title"><?= $titleHtml ?></div>

<?php if ($nftId !== null): ?>
  <a class="btn btn-sm btn-outline" target="_blank"
     href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>">View</a>
<?php endif; ?>
