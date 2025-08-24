<?php
/**
 * app/site_unbreak_hotfix.php
 * Emergency non‑destructive shim to keep pages from white‑screening.
 *
 * What it does:
 * - Turns off fatal "undefined function" by providing safe stand‑ins:
 *     settings_servers()            -> returns ['EU','NA','SEA'] if DB unavailable
 *     settings_update_servers($v)   -> best effort save; returns true even if no DB
 * - If db()/mysqli exists and `settings.servers_json` is present, it reads/writes it.
 * - Does NOT replace your files; safe to include multiple times.
 */

// 1) Try to load your DB, but don't die if missing.
if (!function_exists('db')) {
  @include_once __DIR__ . '/db.php';
}

// 2) helpers
function __hf_db() {
  if (function_exists('db')) { $h=@db(); if ($h instanceof mysqli) return $h; }
  if (isset($GLOBALS['mysqli']) && $GLOBALS['mysqli'] instanceof mysqli) return $GLOBALS['mysqli'];
  if (isset($GLOBALS['conn'])   && $GLOBALS['conn']   instanceof mysqli) return $GLOBALS['conn'];
  return null;
}
function __hf_defaults(){ return array('EU','NA','SEA'); }
function __hf_has_col($db,$t,$c){
  if(!$db) return false;
  $q=@$db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
  if(!$q) return false; $q->bind_param("ss",$t,$c); $q->execute(); $q->bind_result($n); $q->fetch(); $q->close(); return (bool)$n;
}
function __hf_ensure($db){
  if(!$db) return;
  if(!__hf_has_col($db,'settings','servers_json')) { @$db->query("ALTER TABLE `settings` ADD COLUMN `servers_json` TEXT NULL"); }
}

// 3) Polyfill functions ONLY if missing
if (!function_exists('settings_servers')) {
  function settings_servers() {
    $db = __hf_db();
    if ($db) {
      __hf_ensure($db);
      if ($r=@$db->query("SELECT `servers_json` AS v FROM `settings` WHERE `servers_json` IS NOT NULL AND `servers_json`<>'' ORDER BY id DESC LIMIT 1")) {
        $row=$r->fetch_assoc(); $r->close();
        if ($row && !empty($row['v'])) {
          $arr = @json_decode($row['v'], true);
          if (is_array($arr) && $arr) return $arr;
        }
      }
    }
    return __hf_defaults();
  }
}
if (!function_exists('settings_update_servers')) {
  function settings_update_servers($servers) {
    // normalize
    if (!is_array($servers)) {
      $tmp=array(); foreach(explode(',', (string)$servers) as $x){ $x=trim($x); if($x!=='') $tmp[]=$x; } $servers=$tmp;
    } else {
      $tmp=array(); foreach($servers as $x){ $x=trim((string)$x); if($x!=='') $tmp[]=$x; } $servers=$tmp;
    }
    if (!$servers) $servers = __hf_defaults();

    $db = __hf_db();
    if ($db) {
      __hf_ensure($db);
      $json = $db->real_escape_string(json_encode(array_values($servers)));
      @ $db->query("UPDATE `settings` SET `servers_json`='".$json."'");
      if ($db->affected_rows === 0) { @ $db->query("INSERT INTO `settings` (`servers_json`) VALUES ('".$json."')"); }
    }
    return true;
  }
}
?>
