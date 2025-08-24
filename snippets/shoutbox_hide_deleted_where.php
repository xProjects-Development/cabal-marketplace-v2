<?php
// snippets/shoutbox_hide_deleted_where.php
// Add this to your shoutbox SELECT WHERE to hide soft-deleted rows, regardless of column name.
function shoutbox_deleted_filter_sql($alias=''){
  $a = $alias ? ($alias.'.') : '';
  // Works for is_deleted/deleted/is_removed/removed/hidden if present; safe if missing.
  return " AND COALESCE({$a}is_deleted, 0)=0 AND COALESCE({$a}deleted, 0)=0 AND COALESCE({$a}is_removed, 0)=0 AND COALESCE({$a}removed,0)=0 AND COALESCE({$a}hidden,0)=0 ";
}
/* Usage example (PHP):
  $sql = "SELECT id, message FROM shout_messages WHERE 1=1 " . shoutbox_deleted_filter_sql() . " ORDER BY id DESC LIMIT 50";
*/
