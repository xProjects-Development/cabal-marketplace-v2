<?php
/**
 * app/settings_simple.php
 * Minimal helpers that ONLY use the `settings` table.
 * - categories (TEXT CSV)
 * - servers_json (TEXT JSON)
 * - creates columns if missing
 * - reads newest non-empty row; writes to ALL rows
 * - PHP 5 + mysqli
 */
require_once __DIR__ . '/db.php';

function ss_db(){ if(function_exists('db')){ $h=@db(); if($h instanceof mysqli) return $h; } return null; }
function ss_has_col($db,$t,$c){
  $q=$db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
  if(!$q) return false; $q->bind_param("ss",$t,$c); $q->execute(); $q->bind_result($n); $q->fetch(); $q->close(); return (bool)$n;
}
function ss_ensure_schema($db){
  if(!ss_has_col($db,'settings','categories'))   { @ $db->query("ALTER TABLE `settings` ADD COLUMN `categories` TEXT NULL"); }
  if(!ss_has_col($db,'settings','servers_json')) { @ $db->query("ALTER TABLE `settings` ADD COLUMN `servers_json` TEXT NULL"); }
}
function ss_order_by_id($db){ return ss_has_col($db,'settings','id') ? " ORDER BY id DESC " : ""; }

function ss_defaults_categories_csv(){ return 'Equipment, Services, Items, Characters, Accessories, Pet Accessories, Runes, Alz, Others'; }
function ss_defaults_servers(){ return array('EU','NA','SEA'); }

function ss_csv_to_array($csv,$fallback_csv){
  $out=array(); foreach (explode(',', (string)$csv) as $x){ $x=trim($x); if($x!=='') $out[]=$x; }
  if(!$out){
    foreach (explode(',', (string)$fallback_csv) as $x){ $x=trim($x); if($x!=='') $out[]=$x; }
  }
  return $out;
}

function ss_read_categories($db){
  ss_ensure_schema($db);
  $order = ss_order_by_id($db);
  $val = null;
  $r = $db->query("SELECT `categories` AS v FROM `settings` WHERE `categories` IS NOT NULL AND `categories`<>''".$order." LIMIT 1");
  $row = $r ? $r->fetch_assoc() : null; $r && $r->close();
  $val = $row ? $row['v'] : null;
  if(!$val){
    $r = $db->query("SELECT `categories` AS v FROM `settings` ".$order." LIMIT 1");
    $row = $r ? $r->fetch_assoc() : null; $r && $r->close();
    $val = $row ? $row['v'] : null;
  }
  return ss_csv_to_array($val, ss_defaults_categories_csv());
}

function ss_read_servers($db){
  ss_ensure_schema($db);
  $order = ss_order_by_id($db);
  $val = null;
  $r = $db->query("SELECT `servers_json` AS v FROM `settings` WHERE `servers_json` IS NOT NULL AND `servers_json`<>''".$order." LIMIT 1");
  $row = $r ? $r->fetch_assoc() : null; $r && $r->close();
  $val = $row ? $row['v'] : null;
  if(!$val){
    $r = $db->query("SELECT `servers_json` AS v FROM `settings` ".$order." LIMIT 1");
    $row = $r ? $r->fetch_assoc() : null; $r && $r->close();
    $val = $row ? $row['v'] : null;
  }
  $arr = $val ? @json_decode($val, true) : null;
  if(!is_array($arr) || !$arr) $arr = ss_defaults_servers();
  return $arr;
}

function ss_write_settings($db, $categories_csv_or_array, $servers_csv_or_array){
  ss_ensure_schema($db);

  // categories -> CSV
  if(is_array($categories_csv_or_array)){
    $tmp=array(); foreach($categories_csv_or_array as $x){ $x=trim($x); if($x!=='') $tmp[]=$x; }
    $cats_csv = $tmp ? implode(', ', $tmp) : ss_defaults_categories_csv();
  } else {
    $cats_csv = implode(', ', ss_csv_to_array($categories_csv_or_array, ss_defaults_categories_csv()));
  }

  // servers -> JSON
  if(is_array($servers_csv_or_array)){
    $srv = array(); foreach($servers_csv_or_array as $x){ $x=trim($x); if($x!=='') $srv[]=$x; }
    if(!$srv) $srv = ss_defaults_servers();
  } else {
    $srv = ss_csv_to_array($servers_csv_or_array, implode(', ', ss_defaults_servers()));
  }

  $cats_csv_esc = $db->real_escape_string($cats_csv);
  $srv_json_esc = $db->real_escape_string(json_encode(array_values($srv)));

  // write to ALL rows so any LIMIT 1 read is consistent
  @ $db->query("UPDATE `settings` SET `categories`='".$cats_csv_esc."', `servers_json`='".$srv_json_esc."'");

  // if table has zero rows, insert one (ignore if other not null cols exist)
  if ($db->affected_rows === 0) {
    @ $db->query("INSERT INTO `settings` (`categories`, `servers_json`) VALUES ('".$cats_csv_esc."', '".$srv_json_esc."')");
  }
}

function ss_servers_select($name,$selected,$placeholder,$class){
  if($placeholder===null) $placeholder='All Servers';
  if($class===null) $class='form-control';
  $db = ss_db(); $servers = $db ? ss_read_servers($db) : ss_defaults_servers();
  $n = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
  $cl = htmlspecialchars($class, ENT_QUOTES, 'UTF-8');
  $h = '<select name="'.$n.'" class="'.$cl.'">';
  $h.= '<option value="">'.htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8').'</option>';
  foreach ($servers as $s){
    $sel = ($selected===$s)?' selected':'';
    $s2 = htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    $h .= '<option value="'.$s2.'"'.$sel.'>'.$s2.'</option>';
  }
  $h .= '</select>';
  return $h;
}
?>
