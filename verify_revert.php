<?php
header('Content-Type: text/plain; charset=utf-8');
$root = __DIR__;
$adminDir = $root . '/admin';
$currencyFile = $root . '/app/lib/currency.php';

echo "Revert checklist\n";
echo "=================\n\n";

if (is_dir($adminDir)) {
  echo "[!] Found: /admin  (delete this folder to revert)\n";
} else {
  echo "[OK] Not found: /admin\n";
}

if (file_exists($currencyFile)) {
  echo "[!] Found: /app/lib/currency.php  (delete if not used elsewhere)\n";
} else {
  echo "[OK] Not found: /app/lib/currency.php\n";
}

echo "\nAdmin entry point:\n";
if (file_exists($root . '/admin.php')) {
  echo "[OK] /admin.php exists\n";
} else {
  echo "[!] Missing /admin.php — restore from backup or the reference in this zip.\n";
}
?>