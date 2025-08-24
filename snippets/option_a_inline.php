<?php
// OPTION A — Inline snippet to drop directly into the Reports loop
// Replace your existing title echo with this block:

// 1) Build a clickable subject (replace "nft 31" with a link)
$__subject = $r['subject'] ?? '';

$__subject = preg_replace_callback('~\bnft\s*#?\s*(\d+)\b~i', function ($m) {
    $id  = (int)$m[1];

    // If you **know** your detail page, hardcode it here and remove the auto-detect lines:
    // $url = "/nft.php?id={$id}";

    // Auto-detect: choose /nft.php if it exists; else fallback to marketplace
    $url = is_file(__DIR__ . '/nft.php') ? "/nft.php?id={$id}" : "/marketplace.php?nft={$id}";

    return '<a class="link" target="_blank" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">nft ' . $id . '</a>';
}, $__subject);

// 2) Print the final title line
echo '<div class="report-title">#' . (int)$r['id'] . ' — ' . $__subject . '</div>';

// 3) Optional "View" button on the right (still inside your report card row)
if (preg_match('~\bnft\s*#?\s*(\d+)\b~i', $r['subject'] ?? '', $m)) {
    $id  = (int)$m[1];
    $url = is_file(__DIR__ . '/nft.php') ? "/nft.php?id={$id}" : "/marketplace.php?nft={$id}";
    echo '<a class="btn btn-sm btn-outline" target="_blank" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">View</a>';
}
?>
