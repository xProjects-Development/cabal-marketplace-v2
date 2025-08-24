<?php
require_once __DIR__ . '/app/bootstrap.php';
admin_only();

$db = db();
$now = date('Y-m-d H:i:s');
$me = current_user();
$admin_id = (int)$me['id'];

/** Resolve messages table name */
function tickets_messages_table(){
  $db = db();
  if ($db->query("SHOW TABLES LIKE 'ticket_messages'")->num_rows) return 'ticket_messages';
  if ($db->query("SHOW TABLES LIKE 'ticket_replies'")->num_rows) return 'ticket_replies'; // fallback
  return null;
}
$msgTable = tickets_messages_table();

/** Handle POST: add reply or change status */
if (is_post()) {
  verify_csrf();
  $tid = (int)($_POST['ticket_id'] ?? 0);
  if ($tid > 0) {
    // change status
    if (isset($_POST['set_status'])) {
      $new = $_POST['set_status'];
      if (!in_array($new, ['open','pending','resolved','closed'], true)) $new = 'pending';
      $stmt = $db->prepare("UPDATE tickets SET status=?, updated_at=NOW() WHERE id=?");
      $stmt->bind_param('si', $new, $tid);
      $stmt->execute(); $stmt->close();
      $_SESSION['flash_ok'] = "Ticket #{$tid} → {$new}";
    }
    // add reply (if table exists)
    if ($msgTable && isset($_POST['reply']) && trim($_POST['reply']) !== '') {
      $body = trim($_POST['reply']);
      // Try columns: (ticket_id, user_id, body, created_at) or common variants
      // Inspect columns once
      static $colsChecked = null;
      if ($colsChecked === null) {
        $colsChecked = [];
        $res = $db->query("SHOW COLUMNS FROM `{$msgTable}`");
        while ($res && ($c = $res->fetch_assoc())) { $colsChecked[$c['Field']] = true; }
      }
      if (!empty($colsChecked['ticket_id']) && !empty($colsChecked['body'])) {
        $fields = "ticket_id, body";
        $place  = "?, ?";
        $types  = "is";
        $vals   = [$tid, $body];

        if (!empty($colsChecked['user_id'])) { $fields .= ", user_id"; $place .= ", ?"; $types .= "i"; $vals[] = $admin_id; }
        if (!empty($colsChecked['sender_user_id'])) { $fields .= ", sender_user_id"; $place .= ", ?"; $types .= "i"; $vals[] = $admin_id; }
        if (!empty($colsChecked['is_admin'])) { $fields .= ", is_admin"; $place .= ", ?"; $types .= "i"; $vals[] = 1; }
        if (!empty($colsChecked['created_at'])) { $fields .= ", created_at"; $place .= ", NOW()"; /* no bind */ }
        if (!empty($colsChecked['updated_at'])) { $fields .= ", updated_at"; $place .= ", NOW()"; /* no bind */ }

        $sql = "INSERT INTO `{$msgTable}` ($fields) VALUES ($place)";
        $stmt = $db->prepare($sql);
        // build dynamic bind
        $bindVals = [];
        foreach ($vals as $v) { $bindVals[] = $v; }
        $stmt->bind_param($types, ...$bindVals);
        $stmt->execute(); $stmt->close();

        // bump ticket updated_at
        $db->query("UPDATE tickets SET updated_at=NOW(), status=IF(status='resolved' OR status='closed','pending',status) WHERE id={$tid}");
        $_SESSION['flash_ok'] = "Reply posted.";
      }
    }
  }
  // redirect back to avoid re-posting
  $to = '/admin_tickets.php' . (isset($_POST['ticket_id']) ? ('?id='.(int)$_POST['ticket_id']) : '');
  redirect($to);
  exit;
}

