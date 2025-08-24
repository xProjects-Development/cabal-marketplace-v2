<?php
require_once __DIR__ . '/app/bootstrap.php';
require_login();
$me = current_user(); $uid = (int)$me['id']; $db = db();

function ticket_can_view($t, $uid){ return $t && ((int)$t['user_id'] === $uid || (current_user() && current_user()['role']==='admin')); }

if (is_post()) { verify_csrf();
  // Create new ticket
  if (isset($_POST['new_ticket'])) {
    $sub = trim($_POST['subject'] ?? ''); $body = trim($_POST['body'] ?? ''); $prio = $_POST['priority'] ?? 'normal';
    if ($sub && $body) {
      $stmt = $db->prepare("INSERT INTO tickets (user_id, subject, priority, last_message_at) VALUES (?, ?, ?, NOW())");
      $stmt->bind_param('iss', $uid, $sub, $prio); $stmt->execute(); $tid = $stmt->insert_id; $stmt->close();
      $stmt = $db->prepare("INSERT INTO ticket_messages (ticket_id, user_id, body, is_admin) VALUES (?, ?, ?, 0)");
      $stmt->bind_param('iis', $tid, $uid, $body); $stmt->execute(); $stmt->close();
      redirect('/helpdesk.php?id='.$tid);
    } else { $_SESSION['flash_error']='Subject and message are required.'; }
  }
  // Reply to ticket
  if (isset($_POST['reply_ticket'])) {
    $tid = (int)$_POST['ticket_id']; $body = trim($_POST['body'] ?? '');
    $r = $db->query("SELECT * FROM tickets WHERE id=".$tid." LIMIT 1"); $t = $r->fetch_assoc();
    if (!$t || !ticket_can_view($t, $uid)) { http_response_code(403); die('Forbidden'); }
    if ($body) {
      $stmt=$db->prepare("INSERT INTO ticket_messages (ticket_id,user_id,body,is_admin) VALUES (?,?,?,0)");
      $stmt->bind_param('iis', $tid, $uid, $body); $stmt->execute(); $stmt->close();
      $db->query("UPDATE tickets SET status='open', last_message_at=NOW() WHERE id=".$tid);
      redirect('/helpdesk.php?id='.$tid);
    }
  }
  // Close ticket (owner)
  if (isset($_POST['close_ticket'])) {
    $tid=(int)$_POST['ticket_id'];
    $r = $db->query("SELECT * FROM tickets WHERE id=".$tid." LIMIT 1"); $t=$r->fetch_assoc();
    if (ticket_can_view($t,$uid) && (int)$t['user_id']===$uid) {
      $db->query("UPDATE tickets SET status='closed' WHERE id=".$tid);
    }
    redirect('/helpdesk.php?id='.$tid);
  }
}

