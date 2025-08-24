<?php
// shoutbox_probe.php — diagnostics for shoutbox data (admin only)
require_once __DIR__ . '/app/bootstrap.php'; admin_only();
require_once __DIR__ . '/app/admin_shoutbox_lib.php';

header('Content-Type: text/plain; charset=utf-8');

$t = _as_shout_table();
if (!$t) { echo "No shout table found\n"; exit; }
$C = _as_shout_cols($t);

echo "Table: {$t}\n";
echo "Columns: id={$C['id']}, body={$C['body']}, created={$C['created']}, user={$C['uid']}, deleted={$C['deleted']}\n";

$q = db()->query("SELECT COUNT(*) c FROM `{$t}`"); $cnt = $q?$q->fetch_assoc()['c']:0;
echo "Total rows: {$cnt}\n";

$q = db()->query("SELECT `{$C['id']}` id, `{$C['body']}` body, `{$C['created']}` created".($C['uid']? ", `{$C['uid']}` user_id":"").($C['deleted']? ", `{$C['deleted']}` del": "")." FROM `{$t}` ORDER BY `{$C['id']}` DESC LIMIT 5");
$i=0; while($q && ($r=$q->fetch_assoc())){ $i++; echo "#{$i} id={$r['id']} created={$r['created']} del=".($r['del'] ?? 'NULL')." body=".substr((string)$r['body'],0,60)."\n"; }
if(!$i) echo "No sample rows fetched.\n";
