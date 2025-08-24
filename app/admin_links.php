<?php
// /app/admin_links.php
// Helper to build a frontend URL for an NFT id without hardcoding a single route.

if (!function_exists('admin_frontend_url_for_nft')) {
    function admin_frontend_url_for_nft(int $id): string {
        // Try to detect project root so we can test for existing frontend files
        $root = rtrim($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__), '/');

        // First match wins
        $candidates = [
            '/nft.php?id=%d',
            '/view_nft.php?id=%d',
            '/item.php?id=%d',
            '/marketplace_item.php?id=%d',
        ];

        foreach ($candidates as $fmt) {
            $path = strtok($fmt, '?'); // strip query for file_exists check
            if (@is_file($root . $path)) {
                return sprintf($fmt, $id);
            }
        }

        // Fallback: marketplace can optionally highlight the item by id
        return '/marketplace.php?nft=' . urlencode((string)$id);
    }
}