// Filters (list view)
$view_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$status  = $_GET['status'] ?? '';
if (!in_array($status, ['','open','pending','resolved','closed'], true)) $status = '';
$q       = trim($_GET['q'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$limit   = 30;
$offset  = ($page - 1) * $limit;

// Fetch counts per status
$counts = ['open'=>0,'pending'=>0,'resolved'=>0,'closed'=>0,'all'=>0];
$crs = $db->query("SELECT status, COUNT(*) c FROM tickets GROUP BY status");
while ($crs && ($row = $crs->fetch_assoc())) { $counts[$row['status']] = (int)$row['c']; $counts['all'] += (int)$row['c']; }

include __DIR__ . '/app/views/partials/header.php'; ?>
<section class="max-w-7xl mx-auto px-4 py-10">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-3xl font-extrabold">Helpdesk — Admin</h1>
    <form class="flex items-center gap-2" method="get" action="admin_tickets.php">
      <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search subject/user..." class="px-4 py-2 border rounded-lg w-64">
      <select name="status" class="px-3 py-2 border rounded-lg">
        <option value="" <?= $status===''?'selected':'' ?>>All (<?= (int)$counts['all'] ?>)</option>
        <option value="open" <?= $status==='open'?'selected':'' ?>>Open (<?= (int)$counts['open'] ?>)</option>
        <option value="pending" <?= $status==='pending'?'selected':'' ?>>Pending (<?= (int)$counts['pending'] ?>)</option>
        <option value="resolved" <?= $status==='resolved'?'selected':'' ?>>Resolved (<?= (int)$counts['resolved'] ?>)</option>
        <option value="closed" <?= $status==='closed'?'selected':'' ?>>Closed (<?= (int)$counts['closed'] ?>)</option>
      </select>
      <button class="bg-gray-800 text-white px-4 py-2 rounded-lg">Filter</button>
    </form>
  </div>

  <?php if (!$msgTable): ?>
    <div class="bg-yellow-100 text-yellow-900 p-3 rounded-lg mb-6">
      <strong>Heads up:</strong> Could not find a messages table. Create <code>ticket_messages</code> (or <code>ticket_replies</code>) to enable replies.<br>
      Minimal schema example:
      <pre class="text-xs overflow-x-auto">
CREATE TABLE ticket_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ticket_id INT NOT NULL,
  user_id INT NULL,
  body TEXT NOT NULL,
  is_admin TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);</pre>
    </div>
  <?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- List -->
    <aside class="lg:col-span-1 bg-white rounded-2xl shadow p-4" style="max-height:70vh; overflow:auto;">
      <?php
        $w = []; $types=''; $vals=[];
        $sql = "SELECT t.id,t.user_id,t.subject,t.status,t.priority,t.category,t.created_at,t.updated_at,
                       u.username,u.first_name,u.last_name
                FROM tickets t
                JOIN users u ON u.id=t.user_id ";
        if ($status!=='') { $w[] = "t.status=?"; $types.='s'; $vals[]=$status; }
        if ($q!=='') { $w[] = "(t.subject LIKE CONCAT('%',?,'%') OR u.username LIKE CONCAT('%',?,'%'))"; $types.='ss'; $vals[]=$q; $vals[]=$q; }
        if ($w) { $sql .= "WHERE ".implode(' AND ',$w)." "; }
        $sql .= "ORDER BY FIELD(t.status,'open','pending','resolved','closed'), t.updated_at DESC, t.id DESC LIMIT ? OFFSET ?";
        $types .= 'ii'; $vals[]=$limit; $vals[]=$offset;

        $stmt = $db->prepare($sql);
        $stmt->bind_param($types, ...$vals);
        $stmt->execute();
        $rs = $stmt->get_result();
        $tickets = $rs->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
      ?>
      <?php if (!$tickets): ?>
        <div class="text-gray-500 text-sm">No tickets found.</div>
      <?php endif; ?>
      <div class="space-y-2">
        <?php foreach ($tickets as $t): $isActive = ($view_id === (int)$t['id']); ?>
          <a href="admin_tickets.php?id=<?= (int)$t['id'] ?>&status=<?= e($status) ?>&q=<?= e($q) ?>"
             class="block p-3 rounded-xl border <?= $isActive ? 'border-purple-500 bg-purple-50' : 'border-transparent hover:bg-gray-50' ?>">
            <div class="flex items-center justify-between gap-3">
              <div class="font-semibold">#<?= (int)$t['id'] ?> • <?= e($t['subject']) ?></div>
              <div class="text-xs text-gray-500"><?= e(date('M j H:i', strtotime($t['updated_at'] ?: $t['created_at']))) ?></div>
            </div>
            <div class="text-xs text-gray-500 mt-1">@<?= e($t['username']) ?> • <span class="uppercase"><?= e($t['status']) ?></span></div>
          </a>
        <?php endforeach; ?>
      </div>
      <!-- (Simple) Pagination hint -->
      <div class="mt-4 text-xs text-gray-500">Showing up to <?= $limit ?> results.</div>
    </aside>

    <!-- Detail -->
    <main class="lg:col-span-2 bg-white rounded-2xl shadow p-0">
      <?php if (!$view_id): ?>
        <div class="p-8 text-gray-500">Pick a ticket from the left.</div>
      <?php else: ?>
        <?php
          // Fetch ticket
          $stmt = $db->prepare("SELECT t.*, u.username, u.first_name, u.last_name, u.email
                                FROM tickets t JOIN users u ON u.id=t.user_id WHERE t.id=? LIMIT 1");
          $stmt->bind_param('i', $view_id); $stmt->execute(); $ticket = $stmt->get_result()->fetch_assoc(); $stmt->close();
          if (!$ticket): ?>
            <div class="p-8 text-gray-500">Ticket not found.</div>
          <?php else:
            // Fetch messages
            $msgs = [];
            if ($msgTable) {
              $stmt = $db->prepare("SELECT m.*, u.username AS sender_username, u.first_name AS sender_first, u.last_name AS sender_last
                                    FROM `{$msgTable}` m
                                    LEFT JOIN users u ON u.id = COALESCE(m.user_id, m.sender_user_id)
                                    WHERE m.ticket_id=? ORDER BY m.id ASC");
              $stmt->bind_param('i', $view_id);
              $stmt->execute(); $msgs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
            }
          ?>
          <div class="border-b px-5 py-4 flex items-center justify-between">
            <div>
              <div class="font-bold text-lg">#<?= (int)$ticket['id'] ?> — <?= e($ticket['subject']) ?></div>
              <div class="text-sm text-gray-500">@<?= e($ticket['username']) ?> • <?= e($ticket['email']) ?></div>
            </div>
            <form method="post" class="flex items-center gap-2">
              <?= csrf_field() ?>
              <input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>">
              <select name="set_status" class="border rounded px-2 py-1 text-sm">
                <?php foreach (['open','pending','resolved','closed'] as $st): ?>
                  <option value="<?= e($st) ?>" <?= $ticket['status']===$st?'selected':'' ?>><?= ucfirst($st) ?></option>
                <?php endforeach; ?>
              </select>
              <button class="bg-gray-700 text-white px-3 py-1 rounded text-sm">Update</button>
            </form>
          </div>

          <div id="msgList" class="px-5 py-4" style="max-height:60vh; overflow:auto;">
            <?php if (!$msgs): ?>
              <div class="text-gray-500 text-sm">No messages yet.</div>
            <?php else: foreach ($msgs as $m):
              $isAdminMsg = isset($m['is_admin']) ? ((int)$m['is_admin']===1) : ((int)($m['user_id'] ?? $m['sender_user_id'] ?? 0) !== (int)$ticket['user_id']);
            ?>
              <div class="mb-3 flex <?= $isAdminMsg ? 'justify-end' : 'justify-start' ?>">
                <div class="max-w-[80%] px-3 py-2 rounded-lg <?= $isAdminMsg ? 'bg-purple-100' : 'bg-gray-100' ?>">
                  <div class="text-sm text-gray-800 whitespace-pre-wrap"><?= nl2br(e($m['body'] ?? '')) ?></div>
                  <div class="text-[11px] text-gray-500 mt-1">
                    <?= e(date('Y-m-d H:i', strtotime($m['created_at'] ?? $m['updated_at'] ?? 'now'))) ?>
                    <?php if (!empty($m['sender_username'])): ?> • @<?= e($m['sender_username']) ?><?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; endif; ?>
          </div>

          <?php if ($msgTable): ?>
          <div class="border-t p-4">
            <form method="post" class="flex items-end gap-2">
              <?= csrf_field() ?>
              <input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>">
              <textarea name="reply" rows="2" class="flex-1 border rounded px-3 py-2" placeholder="Write a reply..."></textarea>
              <button class="bg-purple-600 text-white px-5 py-2 rounded-lg hover:bg-purple-700">Send</button>
            </form>
          </div>
          <?php endif; ?>
        <?php endif; ?>
      <?php endif; ?>
    </main>
  </div>
</section>
<?php include __DIR__ . '/app/views/partials/footer.php'; ?>
