<?php
// ===== Admin helpers (compatible with older PHP/MySQL) =====

/** small util to fetch all rows as assoc even without mysqlnd */
function _adminx_fetch_all($res) {
  if (!$res) return [];
  $rows = [];
  while ($row = $res->fetch_assoc()) { $rows[] = $row; }
  return $rows;
}

function adminx_log($action, $meta = []) {
  $uid = (int)(current_user()['id'] ?? 0);
  $stmt = db()->prepare('INSERT INTO audit_log (admin_user_id, action, meta) VALUES (?,?,?)');
  $meta_json = json_encode($meta);
  $stmt->bind_param('iss', $uid, $action, $meta_json);
  @$stmt->execute();
  $stmt->close();
}

function adminx_counts() {
  $db = db();
  $users = $db->query('SELECT COUNT(*) c FROM users'); $users = $users ? ($users->fetch_assoc()['c'] ?? 0) : 0;
  $nfts  = $db->query('SELECT COUNT(*) c FROM nfts');  $nfts  = $nfts  ? ($nfts->fetch_assoc()['c']  ?? 0) : 0;
  $offers = 0;
  if ($db->query("SHOW TABLES LIKE 'offers'")->num_rows) {
    $tmp = $db->query('SELECT COUNT(*) c FROM offers'); $offers = $tmp ? ($tmp->fetch_assoc()['c'] ?? 0) : 0;
  }
  $msgs = 0;
  if ($db->query("SHOW TABLES LIKE 'messages'")->num_rows) {
    $tmp = $db->query('SELECT COUNT(*) c FROM messages'); $msgs = $tmp ? ($tmp->fetch_assoc()['c'] ?? 0) : 0;
  }
  $open_reports = 0;
  if ($db->query("SHOW TABLES LIKE 'reports'")->num_rows) {
    $tmp = $db->query("SELECT COUNT(*) c FROM reports WHERE status='open'"); $open_reports = $tmp ? ($tmp->fetch_assoc()['c'] ?? 0) : 0;
  }
  return ['users'=>$users,'nfts'=>$nfts,'offers'=>$offers,'messages'=>$msgs,'open_reports'=>$open_reports];
}

function adminx_users($q = '', $limit = 50, $offset = 0) {
  $limit = max(1, (int)$limit); $offset = max(0, (int)$offset);
  $q = trim((string)$q);
  if ($q !== '') {
    $sql = "SELECT id,username,first_name,last_name,email,role,status,created_at FROM users
            WHERE (username LIKE CONCAT('%',?,'%') OR email LIKE CONCAT('%',?,'%') OR first_name LIKE CONCAT('%',?,'%') OR last_name LIKE CONCAT('%',?,'%'))
            ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}";
    $stmt = db()->prepare($sql);
    // 4 placeholders => bind 4 variables
    $stmt->bind_param('ssss', $q,$q,$q,$q);
  } else {
    $sql = "SELECT id,username,first_name,last_name,email,role,status,created_at FROM users
            ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}";
    $stmt = db()->prepare($sql);
  }
  $stmt->execute(); $res = $stmt->get_result(); $rows = [];
  while ($row = $res->fetch_assoc()) { $rows[] = $row; }
  $stmt->close();
  return $rows;
}

function adminx_user_update_role($uid, $role) {
  $role = in_array($role, ['user','admin'], true) ? $role : 'user';
  $stmt = db()->prepare('UPDATE users SET role=? WHERE id=?');
  $stmt->bind_param('si', $role, $uid);
  $ok = $stmt->execute(); $stmt->close();
  if ($ok) adminx_log('user.role', ['uid'=>$uid,'role'=>$role]);
  return $ok;
}

function adminx_user_suspend($uid, $suspend = true) {
  $status = $suspend ? 'suspended' : 'active';
  $stmt = db()->prepare("UPDATE users SET status=? WHERE id=?");
  $stmt->bind_param('si', $status, $uid);
  $ok = $stmt->execute(); $stmt->close();
  if ($ok) adminx_log('user.'.($suspend?'suspend':'unsuspend'), ['uid'=>$uid]);
  return $ok;
}

