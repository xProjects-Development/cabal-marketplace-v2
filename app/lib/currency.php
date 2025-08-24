<?php
// app/lib/currency.php
// Back-compatible helpers for ALZ ↔ EUR
if (!function_exists('settings_load')) {
  require_once __DIR__ . '/../bootstrap.php';
}

// Returns EUR per 1 ALZ (float)
function alz_rate_eur_per_alz(): float {
  $s = settings_load();
  $perAlz = (float)($s['alz_to_eur'] ?? 0.0);
  if ($perAlz > 0) return $perAlz;
  if (isset($s['eur_per_1b_alz'])) {
    return ((float)$s['eur_per_1b_alz']) / 1000000000.0;
  }
  return 0.0;
}

// Returns EUR for 1,000,000,000 ALZ
function alz_rate_eur_per_1b(): float {
  return alz_rate_eur_per_alz() * 1000000000.0;
}

function alz_to_eur(float $amount_alz): float {
  return $amount_alz * alz_rate_eur_per_alz();
}

function eur_to_alz(float $eur): float {
  $r = alz_rate_eur_per_alz();
  return $r > 0 ? ($eur / $r) : 0.0;
}
