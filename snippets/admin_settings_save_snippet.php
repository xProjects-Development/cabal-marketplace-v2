<?php
// snippets/admin_settings_save_snippet.php
// Drop-in replacement for your Admin → Settings POST handler.
// Ensures we don't accidentally write ALZ max into the 'alz_to_eur' column.
require_once __DIR__ . '/../app/settings_kv.php';

if (isset($_POST['save_settings'])) {
    $rate = (float)($_POST['alz_to_eur'] ?? 0);
    $fee  = (float)($_POST['transaction_fee'] ?? 0);
    $mm   = !empty($_POST['maintenance_mode']) ? 1 : 0;
    $amax = (float)($_POST['alz_max'] ?? 140000000000); // separate field!

    // Guardrails
    if ($rate <= 0)  $rate = 0.00000001;
    if ($rate > 10)  $rate = 10; // sanity limit: 1 ALZ won't be > €10
    if ($amax <= 0)  $amax = 140000000000;

    // Save the actual rate/fee/maintenance via your existing helper
    settings_update($rate, $fee, $mm);

    // Save the ALZ maximum separately in a KV table
    settings_write_kv('alz_max', (string)$amax);

    $saved = true;
}
