<?php
/**
 * FAQ model + install helper for CABAL Marketplace | Europe
 * Depends on project helpers: db() -> mysqli, e(), admin_only(), csrf_field(), verify_csrf()
 */

if (!function_exists('faq_install')) {
  function faq_install() {
    $db = db();
    $sql = "CREATE TABLE IF NOT EXISTS `faqs` (
      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `question` VARCHAR(255) NOT NULL,
      `answer` TEXT NOT NULL,
      `is_published` TINYINT(1) NOT NULL DEFAULT 0,
      `position` INT NOT NULL DEFAULT 0,
      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY (`is_published`),
      KEY (`position`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $db->query($sql);

    // Ensure unique incremental positions if empty or all zeros
    $res = $db->query("SELECT COUNT(*) AS c FROM faqs");
    $row = $res ? $res->fetch_assoc() : ['c'=>0];
    if ((int)$row['c'] > 0) {
      // Fill position where it's zero to id order
      $db->query("UPDATE faqs SET position = id WHERE position = 0");
    }
  }

  function faq_next_position() {
    $db = db();
    $res = $db->query("SELECT COALESCE(MAX(position),0) AS maxp FROM faqs");
    $row = $res ? $res->fetch_assoc() : ['maxp' => 0];
    return (int)$row['maxp'] + 1;
  }

  function faq_all_published() {
    faq_install();
    $db = db();
    $rows = [];
    $res = $db->query("SELECT id, question, answer, is_published, position FROM faqs WHERE is_published=1 ORDER BY position ASC, id ASC");
    if ($res) { while ($r = $res->fetch_assoc()) $rows[] = $r; }
    return $rows;
  }

  function faq_all_admin() {
    faq_install();
    $db = db();
    $rows = [];
    $res = $db->query("SELECT id, question, answer, is_published, position, created_at, updated_at FROM faqs ORDER BY position ASC, id ASC");
    if ($res) { while ($r = $res->fetch_assoc()) $rows[] = $r; }
    return $rows;
  }

  function faq_create($q, $a, $published, $position=null) {
    $db = db();
    if ($position === null) $position = faq_next_position();
    $stmt = $db->prepare("INSERT INTO faqs (question, answer, is_published, position) VALUES (?, ?, ?, ?)");
    $pub = $published ? 1 : 0;
    $stmt->bind_param("ssii", $q, $a, $pub, $position);
    $stmt->execute();
    return $stmt->insert_id;
  }

  function faq_update($id, $q, $a, $published, $position) {
    $db = db();
    $stmt = $db->prepare("UPDATE faqs SET question=?, answer=?, is_published=?, position=? WHERE id=?");
    $pub = $published ? 1 : 0;
    $stmt->bind_param("ssiii", $q, $a, $pub, $position, $id);
    return $stmt->execute();
  }

  function faq_delete($id) {
    $db = db();
    $stmt = $db->prepare("DELETE FROM faqs WHERE id=?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
  }

  function faq_publish($id, $on=true) {
    $db = db();
    $stmt = $db->prepare("UPDATE faqs SET is_published=? WHERE id=?");
    $v = $on ? 1 : 0;
    $stmt->bind_param("ii", $v, $id);
    return $stmt->execute();
  }

  function faq_move($id, $dir) {
    // Swap positions with neighbor above/below
    $db = db();
    $id = (int)$id;
    $dir = $dir === 'up' ? 'up' : 'down';
    $res = $db->query("SELECT id, position FROM faqs WHERE id=$id");
    if (!$res || !$res->num_rows) return false;
    $cur = $res->fetch_assoc();
    $pos = (int)$cur['position'];
    if ($dir === 'up') {
      $neighbor = $db->query("SELECT id, position FROM faqs WHERE position < $pos ORDER BY position DESC LIMIT 1");
    } else {
      $neighbor = $db->query("SELECT id, position FROM faqs WHERE position > $pos ORDER BY position ASC LIMIT 1");
    }
    if (!$neighbor || !$neighbor->num_rows) return false;
    $n = $neighbor->fetch_assoc();
    $nid = (int)$n['id']; $npos = (int)$n['position'];

    $db->begin_transaction();
    try {
      $db->query("UPDATE faqs SET position = -1 WHERE id = $id"); // temp
      $db->query("UPDATE faqs SET position = $pos WHERE id = $nid");
      $db->query("UPDATE faqs SET position = $npos WHERE id = $id");
      $db->commit();
      return true;
    } catch (\Throwable $e) {
      $db->rollback();
      return false;
    }
  }
}
