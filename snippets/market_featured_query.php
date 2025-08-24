<?php
// Prepared SELECT for marketplace list (orders Featured first; optional Featured-only filter)
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$cat = isset($_GET['cat']) ? trim($_GET['cat']) : '';
$featured_only = isset($_GET['featured']) && $_GET['featured'] == '1';

$where = []; $types=''; $params=[];
if ($q !== '') { $where[] = "title LIKE ?"; $types.='s'; $params[] = "%{$q}%"; }
if ($cat !== '') { $where[] = "category = ?"; $types.='s'; $params[] = $cat; }
if ($featured_only) { $where[] = "is_featured = 1"; }

$whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';
$sql = "SELECT id, title, image_path, price_alz, category, is_featured, created_at, creator_username
          FROM nfts
          {$whereSql}
         ORDER BY is_featured DESC, created_at DESC
         LIMIT ? OFFSET ?";
$types.='ii'; $params[] = $limit; $params[] = $offset;

$stmt = db()->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$nfts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
