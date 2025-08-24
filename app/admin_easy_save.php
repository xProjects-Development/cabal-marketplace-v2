<?php
/**
 * app/admin_easy_save.php
 * Minimal, robust saver for Admin Settings.
 * Include ONCE near the TOP of admin.php (after bootstrap).
 * Saves when the settings form posts (looks for our hidden flag or the fields).
 */
require_once __DIR__ . '/db.php';

function aes_has_col($db,$t,$c){
  $q=$db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
  if(!$q) return false; $q->bind_param("ss",$t,$c); $q->execute(); $q->bind_result($n); $q->fetch(); $q->close(); return (bool)$n;
}
function aes_ensure($db){
  if(!aes_has_col($db,'settings','categories'))   @$db->query("ALTER TABLE `settings` ADD COLUMN `categories` TEXT NULL");
  if(!aes_has_col($db,'settings','servers_json')) @$db->query("ALTER TABLE `settings` ADD COLUMN `servers_json` TEXT NULL");
}
function aes_norm_csv($csv){ $out=array(); foreach(explode(',',(string)$csv) as $x){ $x=trim($x); if($x!=='') $out[]=$x; } return $out; }
function aes_rows_exist($db){ $r=$db->query("SELECT COUNT(*) AS c FROM `settings`"); $row=$r?$r->fetch_assoc():array('c'=>0); $r&&$r->close(); return (int)$row['c']>0; }

if ($_SERVER['REQUEST_METHOD']==='POST' &&
    (isset($_POST['__aes_save']) || isset($_POST['servers_csv']) || isset($_POST['categories_csv']) || isset($_POST['servers']) || isset($_POST['categories']))) {

  $db = db(); if(!$db) return;
  aes_ensure($db);

  // Categories
  $cats_in = isset($_POST['categories_csv']) ? $_POST['categories_csv'] : (isset($_POST['categories'])?$_POST['categories']:'');
  $cats = aes_norm_csv($cats_in);
  if(!$cats) $cats = aes_norm_csv('Equipment, Services, Items, Characters, Accessories, Pet Accessories, Runes, Alz, Others');
  $cats_csv_esc = $db->real_escape_string(implode(', ', $cats));
  @$db->query("UPDATE `settings` SET `categories`='".$cats_csv_esc."'");
  if($db->affected_rows===0 and not aes_rows_exist($db)){ @$db->query("INSERT INTO `settings` (`categories`) VALUES ('".$cats_csv_esc."')"); }

  // Servers
  $srv_in = isset($_POST['servers_csv']) ? $_POST['servers_csv'] : (isset($_POST['servers'])?$_POST['servers']:'');
  $srvs = aes_norm_csv($srv_in); if(!$srvs) $srvs = array('EU','NA','SEA');
  $srv_json_esc = $db->real_escape_string(json_encode(array_values($srvs)));
  @$db->query("UPDATE `settings` SET `servers_json`='".$srv_json_esc."'");
  if($db->affected_rows===0 and not aes_rows_exist($db)){ @$db->query("INSERT INTO `settings` (`servers_json`) VALUES ('".$srv_json_esc."')"); }
}
?>
