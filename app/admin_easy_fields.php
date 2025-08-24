<?php
/**
 * app/admin_easy_fields.php
 * Include INSIDE the Settings <form>. Renders the two inputs.
 * Adds a hidden __aes_save=1 so the saver knows it's a settings submit.
 */
require_once __DIR__ . '/db.php';

function aes_read_csv($db){
  $csv=''; if($r=$db->query("SHOW COLUMNS FROM `settings` LIKE 'categories'")){ if($r->num_rows){ 
      $q=$db->query("SELECT `categories` AS v FROM `settings` WHERE `categories` IS NOT NULL AND `categories`<>'' ORDER BY id DESC LIMIT 1");
      $row=$q?$q->fetch_assoc():null; $q&&$q->close(); $csv=$row?$row['v']:'';
      if(!$csv){ $q=$db->query("SELECT `categories` AS v FROM `settings` ORDER BY id DESC LIMIT 1"); $row=$q?$q->fetch_assoc():null; $q&&$q->close(); $csv=$row?$row['v']:''; }
  } $r->close(); }
  if(!$csv) $csv='Equipment, Services, Items, Characters, Accessories, Pet Accessories, Runes, Alz, Others';
  return $csv;
}
function aes_read_servers_csv($db){
  $csv=''; if($r=$db->query("SHOW COLUMNS FROM `settings` LIKE 'servers_json'")){ if($r->num_rows){
      $q=$db->query("SELECT `servers_json` AS v FROM `settings` WHERE `servers_json` IS NOT NULL AND `servers_json`<>'' ORDER BY id DESC LIMIT 1");
      $row=$q?$q->fetch_assoc():null; $q&&$q->close(); $v=$row?$row['v']:'';
      if(!$v){ $q=$db->query("SELECT `servers_json` AS v FROM `settings` ORDER BY id DESC LIMIT 1"); $row=$q?$q->fetch_assoc():null; $q&&$q->close(); $v=$row?$row['v']:''; }
      $arr = $v? json_decode($v,true): null; if(is_array($arr)){ $csv=implode(', ',$arr); }
  } $r->close(); }
  if(!$csv) $csv='EU, NA, SEA';
  return $csv;
}

$db = db();
$cats_csv = $db? aes_read_csv($db): '';
$srvs_csv = $db? aes_read_servers_csv($db): '';
?>
<input type="hidden" name="__aes_save" value="1">
<div class="form-group" style="margin-top:14px;">
  <label for="categories_csv"><strong>Categories (comma-separated)</strong></label>
  <input id="categories_csv" name="categories_csv" class="form-control" value="<?= htmlspecialchars($cats_csv, ENT_QUOTES, 'UTF-8') ?>">
</div>
<div class="form-group" style="margin-top:14px;">
  <label for="servers_csv"><strong>Servers (comma-separated)</strong></label>
  <input id="servers_csv" name="servers_csv" class="form-control" value="<?= htmlspecialchars($srvs_csv, ENT_QUOTES, 'UTF-8') ?>">
</div>
