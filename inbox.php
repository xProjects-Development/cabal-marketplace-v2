<?php require_once __DIR__ . '/app/bootstrap.php'; require_login();
include __DIR__ . '/app/views/partials/header.php';
$me = current_user();

$uid = (int)$me['id'];
$db = db();

function table_exists($name){ $n = db()->real_escape_string($name); return db()->query("SHOW TABLES LIKE '{$n}'")->num_rows ? true : false; }
function column_exists($table, $col){ $t = db()->real_escape_string($table); $c = db()->real_escape_string($col); return db()->query("SHOW COLUMNS FROM `{$t}` LIKE '{$c}'")->num_rows ? true : false; }

$has_conversations = table_exists('conversations');
$has_is_read = column_exists('messages','is_read');
$threads = [];

if ($has_conversations) {
  // Pull threads for this user
  $sql = "SELECT c.id, c.nft_id, c.buyer_user_id, c.seller_user_id, c.updated_at,
                 n.title AS nft_title, n.image_path, n.creator_user_id,
                 u1.username AS buyer_username, u2.username AS seller_username
          FROM conversations c
          JOIN nfts n ON n.id=c.nft_id
          JOIN users u1 ON u1.id=c.buyer_user_id
          JOIN users u2 ON u2.id=c.seller_user_id
          WHERE c.buyer_user_id=? OR c.seller_user_id=?
          ORDER BY c.updated_at DESC
          LIMIT 100";
  $stmt = $db->prepare($sql); $stmt->bind_param('ii', $uid, $uid); $stmt->execute(); $res=$stmt->get_result();
  while ($row = $res->fetch_assoc()) {
    // last message
    $stmt2 = $db->prepare("SELECT m.id, m.body, m.created_at, m.sender_user_id FROM messages m WHERE m.conversation_id=? ORDER BY m.id DESC LIMIT 1");
    $stmt2->bind_param('i', $row['id']); $stmt2->execute(); $last = $stmt2->get_result()->fetch_assoc(); $stmt2->close();
    $row['last_body'] = $last ? $last['body'] : '';
    $row['last_at'] = $last ? $last['created_at'] : $row['updated_at'];
    // unread
    if ($has_is_read) {
      $stmt3 = $db->prepare("SELECT COUNT(*) AS c FROM messages WHERE conversation_id=? AND recipient_user_id=? AND is_read=0");
      $stmt3->bind_param('ii', $row['id'], $uid); $stmt3->execute(); $row['unread'] = (int)($stmt3->get_result()->fetch_assoc()['c'] ?? 0); $stmt3->close();
    } else {
      $row['unread'] = ($last && (int)$last['sender_user_id'] !== $uid) ? 1 : 0;
    }
    // other party
    $other_id = ($uid===(int)$row['buyer_user_id']) ? (int)$row['seller_user_id'] : (int)$row['buyer_user_id'];
    $row['other_username'] = ($uid===(int)$row['buyer_user_id']) ? $row['seller_username'] : $row['buyer_username'];
    $threads[] = $row;
  }
  $stmt->close();
} else {
  // Fallback: synthesize thread list from messages
  $sql = "SELECT m.conversation_id AS id, MAX(m.created_at) AS last_at
          FROM messages m
          WHERE m.sender_user_id=? OR m.recipient_user_id=?
          GROUP BY m.conversation_id
          ORDER BY last_at DESC
          LIMIT 100";
  $stmt = $db->prepare($sql); $stmt->bind_param('ii', $uid, $uid); $stmt->execute(); $res=$stmt->get_result();
  while ($row=$res->fetch_assoc()) {
    $cid = (int)$row['id'];
    // Try to deduce other user + nft
    $stmt2 = $db->prepare("SELECT m.body, m.created_at, m.sender_user_id, m.recipient_user_id FROM messages m WHERE m.conversation_id=? ORDER BY m.id DESC LIMIT 1");
    $stmt2->bind_param('i', $cid); $stmt2->execute(); $last = $stmt2->get_result()->fetch_assoc(); $stmt2->close();
    $other_id = $last ? ((int)$last['sender_user_id'] === $uid ? (int)$last['recipient_user_id'] : (int)$last['sender_user_id']) : 0;
    $other_username = '';
    if ($other_id) { $r = $db->query("SELECT username FROM users WHERE id=".$other_id." LIMIT 1"); $other_username = $r && $r->num_rows ? ($r->fetch_assoc()['username'] ?? '') : ''; }
    $threads[] = ['id'=>$cid,'nft_id'=>null,'nft_title'=>null,'image_path'=>null,'other_username'=>$other_username,'last_body'=>$last?($last['body']??''):'' ,'last_at'=>$row['last_at'],'unread'=>($last && (int)$last['sender_user_id'] !== $uid)?1:0];
  }
  $stmt->close();
}

