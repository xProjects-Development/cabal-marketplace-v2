<?php
require_once __DIR__ . '/app/bootstrap.php';
admin_only();

// Optional: show PHP errors to admin while we wire this up
ini_set('display_errors','1'); error_reporting(E_ALL);

$db = db();

/** --- utilities --------------------------------------------------------- */
function table_exists($name){
  $esc = db()->real_escape_string($name);
  $q = db()->query("SHOW TABLES LIKE '{$esc}'");
  return $q && $q->num_rows > 0;
}
function pick_ticket_table(){
  $candidates = ['tickets','helpdesk_tickets','support_tickets'];
  $best = null; $bestRows = -1;
  foreach ($candidates as $t){
    if (!table_exists($t)) continue;
    $r = db()->query("SELECT COUNT(*) c FROM `{$t}`");
    $rows = $r ? (int)($r->fetch_assoc()['c'] ?? 0) : 0;
    if ($rows > $bestRows) { $best = $t; $bestRows = $rows; }
  }
  return [$best, $bestRows];
}
function get_columns($table){
  $cols = [];
  $q = db()->query("SHOW COLUMNS FROM `{$table}`");
  while($q && ($r = $q->fetch_assoc())){
    $cols[] = $r['Field'];
  }
  return $cols;
}
function col_has($cols, $name){ return in_array($name, $cols, true); }
function first_col($cols, $names){
  foreach ($names as $n) if (col_has($cols,$n)) return $n;
  return null;
}
function normalize_status($s){
  $m = [
    'new' => 'open',
    'in_progress' => 'pending',
    'in-progress'  => 'pending',
    'progress'     => 'pending',
    'solved'       => 'resolved',
    'resolve'      => 'resolved',
  ];
  $s = strtolower(trim($s));
  return $m[$s] ?? $s;
}

/** --- detect table & columns ------------------------------------------- */
list($T, $rowCount) = pick_ticket_table();
if (!$T){
  include __DIR__ . '/app/views/partials/header.php';
  echo '<section class="max-w-5xl mx-auto px-4 py-12">';
  echo '<h1 class="text-3xl font-bold mb-4">Helpdesk — Admin</h1>';
  echo '<div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded">';
  echo '<p><strong>No tickets table found.</strong> Create one of <code>tickets</code>, <code>helpdesk_tickets</code>, or <code>support_tickets</code>.</p>';
  echo '<pre class="mt-3 text-sm">CREATE TABLE tickets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  subject VARCHAR(255) NOT NULL,
  status VARCHAR(32) NOT NULL DEFAULT "open",
  priority VARCHAR(32) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);</pre>';
  echo '</div></section>';
  include __DIR__ . '/app/views/partials/footer.php';
  exit;
}
$cols = get_columns($T);

// map logical fields to actual column names
$C = [
  'id'       => first_col($cols, ['id','ticket_id']),
  'user_id'  => first_col($cols, ['user_id','reporter_user_id','owner_id']),
  'subject'  => first_col($cols, ['subject','title','topic']),
  'status'   => first_col($cols, ['status','state','stage']),
  'priority' => first_col($cols, ['priority','severity']),
  'created'  => first_col($cols, ['created_at','created','added_at','date_created']),
  'updated'  => first_col($cols, ['updated_at','updated','modified_at']),
];

// sanitise inputs
$status = isset($_GET['status']) ? strtolower(trim($_GET['status'])) : 'all';
$status = normalize_status($status);
$q = trim($_GET['q'] ?? '');

// WHERE builder
$where = '1';
$types = ''; $bind = [];

// filter by status (if column exists and not "all")
if ($C['status'] && $status !== 'all') {
  $like = '%' . $status . '%';
  $where .= " AND LOWER(`{$C['status']}`) LIKE ?";
  $types .= 's'; $bind[] = $like;
}

// search (subject/id/user)
if ($q !== '') {
  $parts = [];
  if ($C['subject']) { $parts[] = "`{$C['subject']}` LIKE ?"; $types.='s'; $bind[] = "%{$q}%"; }
  if ($C['id'] && ctype_digit($q)) { $parts[] = "`{$C['id']}` = ?"; $types.='i'; $bind[] = (int)$q; }
  if ($C['user_id']) {
    $parts[] = "`{$C['user_id']}` IN (SELECT id FROM users WHERE username LIKE ? OR email LIKE ? OR CONCAT(first_name,' ',last_name) LIKE ?)";
    $types .= 'sss'; $bind[]="%{$q}%"; $bind[]="%{$q}%"; $bind[]="%{$q}%";
  }
  if ($parts) $where .= " AND (" . implode(' OR ', $parts) . ")";
}

$order = $C['updated'] ?: ($C['created'] ?: $C['id']);
$sql = "SELECT * FROM `{$T}` WHERE {$where} ORDER BY `{$order}` DESC LIMIT 200";
$stmt = $db->prepare($sql);
if ($types) $stmt->bind_param($types, ...$bind);
$stmt->execute();
$res  = $stmt->get_result();
$rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

// fetch reporter usernames
$usernames = [];
if ($C['user_id'] && $rows){
  $ids = [];
  foreach ($rows as $r) { $uid = (int)($r[$C['user_id']] ?? 0); if ($uid>0) $ids[$uid]=1; }
  if ($ids){
    $in = implode(',', array_map('intval', array_keys($ids)));
    $uQ = $db->query("SELECT id, username, first_name, last_name FROM users WHERE id IN ({$in})");
    while($uQ && ($u = $uQ->fetch_assoc())){
      $usernames[(int)$u['id']] = [
        'username' => $u['username'],
        'name'     => trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''))
      ];
    }
  }
}

