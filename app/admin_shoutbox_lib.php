<?php
// app/admin_shoutbox_lib.php — robust shoutbox admin helpers (v5)
if (!function_exists('mb_strimwidth')) {
  function mb_strimwidth($str, $start, $width, $trimmarker='') {
    $s = substr($str, $start, $width);
    return (strlen($str) > $width ? $s.$trimmarker : $s);
  }
}
function _as_db_table_exists($name){
  $name = db()->real_escape_string($name);
  $r = db()->query("SHOW TABLES LIKE '{$name}'");
  return $r && $r->num_rows > 0;
}
function _as_db_has_users(){
  if (!_as_db_table_exists('users')) return false;
  $res = db()->query("SHOW COLUMNS FROM `users`");
  $have=['id'=>false,'username'=>false];
  while($res && ($row=$res->fetch_assoc())){
    if ($row['Field']==='id') $have['id']=true;
    if ($row['Field']==='username') $have['username']=true;
  }
  return $have['id'] && $have['username'];
}
function _as_shout_table(){
  if (defined('SHOUTBOX_TABLE') && SHOUTBOX_TABLE && _as_db_table_exists(SHOUTBOX_TABLE)) return SHOUTBOX_TABLE;
  $env=getenv('SHOUTBOX_TABLE'); if ($env && _as_db_table_exists($env)) return $env;
  foreach(['shout_messages','shoutbox_messages','shoutbox','shouts','chat_messages','messages_shout'] as $t){
    if (_as_db_table_exists($t)) return $t;
  }
  return null;
}
function _as_shout_cols($t){
  $cols=[]; $res=db()->query("SHOW COLUMNS FROM `{$t}`");
  while($res && ($row=$res->fetch_assoc())) $cols[$row['Field']]=true;
  $id='id';       foreach(['id','message_id','shout_id','sid'] as $c){ if(isset($cols[$c])){$id=$c;break;} }
  $body='message';foreach(['message','content','text','msg','body','shout','comment'] as $c){ if(isset($cols[$c])){$body=$c;break;} }
  $created='created_at'; foreach(['created_at','created','time','timestamp','posted_at','date','sent_at'] as $c){ if(isset($cols[$c])){$created=$c;break;} }
  $order_by = isset($cols[$created]) ? $created : $id;
  $uid=null;     foreach(['user_id','author_id','sender_id','from_user_id','uid','profile_id'] as $c){ if(isset($cols[$c])){$uid=$c;break;} }
  $deleted=null; foreach(['is_deleted','deleted','is_removed','removed','hidden'] as $c){ if(isset($cols[$c])){$deleted=$c;break;} }
  return ['id'=>$id,'body'=>$body,'created'=>$created,'order_by'=>$order_by,'uid'=>$uid,'deleted'=>$deleted,'has_users'=>_as_db_has_users()];
}
function adminx_shout_delete($id, $hard=false){
  admin_only();
  $t=_as_shout_table(); if (!$t) return false;
  $C=_as_shout_cols($t);
  if (!$hard) { if (!empty($_POST['hard'])) $hard=true; if (getenv('SHOUTBOX_HARD_DELETE')==='1') $hard=true; }
  $id=(int)$id;
  if ($hard || !$C['deleted']) {
    $sql="DELETE FROM `{$t}` WHERE `{$C['id']}`={$id}";
  } else {
    $sql="UPDATE `{$t}` SET `{$C['deleted']}`=1 WHERE `{$C['id']}`={$id}";
  }
  return db()->query($sql)===TRUE;
}
function adminx_shoutbox_render($q='', $limit=50, $page=0){
  admin_only();
  $t=_as_shout_table();
  if(!$t){ echo '<div class="p-4 bg-yellow-50 text-yellow-800 rounded">No shoutbox table found.</div>'; return; }
  $C=_as_shout_cols($t);
  $limit=max(1,(int)$limit); $page=max(0,(int)$page); $offset=$page*$limit;
  $safe=!empty($_GET['safe']);
  $where=[];
  if(!$safe && $C['deleted']) $where[]="COALESCE(`{$C['deleted']}`,0)=0";
  if($q!==''){ $q=db()->real_escape_string($q); $where[]="`{$C['body']}` LIKE '%{$q}%'"; }
  $whereSql=$where?('WHERE '.implode(' AND ',$where)) : '';
  $sql="SELECT `{$C['id']}` AS id, `{$C['body']}` AS body, `{$C['created']}` AS created"
     . ($C['uid']? ", `{$C['uid']}` AS user_id": "")
     . " FROM `{$t}` {$whereSql} ORDER BY `{$C['order_by']}` DESC LIMIT {$limit} OFFSET {$offset}";
  $res=db()->query($sql);
  $total=0; $qAll=db()->query("SELECT COUNT(*) c FROM `{$t}`"); if($qAll && ($r=$qAll->fetch_assoc())) $total=(int)$r['c'];
  echo '<div class="text-xs text-gray-500 mb-2">Table: <b>'.e($t).'</b> | ID: <b>'.e($C['id']).'</b> | Message: <b>'.e($C['body']).'</b> | Time: <b>'.e($C['created']).'</b>'.($C['uid']?' | User: <b>'.e($C['uid']).'</b>':'').' | Delete flag: <b>'.e($C['deleted']?:'HARD').'</b> | Rows in table: <b>'.$total.'</b>'.($safe?' | <b>SAFE MODE</b>':'').'</div>';
  ?>
  <div class="mb-4">
    <form method="get" class="flex gap-2 items-center">
      <input type="hidden" name="tab" value="shoutbox">
      <input class="border px-3 py-2 rounded w-64" name="q" value="<?= e($q) ?>" placeholder="Search shouts...">
      <button class="px-4 py-2 bg-gray-800 text-white rounded">Search</button>
      <a class="text-xs text-gray-500 underline" href="?tab=shoutbox&safe=1">Safe mode</a>
    </form>
  </div>
  <div class="bg-white rounded-2xl shadow overflow-hidden">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50 text-gray-600">
        <tr>
          <th class="p-3 text-left w-20">ID</th>
          <?php if ($C['uid']): ?><th class="p-3 text-left w-40">User</th><?php endif; ?>
          <th class="p-3 text-left">Message</th>
          <th class="p-3 text-left w-48">Created</th>
          <th class="p-3 text-left w-40">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if($res) while($row=$res->fetch_assoc()): ?>
        <tr class="border-t">
          <td class="p-3 text-gray-500">#<?= (int)$row['id'] ?></td>
          <?php if ($C['uid']): ?>
          <td class="p-3">
            <?php
            $uname='—';
            if ($C['has_users']) {
              $u=db()->query("SELECT username FROM users WHERE id=".(int)$row['user_id']." LIMIT 1");
              if($u && $u->num_rows) $uname='@'.e($u->fetch_assoc()['username']);
            }
            echo $uname;
            ?>
          </td>
          <?php endif; ?>
          <td class="p-3"><?= e(mb_strimwidth($row['body'] ?? '', 0, 140, '…')) ?></td>
          <td class="p-3 text-gray-500"><?= e($row['created']) ?></td>
          <td class="p-3">
            <form method="post" class="inline" onsubmit="return confirm('Delete this message?');">
              <?= csrf_field() ?>
              <input type="hidden" name="tab" value="shoutbox">
              <input type="hidden" name="action" value="del">
              <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
              <label class="text-xs mr-1"><input type="checkbox" name="hard" value="1"> Hard</label>
              <button class="px-3 py-1.5 bg-red-600 text-white rounded">Delete</button>
            </form>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
  <?php
  $pages=max(1,ceil($total / max(1,(int)$limit)));
  if($pages>1): ?>
    <div class="mt-3 flex gap-2">
      <?php for($i=0;$i<$pages;$i++): $u='?tab=shoutbox&p='.$i.($q!==''?('&q='.urlencode($q)):'').(!empty($_GET['safe'])?'&safe=1':''); ?>
        <a class="px-3 py-1.5 rounded <?= $i==(int)$page?'bg-gray-900 text-white':'bg-gray-100 text-gray-700' ?>" href="<?= e($u) ?>"><?= $i+1 ?></a>
      <?php endfor; ?>
    </div>
  <?php endif;
}
