<?php
// app/admin_upgrades.php
// Helpers for admin panel: totals, filters, pagination utils, CSV exporters

require_once __DIR__ . '/bootstrap.php';

function adminu_param($name, $default=null) {
  return isset($_GET[$name]) ? trim($_GET[$name]) : $default;
}

function adminu_int($name, $default=1) {
  $v = isset($_GET[$name]) ? (int)$_GET[$name] : $default;
  return $v > 0 ? $v : $default;
}

function adminu_build_query(array $overrides=[]) {
  $q = $_GET;
  foreach ($overrides as $k=>$v) { if ($v === null) unset($q[$k]); else $q[$k]=$v; }
  $s = http_build_query($q);
  return $s ? ('?'.$s) : '';
}

function adminu_users_total($q='', $role='', $status='') {
  $w = []; $q = db()->real_escape_string($q);
  if ($q !== '') {
    $w[] = "(username LIKE '%{$q}%' OR email LIKE '%{$q}%' OR first_name LIKE '%{$q}%' OR last_name LIKE '%{$q}%')";
  }
  if ($role !== '')   { $role = db()->real_escape_string($role);   $w[] = "role = '{$role}'"; }
  if ($status !== '') { $status = db()->real_escape_string($status); $w[] = "status = '{$status}'"; }
  $where = $w ? ('WHERE '.implode(' AND ', $w)) : '';
  $sql = "SELECT COUNT(*) c FROM users {$where}";
  $r = db()->query($sql);
  $row = $r ? $r->fetch_assoc() : ['c'=>0];
  return (int)$row['c'];
}

function adminu_nfts_total($q='', $category='', $featured='') {
  $w = []; $q = db()->real_escape_string($q);
  if ($q !== '') {
    $w[] = "(title LIKE '%{$q}%')";
  }
  if ($category !== '') { $category = db()->real_escape_string($category); $w[] = "category = '{$category}'"; }
  if ($featured !== '') { $featured = (int)$featured; $w[] = "is_featured = {$featured}"; }
  $where = $w ? ('WHERE '.implode(' AND ', $w)) : '';
  $sql = "SELECT COUNT(*) c FROM nfts {$where}";
  $r = db()->query($sql);
  $row = $r ? $r->fetch_assoc() : ['c'=>0];
  return (int)$row['c'];
}

function adminu_pager($total, $per, $page) {
  $pages = max(1, (int)ceil($total / max(1,$per)));
  if ($pages <= 1) return;
  echo '<div class="mt-4 flex flex-wrap gap-2">';
  for ($i=1; $i<=$pages; $i++) {
    $href = adminu_build_query(['page'=>$i]);
    $cls = $i===$page ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700';
    echo '<a class="px-3 py-1.5 rounded '.$cls.'" href="'.e($href).'">'.$i.'</a>';
  }
  echo '</div>';
}

function adminu_csv_header($name) {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="'.$name.'";');
}

function adminu_csv_escape($v) {
  $v = (string)$v;
  $v = str_replace('"', '""', $v);
  return '"'.$v.'"';
}
