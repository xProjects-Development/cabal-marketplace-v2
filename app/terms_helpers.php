<?php
require_once __DIR__ . '/bootstrap.php';
function terms_table_has_col($t,$c){
  $t = db()->real_escape_string($t); $c = db()->real_escape_string($c);
  $r = db()->query("SHOW COLUMNS FROM `{$t}` LIKE '{$c}'"); return $r && $r->num_rows>0;
}
function terms_store_acceptance(int $user_id): bool {
  if (!terms_table_has_col('users','accepted_terms_at')) return false;
  $user_id=(int)$user_id;
  return db()->query("UPDATE `users` SET `accepted_terms_at`=NOW() WHERE `id`={$user_id} LIMIT 1")===TRUE;
}