// quick status counts
function count_like($T,$col,$want){
  if (!$col) return 0;
  $want = normalize_status($want);
  $like = '%' . $want . '%';
  $st = db()->prepare("SELECT COUNT(*) c FROM `{$T}` WHERE LOWER(`{$col}`) LIKE ?");
  $st->bind_param('s', $like);
  $st->execute();
  $r = $st->get_result()->fetch_assoc();
  $st->close();
  return (int)($r['c'] ?? 0);
}
$counts = [
  'all'      => $rowCount,
  'open'     => count_like($T, $C['status'], 'open'),
  'pending'  => count_like($T, $C['status'], 'pending'),
  'resolved' => count_like($T, $C['status'], 'resolved'),
  'closed'   => count_like($T, $C['status'], 'closed'),
];

include __DIR__ . '/app/views/partials/header.php';
?>
<section class="max-w-7xl mx-auto px-4 py-10">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-3xl font-bold">Helpdesk — Admin</h1>
    <form method="get" class="flex items-center gap-2">
      <input type="hidden" name="status" value="<?= e($status) ?>">
      <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search subject / user / #id…" class="px-4 py-2 border rounded-lg w-72">
      <button class="bg-gray-800 text-white px-4 py-2 rounded-lg">Search</button>
    </form>
  </div>

  <!-- Filters -->
  <div class="flex items-center gap-2 mb-5 text-sm">
    <?php
      $filters = [
        'all'      => 'All',
        'open'     => 'Open',
        'pending'  => 'Pending',
        'resolved' => 'Resolved',
        'closed'   => 'Closed',
      ];
      foreach ($filters as $k=>$label):
        $active = ($status === $k);
    ?>
      <a href="?status=<?= e($k) ?>&q=<?= urlencode($q) ?>"
         class="px-3 py-1.5 rounded-full border <?= $active?'bg-purple-600 text-white border-purple-600':'bg-white hover:bg-gray-50' ?>">
        <?= e($label) ?> (<?= (int)$counts[$k] ?>)
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Diagnostics (only for admins) -->
  <div class="mb-4 text-xs text-gray-600">
    <div class="bg-gray-50 border border-gray-200 rounded p-3">
      <div><strong>Using table:</strong> <code><?= e($T) ?></code> • <strong>Rows:</strong> <?= (int)$rowCount ?></div>
      <div class="mt-1"><strong>Columns:</strong>
        id=<code><?= e($C['id']??'-') ?></code>,
        user_id=<code><?= e($C['user_id']??'-') ?></code>,
        subject=<code><?= e($C['subject']??'-') ?></code>,
        status=<code><?= e($C['status']??'-') ?></code>,
        priority=<code><?= e($C['priority']??'-') ?></code>,
        created=<code><?= e($C['created']??'-') ?></code>,
        updated=<code><?= e($C['updated']??'-') ?></code>
      </div>
    </div>
  </div>

  <?php if (!$rows): ?>
    <div class="bg-white rounded-xl shadow p-8 text-center text-gray-600">
      No tickets match your filter/search.
    </div>
  <?php else: ?>
    <div class="bg-white rounded-xl shadow divide-y">
      <?php foreach ($rows as $r):
        $tid   = (int)($r[$C['id']] ?? 0);
        $subj  = (string)($C['subject'] ? ($r[$C['subject']] ?? '') : ('#'.$tid));
        $st    = strtolower((string)($C['status'] ? ($r[$C['status']] ?? '') : ''));
        $stN   = normalize_status($st);
        $prio  = (string)($C['priority'] ? ($r[$C['priority']] ?? '') : '');
        $when  = (string)($C['updated'] ? ($r[$C['updated']] ?? '') : ($C['created'] ? ($r[$C['created']] ?? '') : ''));
        $uid   = (int)($C['user_id'] ? ($r[$C['user_id']] ?? 0) : 0);
        $uname = $uid && isset($usernames[$uid]) ? ('@'.$usernames[$uid]['username']) : '—';
      ?>
        <div class="p-4 flex items-center gap-4">
          <div class="w-16 text-xs text-gray-500">#<?= $tid ?></div>
          <div class="flex-1">
            <div class="font-semibold"><?= e($subj) ?></div>
            <div class="text-xs text-gray-500">Reporter: <?= e($uname) ?></div>
          </div>
          <div class="text-xs">
            <?php if ($prio): ?><span class="px-2 py-0.5 rounded-full bg-gray-100 mr-2"><?= e($prio) ?></span><?php endif; ?>
            <span class="px-2 py-0.5 rounded-full <?= $stN==='open'?'bg-green-100 text-green-700':($stN==='pending'?'bg-yellow-100 text-yellow-700':($stN==='resolved'?'bg-blue-100 text-blue-700':'bg-gray-100 text-gray-700')) ?>">
              <?= e(ucfirst($stN ?: $st)) ?>
            </span>
          </div>
          <div class="w-44 text-right text-xs text-gray-500"><?= e($when ? date('M j, Y H:i', strtotime($when)) : '') ?></div>
          <a href="helpdesk_view.php?id=<?= $tid ?>" class="ml-2 text-purple-600 hover:text-purple-800 text-sm">Open</a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/app/views/partials/footer.php'; ?>
