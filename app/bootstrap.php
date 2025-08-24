<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/middleware.php';
require_once __DIR__ . '/user.php';
require_once __DIR__ . '/nft.php';
require_once __DIR__ . '/shoutbox.php';
require_once __DIR__ . '/offers.php';
require_once __DIR__ . '/messages.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/profile_feedback.php';
$settings = settings_load();
?>
