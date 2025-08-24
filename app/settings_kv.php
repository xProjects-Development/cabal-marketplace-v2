<?php
// app/settings_kv.php — tiny KV store for extra settings like alz_max
function _kv_ready(){
  @db()->query("CREATE TABLE IF NOT EXISTS app_settings (k VARCHAR(64) PRIMARY KEY, v TEXT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
function settings_write_kv($key,$value){ _kv_ready(); $stmt=db()->prepare("INSERT INTO app_settings (k,v) VALUES (?,?) ON DUPLICATE KEY UPDATE v=VALUES(v)"); $stmt->bind_param('ss',$key,$value); $ok=$stmt->execute(); $stmt->close(); return $ok; }
function settings_read_kv($key,$default=null){ _kv_ready(); $stmt=db()->prepare("SELECT v FROM app_settings WHERE k=? LIMIT 1"); $stmt->bind_param('s',$key); $stmt->execute(); $res=$stmt->get_result(); $row=$res?$res->fetch_assoc():null; $stmt->close(); return $row['v'] ?? $default; }
