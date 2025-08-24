<?php
/**
 * app/models/faq.php
 * Helper functions for FAQs + auto-migration.
 */

if (!function_exists('e')) { function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); } }

/** mysqli escape helper */
function _faq_db_esc($s) {
  $db = db();
  if (is_object($db) && method_exists($db, 'real_escape_string')) return $db->real_escape_string($s);
  return addslashes($s);
}

/** Run a query and return result or false */
function _faq_db_q($sql) {
  $db = db();
  return $db->query($sql);
}

/** Check if column exists */
function _faq_col_exists($table, $col) {
  $res = _faq_db_q("SHOW COLUMNS FROM `$table` LIKE '". _faq_db_esc($col) ."'");
  return $res && $res->num_rows > 0;
}

/** Ensure table + columns exist */
function faq_table_ensure() {
  // Create table if not exists (minimal set)
  _faq_db_q("CREATE TABLE IF NOT EXISTS `faqs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `question` TEXT NOT NULL,
    `answer` MEDIUMTEXT NOT NULL,
    `is_published` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

  // Make sure sort_order exists (for older installs)
  if (!_faq_col_exists('faqs', 'sort_order')) {
    _faq_db_q("ALTER TABLE `faqs` ADD `sort_order` INT NOT NULL DEFAULT 0 AFTER `is_published`;");
  }
}
faq_table_ensure(); // run on include

/** Fetch all (admin) */
function faq_all() {
  $res = _faq_db_q("SELECT id, question, answer, is_published, sort_order, created_at, updated_at
                    FROM faqs ORDER BY sort_order ASC, created_at ASC");
  $out = [];
  if ($res) { while ($row = $res->fetch_assoc()) $out[] = $row; }
  return $out;
}

/** Fetch published (public) */
function faq_all_published() {
  $res = _faq_db_q("SELECT id, question, answer, is_published, sort_order, created_at, updated_at
                    FROM faqs WHERE is_published=1 ORDER BY sort_order ASC, created_at ASC");
  $out = [];
  if ($res) { while ($row = $res->fetch_assoc()) $out[] = $row; }
  return $out;
}

/** Create */
function faq_create($question, $answer, $is_published=1) {
  $q = _faq_db_esc($question);
  $a = _faq_db_esc($answer);
  $pub = (int)$is_published;

  // Next sort order
  $next = 1;
  $res = _faq_db_q("SELECT COALESCE(MAX(sort_order),0)+1 AS n FROM faqs");
  if ($res && ($row=$res->fetch_assoc())) $next = (int)$row['n'];

  $sql = "INSERT INTO faqs (question, answer, is_published, sort_order, created_at, updated_at)
          VALUES ('$q', '$a', $pub, $next, NOW(), NOW())";
  return _faq_db_q($sql) ? true : false;
}

/** Publish toggle */
function faq_publish($id, $to) {
  $id = (int)$id; $to = (int)$to;
  return _faq_db_q("UPDATE faqs SET is_published=$to, updated_at=NOW() WHERE id=$id");
}

/** Delete */
function faq_delete($id) {
  $id = (int)$id;
  return _faq_db_q("DELETE FROM faqs WHERE id=$id");
}

/** Reorder up/down */
function faq_reorder($id, $down=false) {
  $id = (int)$id;
  // get this row
  $res = _faq_db_q("SELECT id, sort_order FROM faqs WHERE id=$id");
  if (!$res || !$res->num_rows) return false;
  $self = $res->fetch_assoc();
  $s = (int)$self['sort_order'];

  if ($down) {
    $res2 = _faq_db_q("SELECT id, sort_order FROM faqs WHERE sort_order > $s ORDER BY sort_order ASC LIMIT 1");
  } else {
    $res2 = _faq_db_q("SELECT id, sort_order FROM faqs WHERE sort_order < $s ORDER BY sort_order DESC LIMIT 1");
  }
  if (!$res2 || !$res2->num_rows) return true; // nothing to swap with

  $peer = $res2->fetch_assoc();
  $p = (int)$peer['sort_order'];
  // swap
  _faq_db_q("UPDATE faqs SET sort_order=$p WHERE id=$id");
  _faq_db_q("UPDATE faqs SET sort_order=$s WHERE id=".(int)$peer['id']);
  return true;
}
