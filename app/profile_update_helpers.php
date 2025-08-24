<?php
require_once __DIR__ . '/bootstrap.php';

/** Check if a column exists */
function _col_exists($table, $col){
  $t = db()->real_escape_string($table);
  $c = db()->real_escape_string($col);
  $r = db()->query("SHOW COLUMNS FROM `{$t}` LIKE '{$c}'");
  return $r && $r->num_rows > 0;
}

/**
 * Partially update the users row.
 * - Only updates keys that are set AND not empty string.
 * - Skips unknown/absent columns safely.
 * - To explicitly clear a field, pass "__CLEAR__" (or add your own checkbox flag).
 */
function user_update_partial(int $userId, array $incoming, array $allowKeys = []): bool {
  if ($userId <= 0) return false;

  // If you want to whitelist, put allowed column names in $allowKeys.
  // If empty, we allow the common set below:
  if (!$allowKeys) {
    $allowKeys = [
      'first_name','last_name','username','email',
      'bio','about','website','location',
      'twitter','instagram','discord',
      'avatar_path','avatar_url','avatar',
      'banner_path','banner_url','banner'
    ];
  }

  $sets = []; $types = ''; $vals = [];

  foreach ($allowKeys as $k) {
    if (!array_key_exists($k, $incoming)) continue;

    $v = $incoming[$k];

    // Respect explicit clear
    if ($v === '__CLEAR__') {
      if (_col_exists('users', $k)) {
        $sets[] = "`$k`=NULL";
      }
      continue;
    }

    // Skip empty strings to preserve existing DB values
    if (is_string($v) && trim($v) === '') continue;

    // Skip nulls unless you want to clear (handled above)
    if ($v === null) continue;

    if (_col_exists('users', $k)) {
      $sets[] = "`$k`=?";
      $types .= 's';
      $vals[] = $v;
    }
  }

  if (!$sets) return true; // nothing to change

  $sql = "UPDATE users SET ".implode(',', $sets)." WHERE id=?";
  $types .= 'i';
  $vals[] = $userId;

  $stmt = db()->prepare($sql);
  if (!$stmt) return false;
  $stmt->bind_param($types, ...$vals);
  $ok = $stmt->execute();
  $stmt->close();
  return $ok;
}