$active_id = isset($_GET['id']) ? (int)$_GET['id'] : (count($threads)?(int)$threads[0]['id']:0);
?>
<style>
  .inbox-wrap { display:grid; grid-template-columns: 360px 1fr; gap: 1rem; }
  @media (max-width: 1024px){ .inbox-wrap { grid-template-columns: 1fr; } .thread-pane { display:none; } .thread-pane.active { display:block; } }
  .thread-item:hover { background:#faf7ff; }
  .msg-bubble{max-width:80%; padding:.75rem 1rem; border-radius:14px;}
  .msg-me{ background: #ece9ff; margin-left:auto; border-top-right-radius:6px;}
  .msg-them{ background: #f5f5f5; border-top-left-radius:6px;}
</style>
<section class="max-w-7xl mx-auto px-4 py-10">
  <h1 class="text-3xl font-bold mb-6">Inbox</h1>
  <div class="inbox-wrap">
    <!-- Threads -->
    <aside class="bg-white rounded-2xl shadow p-4">
      <div class="flex items-center gap-2 mb-3">
        <input id="threadSearch" type="text" placeholder="Search user or title..." class="w-full border rounded px-3 py-2">
      </div>
      <div id="threads" class="space-y-2" style="max-height:70vh; overflow:auto;">
        <?php if (!$threads): ?><div class="text-gray-500 text-sm">No conversations yet.</div><?php endif; ?>
        <?php foreach ($threads as $t): $is_active = ((int)$t['id']===$active_id); ?>
          <a href="?id=<?= (int)$t['id'] ?>" data-thread-id="<?= (int)$t['id'] ?>" class="thread-item block p-3 rounded-xl border <?= $is_active ? 'border-purple-400 bg-purple-50' : 'border-transparent' ?>">
            <div class="flex items-center justify-between gap-3">
              <div class="font-semibold">@<?= e($t['other_username'] ?? '') ?></div>
              <div class="text-xs text-gray-500"><?= e(date('M j H:i', strtotime($t['last_at']))) ?></div>
            </div>
            <div class="text-sm text-gray-600 line-clamp-1"><?= e(mb_substr($t['last_body'] ?? '',0,100)) ?></div>
            <?php if (!empty($t['unread'])): ?><span class="text-xs inline-block mt-1 bg-purple-600 text-white rounded-full px-2 py-0.5">new</span><?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    </aside>

    <!-- Conversation -->
    <main class="bg-white rounded-2xl shadow p-0 thread-pane <?= $active_id?'active':'' ?>">
      <?php if ($active_id): ?>
      <div class="border-b px-5 py-4 flex items-center justify-between">
        <div class="font-bold">Conversation #<?= (int)$active_id ?></div>
        <a href="/marketplace.php" class="text-sm text-gray-500 hover:underline">Back to marketplace</a>
      </div>
      <div id="msgList" class="px-5 py-4" style="height:60vh; overflow:auto;"></div>
      <div class="border-t p-4">
        <form id="msgForm" class="flex items-end gap-2">
          <?= csrf_field() ?>
          <textarea id="msgBody" rows="2" class="flex-1 border rounded px-3 py-2" placeholder="Write a message... (Enter to send, Shift+Enter for new line)"></textarea>
          <button class="bg-purple-600 text-white px-5 py-2 rounded-lg hover:bg-purple-700">Send</button>
        </form>
        <p class="text-xs text-gray-500 mt-2">Tip: Press Enter to send, Shift+Enter for a new line.</p>
      </div>
      <?php else: ?>
        <div class="p-8 text-gray-500">Pick a conversation from the left.</div>
      <?php endif; ?>
    </main>
  </div>
</section>

<script>
(function(){
  // filter threads
  const ts = document.getElementById('threadSearch');
  const list = document.getElementById('threads');
  if (ts) ts.addEventListener('input', function(){
    const q = this.value.toLowerCase();
    list.querySelectorAll('.thread-item').forEach(a => {
      const text = a.textContent.toLowerCase();
      a.style.display = text.indexOf(q) !== -1 ? '' : 'none';
    });
  });

  const convoId = <?= (int)$active_id ?>;
  const msgList = document.getElementById('msgList');
  let lastId = 0;

  function renderMessage(it){
    const wrap = document.createElement('div');
    wrap.className = 'mb-2 flex ' + (it.sender_user_id===<?= (int)$uid ?> ? 'justify-end' : 'justify-start');
    wrap.innerHTML = '<div class="msg-bubble '+ (it.sender_user_id===<?= (int)$uid ?>?'msg-me':'msg-them') +'"><div class="text-sm text-gray-700 whitespace-pre-wrap"></div><div class="text-[11px] text-gray-500 mt-1">'+ new Date(it.created_at).toLocaleString() +'</div></div>';
    wrap.querySelector('.text-sm').textContent = it.body || '';
    msgList.appendChild(wrap);
  }

  async function fetchThread(){
    if (!convoId) return;
    const res = await fetch('api/messages_thread.php?id='+convoId+'&after='+lastId, {headers:{'X-CSRF': window.CSRF}});
    const json = await res.json().catch(()=>({}));
    if (!json || !json.ok) return;
    (json.items||[]).forEach(it => { renderMessage(it); lastId = it.id; });
    msgList.scrollTop = msgList.scrollHeight;
  }

  async function sendMessage(body){
    const res = await fetch('api/message_post.php', {
      method:'POST', headers:{'Content-Type':'application/json','X-CSRF': window.CSRF},
      body: JSON.stringify({ conversation_id: convoId, body: body })
    });
    const json = await res.json().catch(()=>({}));
    if (!json || !json.ok) { alert(json && json.error ? json.error : 'Failed'); return; }
    lastId = 0; msgList.innerHTML=''; fetchThread();
  }

  // initial + poll
  fetchThread(); setInterval(fetchThread, 5000);

  // form send
  const form = document.getElementById('msgForm');
  const bodyEl = document.getElementById('msgBody');
  form.addEventListener('submit', function(e){ e.preventDefault(); const v=(bodyEl.value||'').trim(); if(!v) return; sendMessage(v); bodyEl.value=''; });
  bodyEl.addEventListener('keydown', function(e){
    if (e.key==='Enter' && !e.shiftKey){ e.preventDefault(); form.dispatchEvent(new Event('submit')); }
  });
})();
</script>
<?php include __DIR__ . '/app/views/partials/footer.php'; ?>
