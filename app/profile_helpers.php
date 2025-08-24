<?php
require_once __DIR__ . '/bootstrap.php';

function _col_exists($table, $col){
  $t = db()->real_escape_string($table);
  $c = db()->real_escape_string($col);
  $r = db()->query("SHOW COLUMNS FROM `{$t}` LIKE '{$c}'");
  return $r && $r->num_rows>0;
}

/**
 * Safe partial-update: only fields present and non-empty are changed.
 * Checkboxes are normalized 0/1. Avatar/Banner are optional.
 */
function profile_update(int $userId, array $post, array $files): bool {
  if ($userId<=0) return false;
  $db = db(); $sets=[]; $types=''; $vals=[];

  // text/optional fields (empty string = ignore; send "__CLEAR__" to NULL)
  $textKeys = ['bio','location','website','twitter','telegram'];
  foreach ($textKeys as $k){
    if (!array_key_exists($k,$post)) continue;
    $v = (string)$post[$k];
    if ($v==='__CLEAR__'){ if(_col_exists('users',$k)){ $sets[]="`{$k}`=NULL"; } continue; }
    if (trim($v)==='') continue;
    if ($k==='website' && $v && !preg_match('~^https?://~i',$v)) $v='https://'.$v;
    if (($k==='twitter'||$k==='telegram') && strlen($v) && $v[0]==='@') $v=substr($v,1);
    if (_col_exists('users',$k)){ $sets[]="`{$k}`=?"; $types.='s'; $vals[]=$v; }
  }

  // checkboxes
  $bools=['is_profile_public'=>isset($post['is_profile_public'])?1:0,
          'show_email'=>isset($post['show_email'])?1:0];
  foreach($bools as $k=>$v){ if (_col_exists('users',$k)){ $sets[]="`{$k}`=?"; $types.='i'; $vals[]=$v; } }

  // avatar upload
  if (!empty($files['avatar']['name']) && is_uploaded_file($files['avatar']['tmp_name'])){
    $info = @getimagesize($files['avatar']['tmp_name']);
    if ($info){
      $ext = image_type_to_extension($info[2],false) ?: 'jpg';
      $dir = __DIR__.'/../uploads/avatars'; if(!is_dir($dir)) @mkdir($dir,0775,true);
      $name = 'ava_'.$userId.'_'.time().'.'.$ext; $dest="$dir/$name";
      if (move_uploaded_file($files['avatar']['tmp_name'], $dest)){
        $public='/uploads/avatars/'.$name;
        foreach(['avatar_path','avatar_url','avatar'] as $c){
          if (_col_exists('users',$c)){ $sets[]="`{$c}`=?"; $types.='s'; $vals[]=$public; break; }
        }
      }
    }
  }

  // banner upload
  if (!empty($files['banner']['name']) && is_uploaded_file($files['banner']['tmp_name'])){
    $info = @getimagesize($files['banner']['tmp_name']);
    if ($info){
      $ext = image_type_to_extension($info[2],false) ?: 'jpg';
      $dir = __DIR__.'/../uploads/banners'; if(!is_dir($dir)) @mkdir($dir,0775,true);
      $name = 'ban_'.$userId.'_'.time().'.'.$ext; $dest="$dir/$name";
      if (move_uploaded_file($files['banner']['tmp_name'], $dest)){
        $public='/uploads/banners/'.$name;
        foreach(['banner_path','banner_url','banner'] as $c){
          if (_col_exists('users',$c)){ $sets[]="`{$c}`=?"; $types.='s'; $vals[]=$public; break; }
        }
      }
    }
  }

  if(!$sets) return true; // nothing to change
  $sql = "UPDATE users SET ".implode(',',$sets)." WHERE id=?";
  $types.='i'; $vals[]=$userId;
  $stmt=$db->prepare($sql); if(!$stmt) return false;
  $stmt->bind_param($types, ...$vals);
  $ok=$stmt->execute(); $stmt->close();
  return (bool)$ok;
}