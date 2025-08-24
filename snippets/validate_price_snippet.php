<?php
// snippets/validate_price_snippet.php
require_once __DIR__ . '/../app/currency.php';
$price_alz = (float)($_POST['price_alz'] ?? 0);
$price_alz = round(max(0, $price_alz), 2);
if ($price_alz > alz_max()) {
  $errors[] = 'Price exceeds maximum allowed (' . fmt_alz(alz_max()) . ' ALZ).';
}
if ($price_alz > 0 && $price_alz < 0.01) {
  $errors[] = 'Minimum price is 0.01 ALZ or 0.';
}
// If no errors, proceed to save $price_alz
