<?php
// snippets/normalize_rate_save_snippet.php
// Use this when saving Admin → Settings "ALZ → EUR rate" to avoid scientific notation.
if (!function_exists('normalize_decimal_input')) {
  function normalize_decimal_input($v, $scale = 12) {
    return rtrim(rtrim(sprintf('%.' . (int)$scale . 'F', (float)$v), '0'), '.');
  }
}
// Example usage in your POST handler:
$rate_in  = $_POST['alz_to_eur'] ?? '0';
$rate_str = normalize_decimal_input($rate_in, 12);
if ((float)$rate_str <= 0) $rate_str = '0.00000001';       // guard
// Save with your existing methods:
if (function_exists('settings_update')) {
  $fee  = isset($fee) ? $fee : 0;
  $mm   = isset($mm) ? $mm : 0;
  settings_update((float)$rate_str, $fee, $mm);
}
if (function_exists('settings_write_kv')) {
  settings_write_kv('alz_to_eur', $rate_str);
}
