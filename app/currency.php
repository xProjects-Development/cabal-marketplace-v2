<?php
// app/currency.php — robust ALZ↔EUR helpers with scientific-notation safe math
require_once __DIR__ . '/settings_kv.php'; // ok if absent; _settings_get guards below

// ---- Settings getter with KV fallback ----
function _settings_get($key, $default=null){
  if (function_exists('settings_load')) {
    static $S = null; if ($S === null) $S = settings_load();
    if (is_array($S) && array_key_exists($key, $S)) return $S[$key];
  }
  if (function_exists('settings_read_kv')) {
    $v = settings_read_kv($key, null);
    if ($v !== null) return $v;
  }
  return $default;
}

// ---- Convert a number to a decimal string (no scientific notation) ----
function _decstr($x, $scale = 20) {
  if ($x === null) return '0';
  $s = is_string($x) ? $x : (string)$x;
  $s = str_replace(',', '.', $s);
  if (stripos($s, 'e') !== false) {
    // Expand scientific notation to a fixed decimal string
    $s = sprintf('%.' . (int)$scale . 'F', (float)$s);
  }
  // Tidy: trim trailing zeros/decimal
  $s = rtrim(rtrim($s, '0'), '.');
  if ($s === '' || $s === '-0') $s = '0';
  return $s;
}

// ---- Public config helpers ----
function alz_max(){
  $v = (float)_settings_get('alz_max', 140000000000);
  return $v > 0 ? $v : 140000000000;
}
function alz_rate(){
  $raw = _settings_get('alz_to_eur', 0.01);
  $s   = _decstr($raw, 12);     // normalize "1e-8" -> "0.00000001"
  $r   = (float)$s;
  if ($r <= 0) $r = 0.00000001; // guardrail
  return $r;
}

// ---- Standard 2dp conversions ----
function _money_round($x){ return round((float)$x, 2); }
function alz_to_eur($alz){ return _money_round(((float)$alz) * alz_rate()); }
function eur_to_alz($eur){ $r = alz_rate(); return $r>0 ? _money_round(((float)$eur) / $r) : 0.0; }
function fmt_eur($eur){ return number_format(_money_round($eur), 2, '.', ' '); }
function fmt_alz($alz){ return number_format(_money_round($alz), 2, '.', ' '); }

// ---- Precise display (up to 8 dp for tiny EUR amounts) ----
function _alz_to_precise($alz, $rate, $scale = 8){
  $alz  = _decstr($alz,  $scale + 4);
  $rate = _decstr($rate, $scale + 8);
  if (function_exists('bcmul')) {
    $v = bcmul($alz, $rate, $scale); // BCMath requires plain decimal strings
  } else {
    $v = number_format((float)$alz * (float)$rate, $scale, '.', '');
  }
  // tidy output: trim zeros but keep at least 2 decimals
  $v = rtrim(rtrim($v, '0'), '.');
  if (strpos($v, '.') === false) $v .= '.00';
  $parts = explode('.', $v, 2);
  if (count($parts) === 2 && strlen($parts[1]) < 2) $v .= str_repeat('0', 2 - strlen($parts[1]));
  return $v;
}
function _fmt_fiat_precise($amount){
  $f = (float)$amount;
  if ($f >= 1)      return number_format($f, 2, '.', ' ');
  if ($f >= 0.01)   return number_format($f, 4, '.', ' ');
  if ($f >= 0.0001) return number_format($f, 6, '.', ' ');
  return number_format($f, 8, '.', ' ');
}
function alz_to_eur_precise($alz, $scale = 8){
  $raw = _alz_to_precise($alz, alz_rate(), $scale);
  return _fmt_fiat_precise($raw);
}
