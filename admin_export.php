<?php
// admin_export.php — export Users or NFTs as CSV with current filters
require_once __DIR__ . '/app/bootstrap.php'; admin_only();
require_once __DIR__ . '/app/admin_upgrades.php';

$type = $_GET['type'] ?? 'users';
$now = date('Ymd_His');

if ($type === 'users') {
  $q = adminu_param('q',''); $role = adminu_param('role',''); $status = adminu_param('status','');
  adminu_csv_header("users_{$now}.csv");
  echo "id,username,email,role,status,first_name,last_name,created_at\n";
  $w = [];
  if ($q !== '') { $q = db()->real_escape_string($q); $w[]="(username LIKE '%{$q}%' OR email LIKE '%{$q}%' OR first_name LIKE '%{$q}%' OR last_name LIKE '%{$q}%')"; }
  if ($role !== '') { $r = db()->real_escape_string($role); $w[]="role='{$r}'"; }
  if ($status !== '') { $s = db()->real_escape_string($status); $w[]="status='{$s}'"; }
  $where = $w ? ('WHERE '.implode(' AND ',$w)) : '';
  $sql = "SELECT id,username,email,role,status,first_name,last_name,created_at FROM users {$where} ORDER BY id DESC";
  $res = db()->query($sql);
  while($res && ($row=$res->fetch_assoc())) {
    echo implode(',', [
      (int)$row['id'],
      adminu_csv_escape($row['username'] ?? ''),
      adminu_csv_escape($row['email'] ?? ''),
      adminu_csv_escape($row['role'] ?? ''),
      adminu_csv_escape($row['status'] ?? ''),
      adminu_csv_escape($row['first_name'] ?? ''),
      adminu_csv_escape($row['last_name'] ?? ''),
      adminu_csv_escape($row['created_at'] ?? '')
    ])."\n";
  }
  exit;
}

if ($type === 'nfts') {
  $q = adminu_param('q',''); $cat = adminu_param('cat',''); $feat = adminu_param('featured','');
  adminu_csv_header("nfts_{$now}.csv");
  echo "id,title,price_alz,category,is_featured,created_at\n";
  $w = [];
  if ($q !== '')   { $q = db()->real_escape_string($q); $w[]="(title LIKE '%{$q}%')"; }
  if ($cat !== '') { $c = db()->real_escape_string($cat); $w[]="category='{$c}'"; }
  if ($feat !== ''){ $f = (int)$feat; $w[]="is_featured={$f}"; }
  $where = $w ? ('WHERE '.implode(' AND ',$w)) : '';
  $sql = "SELECT id,title,price_alz,category,is_featured,created_at FROM nfts {$where} ORDER BY id DESC";
  $res = db()->query($sql);
  while($res && ($row=$res->fetch_assoc())) {
    echo implode(',', [
      (int)$row['id'],
      adminu_csv_escape($row['title'] ?? ''),
      adminu_csv_escape($row['price_alz'] ?? ''),
      adminu_csv_escape($row['category'] ?? ''),
      (int)($row['is_featured'] ?? 0),
      adminu_csv_escape($row['created_at'] ?? '')
    ])."\n";
  }
  exit;
}

http_response_code(400);
echo "Unknown export type.";
