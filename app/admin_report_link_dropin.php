<?php
// /app/admin_report_link_dropin.php
// Prints a guaranteed "View NFT" button for an NFT report (place near your Close button).

if (!function_exists('admin_report_link_dropin')) {
    function admin_report_link_dropin(array $r): void {
        // 1) Attempt to extract NFT id from known columns
        $nftId = null;
        foreach (['target_id','nft_id','item_id','object_id'] as $k) {
            if (isset($r[$k]) && is_scalar($r[$k]) && ctype_digit((string)$r[$k])) {
                $nftId = (int)$r[$k];
                break;
            }
        }
        // 2) Parse from subject text like "nft 31" if columns not present
        if ($nftId === null) {
            $text = $r['subject'] ?? ($r['title'] ?? '');
            if ($text && preg_match('~\bnft\s*#?\s*(\d+)\b~i', $text, $m)) {
                $nftId = (int)$m[1];
            }
        }
        if ($nftId === null) return; // not an NFT report

        // 3) Build a public URL
        // If you ALWAYS use /nft.php?id=, you can hardcode:
        // $url = "/nft.php?id={$nftId}";
        $rootHasNft = @is_file(dirname(__DIR__) . '/nft.php'); // site root/nft.php
        $url = $rootHasNft ? ("/nft.php?id={$nftId}") : ("/marketplace.php?nft={$nftId}");

        // 4) Print the button (HTML not escaped again)
        echo '<a class="btn btn-sm btn-outline" target="_blank" href="'
             . htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
             . '">View NFT</a>';
    }
}