$tid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
include __DIR__ . '/app/views/partials/header.php';
?>
<section class="max-w-6xl mx-auto px-4 py-10">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-3xl font-bold">Helpdesk</h1>
    <a href="<?= e(BASE_URL) ?>/helpdesk.php" class="text-sm text-gray-600 hover:underline">All tickets</a>
  </div>

  <?php if ($tid): ?>
    <?php
      $stmt=$db->prepare("SELECT t.*, u.username FROM tickets t JOIN users u ON u.id=t.user_id WHERE t.id=? LIMIT 1");
      $stmt->bind_param('i',$tid); $stmt->execute(); $ticket=$stmt->get_result()->fetch_assoc(); $stmt->close();
      if (!$ticket || !ticket_can_view($ticket,$uid)) { http_response_code(404); echo '<div class="text-gray-500">Ticket not found.</div>'; include __DIR__.'/app/views/partials/footer.php'; exit; }
      $msgs=$db->query("SELECT tm.*, u.username FROM ticket_messages tm LEFT JOIN users u ON u.id=tm.user_id WHERE tm.ticket_id=".$tid." ORDER BY tm.id ASC")->fetch_all(MYSQLI_ASSOC);
    ?>
    <div class="bg-white rounded-2xl shadow p-6 mb-6">
      <div class="flex items-start justify-between">
        <div>
          <div class="text-sm uppercase tracking-wide text-gray-500">#<?= (int)$ticket['id'] ?></div>
          <h2 class="text-2xl font-bold"><?= e($ticket['subject']) ?></h2>
          <div class="mt-1 text-sm text-gray-600">
            Status: <span class="px-2 py-0.5 rounded-full <?= $ticket['status']==='closed'?'bg-gray-200':'bg-green-100' ?>"><?= e($ticket['status']) ?></span>
            • Priority: <span class="px-2 py-0.5 rounded-full bg-purple-100"><?= e($ticket['priority']) ?></span>
          </div>
        </div>
        <?php if ((int)$ticket['user_id']===$uid && $ticket['status']!=='closed'): ?>
        <form method="post"><?= csrf_field() ?>
          <input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>">
          <button name="close_ticket" value="1" class="text-sm bg-gray-800 text-white px-3 py-1 rounded">Close</button>
        </form>
        <?php endif; ?>
      </div>

      <div class="mt-6 space-y-3 max-h-[55vh] overflow-y-auto">
        <?php foreach ($msgs as $m): $mine = ((int)$m['user_id']===$uid); ?>
          <div class="p-3 rounded <?= $m['is_internal']?'bg-yellow-50 border border-yellow-200':($mine?'bg-purple-50':'bg-gray-50') ?>">
            <div class="text-xs text-gray-500 mb-1">
              <?= $m['is_internal'] ? 'Internal note' : ($m['is_admin'] ? 'Admin' : '@'.e($m['username'] ?? 'you')) ?>
              • <?= e(date('Y-m-d H:i', strtotime($m['created_at']))) ?>
            </div>
            <div class="whitespace-pre-wrap text-gray-800"><?= nl2br(e($m['body'])) ?></div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if ($ticket['status']!=='closed'): ?>
      <form method="post" class="mt-4">
        <?= csrf_field() ?>
        <input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>">
        <textarea name="body" rows="3" class="w-full border rounded px-3 py-2" placeholder="Write a reply..."></textarea>
        <div class="mt-2 text-right">
          <button name="reply_ticket" value="1" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">Send</button>
        </div>
      </form>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if (!$tid): ?>
    <div class="bg-white rounded-2xl shadow p-6 mb-6">
      <h2 class="text-xl font-bold mb-3">Open a ticket</h2>
      <?php if (!empty($_SESSION['flash_error'])): ?><div class="bg-red-100 text-red-800 p-2 rounded mb-3"><?= e($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div><?php endif; ?>
      <form method="post" class="grid grid-cols-1 md:grid-cols-6 gap-3">
        <?= csrf_field() ?>
        <input class="md:col-span-4 border rounded px-3 py-2" name="subject" placeholder="Subject" required>
        <select class="md:col-span-2 border rounded px-3 py-2" name="priority">
          <option value="normal">Priority: Normal</option>
          <option value="high">Priority: High</option>
          <option value="low">Priority: Low</option>
        </select>
        <textarea class="md:col-span-6 border rounded px-3 py-2" rows="4" name="body" placeholder="Describe your issue..." required></textarea>
        <div class="md:col-span-6 text-right">
          <button class="bg-purple-600 text-white px-5 py-2 rounded" name="new_ticket" value="1">Create ticket</button>
        </div>
      </form>
    </div>

    <?php
      $res = $db->query("SELECT * FROM tickets WHERE user_id=".$uid." ORDER BY (status='open') DESC, last_message_at DESC");
      $rows = $res->fetch_all(MYSQLI_ASSOC);
    ?>
    <div class="bg-white rounded-2xl shadow p-6">
      <h2 class="text-xl font-bold mb-4">My tickets</h2>
      <?php if (!$rows): ?><div class="text-gray-500">No tickets yet.</div><?php endif; ?>
      <div class="space-y-2">
        <?php foreach ($rows as $t): ?>
          <a class="block p-3 rounded border hover:bg-gray-50" href="<?= e(BASE_URL) ?>/helpdesk.php?id=<?= (int)$t['id'] ?>">
            <div class="flex items-center justify-between">
              <div class="font-semibold"><?= e($t['subject']) ?></div>
              <div class="text-xs text-gray-500"><?= e(date('Y-m-d H:i', strtotime($t['last_message_at']))) ?></div>
            </div>
            <div class="text-sm text-gray-600">Status: <?= e($t['status']) ?> • Priority: <?= e($t['priority']) ?></div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/app/views/partials/footer.php'; ?>