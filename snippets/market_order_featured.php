<?php
// snippets/market_order_featured.php
// Place where you build the ORDER BY from your sort dropdown.
$sort = $_GET['sort'] ?? 'newest';
switch ($sort) {
  case 'price_low':  $orderBase = 'price_alz ASC';  break;
  case 'price_high': $orderBase = 'price_alz DESC'; break;
  case 'oldest':     $orderBase = 'created_at ASC'; break;
  default:           $orderBase = 'created_at DESC';
}
// IMPORTANT: prefix Featured-first
$order = "is_featured DESC, {$orderBase}";