function adminx_nfts($q = '', $limit = 50, $offset = 0) {
  $limit = max(1, (int)$limit); $offset = max(0, (int)$offset);
  $q = trim((string)$q);
  if ($q !== '') {
    $sql = "SELECT n.id,n.title,n.category,n.price_alz,n.is_featured,n.image_path,n.created_at,u.username AS creator_username
            FROM nfts n JOIN users u ON u.id = n.creator_user_id
            WHERE (n.title LIKE CONCAT('%',?,'%') OR u.username LIKE CONCAT('%',?,'%') OR n.category LIKE CONCAT('%',?,'%'))
            ORDER BY n.created_at DESC LIMIT {$limit} OFFSET {$offset}";
    $stmt = db()->prepare($sql);
    $stmt->bind_param('sss', $q,$q,$q);
  } else {
    $sql = "SELECT n.id,n.title,n.category,n.price_alz,n.is_featured,n.image_path,n.created_at,u.username AS creator_username
            FROM nfts n JOIN users u ON u.id = n.creator_user_id
            ORDER BY n.created_at DESC LIMIT {$limit} OFFSET {$offset}";
    $stmt = db()->prepare($sql);
  }
  $stmt->execute(); $res = $stmt->get_result(); $rows = _adminx_fetch_all($res); $stmt->close();
  return $rows;
}

function adminx_nft_delete($nid) {
  $stmt = db()->prepare('DELETE FROM nfts WHERE id=?'); $stmt->bind_param('i', $nid); $ok = $stmt->execute(); $stmt->close();
  if ($ok) adminx_log('nft.delete', ['nid'=>$nid]);
  return $ok;
}

function adminx_nft_feature($nid, $yes = true) {
  $v = $yes ? 1 : 0;
  $stmt = db()->prepare('UPDATE nfts SET is_featured=? WHERE id=?'); $stmt->bind_param('ii', $v, $nid); $ok = $stmt->execute(); $stmt->close();
  if ($ok) adminx_log('nft.'.($yes?'feature':'unfeature'), ['nid'=>$nid]);
  return $ok;
}

function adminx_offers($limit = 50) {
  $limit = max(1, (int)$limit);
  $db = db();
  if (!$db->query("SHOW TABLES LIKE 'offers'")->num_rows) return [];

  $has_amount = $db->query("SHOW COLUMNS FROM offers LIKE 'amount_alz'")->num_rows ? true : false;
  $has_type   = $db->query("SHOW COLUMNS FROM offers LIKE 'type'")->num_rows ? true : false;
  $has_status = $db->query("SHOW COLUMNS FROM offers LIKE 'status'")->num_rows ? true : false;

  $sel_type   = $has_type   ? "o.type" : "(CASE WHEN o.amount_alz IS NULL OR o.amount_alz=0 THEN 'buy' ELSE 'offer' END) AS type";
  $sel_amt    = $has_amount ? "o.amount_alz" : "NULL AS amount_alz";
  $sel_status = $has_status ? "o.status" : "'pending' AS status";

  $sql = "SELECT o.id, {$sel_type}, {$sel_amt}, o.created_at, {$sel_status},
                 n.id AS nft_id, n.title, u.username AS seller_username, b.username AS buyer_username
          FROM offers o
          JOIN nfts n ON n.id = o.nft_id
          JOIN users b ON b.id = o.buyer_user_id
          JOIN users u ON u.id = n.creator_user_id
          ORDER BY o.created_at DESC LIMIT {$limit}";
  $res = $db->query($sql);
  return _adminx_fetch_all($res);
}

function adminx_reports($status = 'open', $limit = 100) {
  $limit = max(1, (int)$limit);
  $db = db();
  if (!$db->query("SHOW TABLES LIKE 'reports'")->num_rows) return [];
  $status = $status === 'closed' ? 'closed' : 'open';
  $sql = "SELECT r.*, u.username AS reporter_username FROM reports r LEFT JOIN users u ON u.id=r.reporter_user_id WHERE r.status='{$status}' ORDER BY r.created_at DESC LIMIT {$limit}";
  $res = $db->query($sql);
  return _adminx_fetch_all($res);
}

function adminx_report_close($rid) {
  if (!db()->query("SHOW TABLES LIKE 'reports'")->num_rows) return false;
  $stmt = db()->prepare("UPDATE reports SET status='closed' WHERE id=?"); $stmt->bind_param('i', $rid); $ok = $stmt->execute(); $stmt->close();
  if ($ok) adminx_log('report.close', ['rid'=>$rid]);
  return $ok;
}
