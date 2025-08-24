<?php
// Auto-adds a floating "Report" button on /nft.php?id=... pages.
$script = basename($_SERVER['SCRIPT_NAME'] ?? '');
if ($script === 'nft.php') {
  $nid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  if ($nid > 0) {
    $href = (defined('BASE_URL') ? BASE_URL : '') . '/report.php?type=nft&id=' . $nid;
    echo '<style>
      .report-fab{position:fixed;right:18px;bottom:18px;z-index:1000;background:#fee2e2;color:#991b1b;
        border-radius:9999px;padding:10px 14px;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,.15);cursor:pointer}
      .report-fab:hover{background:#fecaca}
      .report-fab i{margin-right:.4rem}
    </style>';
    echo '<a class="report-fab" href="'.e($href).'"><i class="fas fa-flag"></i>Report</a>';
  }
}
