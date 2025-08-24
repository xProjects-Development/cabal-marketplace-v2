<?php
// SAFE settings.php (no dummy db() here). Assumes a global db() function exists from bootstrap.

/** Load platform settings row (id=1). */
function settings_load(): array {
    $res = @db()->query('SELECT * FROM settings WHERE id=1');
    if ($res && ($row = $res->fetch_assoc())) return $row;
    // sensible defaults
    return ['alz_to_eur'=>10.00,'transaction_fee'=>2.5,'maintenance_mode'=>0];
}

/** Update core settings. */
function settings_update(float $alz_to_eur, float $transaction_fee, int $maintenance_mode): bool {
    $stmt = db()->prepare('UPDATE settings SET alz_to_eur=?, transaction_fee=?, maintenance_mode=? WHERE id=1');
    $stmt->bind_param('ddi', $alz_to_eur, $transaction_fee, $maintenance_mode);
    $ok = $stmt->execute(); $stmt->close(); return (bool)$ok;
}

/** Ensure categories_json column exists. */
function settings_ensure_categories_column(): void {
    $sql = "SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='settings' AND COLUMN_NAME='categories_json'";
    $res = db()->query($sql);
    if ($res && $res->fetch_row()) { return; }
    // Add column if missing
    @db()->query('ALTER TABLE settings ADD COLUMN categories_json TEXT NULL');
    // Seed defaults if row exists
    @db()->query("UPDATE settings SET categories_json = COALESCE(categories_json, '[\"Art\",\"Music\",\"Photography\",\"Gaming\",\"Sports\",\"Collectibles\"]') WHERE id=1");
}

/** Get categories list from settings (JSON). */
function settings_categories(): array {
    settings_ensure_categories_column();
    $res = db()->query('SELECT categories_json FROM settings WHERE id=1');
    $row = $res ? $res->fetch_assoc() : null;
    $json = $row['categories_json'] ?? null;
    if ($json) {
        $arr = json_decode($json, true);
        if (is_array($arr) && $arr) {
            $out = [];
            foreach ($arr as $c) {
                $c = trim((string)$c);
                if ($c !== '') { $out[$c] = mb_strlen($c) > 24 ? mb_substr($c, 0, 24) : $c; }
            }
            return array_values($out);
        }
    }
    return ['Art','Music','Photography','Gaming','Sports','Collectibles'];
}

/** Save categories back to JSON. */
function settings_update_categories(array $cats): bool {
    settings_ensure_categories_column();
    $out = [];
    foreach ($cats as $c) {
        $c = trim((string)$c);
        if ($c !== '') { $out[$c] = mb_strlen($c) > 24 ? mb_substr($c, 0, 24) : $c; }
    }
    $json = json_encode(array_values($out));
    $stmt = db()->prepare('UPDATE settings SET categories_json=? WHERE id=1');
    $stmt->bind_param('s', $json);
    $ok = $stmt->execute(); $stmt->close(); return (bool)$ok;
}
