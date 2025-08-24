<?php
require_once __DIR__ . '/app/bootstrap.php';
require_login();

$db = db();

/* -------- helpers -------- */
function table_exists($name){ $n=db()->real_escape_string($name); $r=db()->query("SHOW TABLES LIKE '{$n}'"); return $r && $r->num_rows>0; }
function column_exists($table, $col){ $t=db()->real_escape_string($table); $c=db()->real_escape_string($col); $r=db()->query("SHOW COLUMNS FROM `{$t}` LIKE '{$c}'"); return $r && $r->num_rows>0; }

/* -------- ensure tables exist (no-op if already created) -------- */
if (!table_exists('tickets')) {
  $db->query("CREATE TABLE IF NOT EXISTS tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    status ENUM('open','pending','closed') NOT NULL DEFAULT 'open',
    priority ENUM('low','normal','high') NOT NULL DEFAULT 'normal',
    category VARCHAR(100) NOT NULL DEFAULT 'General',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX(user_id), INDEX(status), INDEX(updated_at)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}
if (!table_exists('ticket_messages')) {
  $db->query("CREATE TABLE IF NOT EXISTS ticket_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    body TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX(ticket_id), INDEX(user_id),
    CONSTRAINT fk_tm_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

/* -------- load ticket -------- */
$tid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($tid <= 0) { http_response_code(404); die('Ticket not found'); }

$select = "t.id, t.user_id, t.subject, t.status, t.created_at, t.updated_at";
$select .= column_exists('tickets','priority') ? ", t.priority" : ", 'normal' as priority";
$select .= column_exists('tickets','category') ? ", t.category" : ", 'General' as category";

$stmt = $db->prepare("SELECT $select, u.username, u.first_name, u.last_name
                      FROM tickets t JOIN users u ON u.id=t.user_id
                      WHERE t.id=? LIMIT 1");
$stmt->bind_param('i', $tid);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ticket) { http_response_code(404); die('Ticket not found'); }

/* -------- permissions -------- */
$me = current_user();
$is_admin = is_admin();
if (!$is_admin && (int)$ticket['user_id'] !== (int)$me['id']) {
  http_response_code(403); die('Forbidden');
}

/* -------- post: reply + (admin) status change -------- */
$errors = []; $saved = false;
if (is_post()) {
  verify_csrf();
  $body = trim($_POST['body'] ?? '');
  $status_in = $_POST['status'] ?? '';
  $status_changed = false;

  if ($is_admin && in_array($status_in, ['open','pending','closed'], true) && $status_in !== $ticket['status']) {
    $stmt = $db->prepare("UPDATE tickets SET status=? WHERE id=?");
    $stmt->bind_param('si', $status_in, $tid);
    $stmt->execute();
    $stmt->close();
    $ticket['status'] = $status_in;
    $status_changed = true;
  }

  if ($body !== '') {
    $stmt = $db->prepare("INSERT INTO ticket_messages (ticket_id, user_id, body) VALUES (?,?,?)");
    $uid = (int)$me['id'];
    $stmt->bind_param('iis', $tid, $uid, $body);
    $ok = $stmt->execute();
    $stmt->close();
    if ($ok) {
      $saved = true;
      $db->query("UPDATE tickets SET updated_at=NOW() WHERE id=".$tid);
    } else {
      $errors[] = 'Failed to post reply.';
    }
  } elseif (!$status_changed) {
    $errors[] = 'Nothing to save.';
  }
}

/* -------- fetch messages -------- */
$msgs = [];
$stmt = $db->prepare("SELECT m.id, m.user_id, m.body, m.created_at, u.username, u.first_name, u.last_name
                      FROM ticket_messages m
                      JOIN users u ON u.id=m.user_id
                      WHERE m.ticket_id=?
                      ORDER BY m.id ASC");
$stmt->bind_param('i', $tid);
$stmt->execute();
$res = $stmt->get_result();
if ($res) $msgs = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* -------- view -------- */
include __DIR__ . '/app/views/partials/header.php';
?>
<section class="max-w-5xl mx-auto px-4 py-10">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl md:text-3xl font-bold">
      Helpdesk — Ticket #<?= (int)$ticket['id'] ?>:
      <span class="text-gray-700"><?= e($ticket['subject']) ?></span>
    </h1>
    <a href="<?= e(BASE_URL) ?>/<?= $is_admin ? 'admin_helpdesk.php' : 'helpdesk.php' ?>"
       class="text-sm text-gray-600 hover:underline">Back to list</a>
  </div>

  <?php if ($saved): ?>
    <div class="mb-4 bg-green-100 text-green-800 px-4 py-2 rounded">Saved.</div>
  <?php endif; ?>
  <?php if ($errors): ?>
    <div class="mb-4 bg-red-100 text-red-800 px-4 py-2 rounded">
      <?php foreach ($errors as $er) echo '<div>'.e($er).'</div>'; ?>
    </div>
  <?php endif; ?>

  <div class="bg-white rounded-2xl shadow p-6 mb-6">
    <div class="flex flex-wrap items-center gap-3 text-sm text-gray-700">
      <span class="px-3 py-1 rounded-full
        <?= $ticket['status']==='closed'?'bg-gray-200 text-gray-700':($ticket['status']==='pending'?'bg-yellow-100 text-yellow-800':'bg-green-100 text-green-800') ?>">
        Status: <?= e(ucfirst($ticket['status'])) ?>
      </span>
      <span class="px-3 py-1 rounded-full bg-purple-100 text-purple-800">Priority: <?= e(ucfirst($ticket['priority'])) ?></span>
      <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-800">Category: <?= e($ticket['category']) ?></span>
      <span class="ml-auto text-gray-500">Opened by @<?= e($ticket['username']) ?> • <?= e(date('M j, Y H:i', strtotime($ticket['created_at']))) ?></span>
    </div>
  </div>

  <div class="bg-white rounded-2xl shadow p-6 mb-6">
    <h3 class="font-semibold mb-4">Conversation</h3>
    <?php if (!$msgs): ?>
      <div class="text-gray-500">No messages yet.</div>
    <?php else: ?>
      <div class="space-y-4">
        <?php foreach ($msgs as $m): $mine = ((int)$m['user_id'] === (int)$me['id']); ?>
          <div class="flex <?= $mine?'justify-end':'justify-start' ?>">
            <div class="max-w-[80%] p-3 rounded-lg shadow <?= $mine?'bg-indigo-50':'bg-gray-50' ?>">
              <div class="text-xs text-gray-500 mb-1">
                <span class="font-medium">@<?= e($m['username']) ?></span> • <?= e(date('M j, Y H:i', strtotime($m['created_at']))) ?>
              </div>
              <div class="whitespace-pre-wrap text-gray-800"><?= nl2br(e($m['body'])) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="bg-white rounded-2xl shadow p-6">
    <form method="post" class="space-y-4">
      <?= csrf_field() ?>
      <div>
        <label class="block text-sm font-medium mb-1">Reply</label>
        <textarea name="body" rows="4" class="w-full border rounded px-3 py-2" placeholder="Write your message..."></textarea>
      </div>

      <div class="flex items-center gap-3">
        <button class="bg-purple-600 text-white px-5 py-2 rounded hover:bg-purple-700">Send</button>
        <?php if ($is_admin): ?>
          <div class="ml-auto flex items-center gap-2">
            <label class="text-sm text-gray-700">Change status:</label>
            <select name="status" class="border rounded px-3 py-2">
              <option value="">— keep —</option>
              <option value="open"   <?= $ticket['status']==='open'?'selected':'' ?>>Open</option>
              <option value="pending"<?= $ticket['status']==='pending'?'selected':'' ?>>Pending</option>
              <option value="closed" <?= $ticket['status']==='closed'?'selected':'' ?>>Closed</option>
            </select>
          </div>
        <?php else: ?>
          <input type="hidden" name="status" value="">
        <?php endif; ?>
      </div>
    </form>
  </div>
</section>
<?php include __DIR__ . '/app/views/partials/footer.php'; ?>
