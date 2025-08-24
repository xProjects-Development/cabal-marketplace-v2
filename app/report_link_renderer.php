<?php
// /app/report_link_renderer.php
// Renders a report title with clickable NFT link when possible, plus a "View" button.

require_once __DIR__ . '/admin_links.php';

if (!function_exists('admin_report__extract_nft_id')) {
    function admin_report__extract_nft_id(array $r): ?int {
        // Prefer explicit numeric columns if present
        foreach (['target_id','nft_id','item_id','object_id'] as $k) {
            if (isset($r[$k]) && is_scalar($r[$k]) && ctype_digit((string)$r[$k])) {
                return (int)$r[$k];
            }
        }
        // Parse from subject text like "nft 31"
        foreach (['subject','title'] as $key) {
            if (!empty($r[$key]) && is_string($r[$key])) {
                if (preg_match('~\bnft\s*#?\s*(\d+)\b~i', $r[$key], $m)) {
                    return (int)$m[1];
                }
            }
        }
        return null;
    }
}

if (!function_exists('admin_report_title_html')) {
    function admin_report_title_html(array $r): string {
        $rid = isset($r['id']) ? (int)$r['id'] : 0;
        $nftId = admin_report__extract_nft_id($r);
        if ($nftId !== null) {
            $url = admin_frontend_url_for_nft($nftId);
            $link = '<a class="link" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" target="_blank">nft ' . $nftId . '</a>';
            return '#' . $rid . ' — ' . $link;
        }
        $subject = $r['subject'] ?? ($r['title'] ?? 'report');
        return '#' . $rid . ' — ' . htmlspecialchars((string)$subject, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('admin_report_view_button')) {
    function admin_report_view_button(array $r): string {
        $nftId = admin_report__extract_nft_id($r);
        if ($nftId === null) return '';
        $url = admin_frontend_url_for_nft($nftId);
        return '<a class="btn btn-sm btn-outline" target="_blank" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">View</a>';
    }
}
