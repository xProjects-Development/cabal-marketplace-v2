<?php
function require_login() {
    if (!current_user()) {
        $_SESSION['flash_error'] = 'Please login to continue.';
        redirect('/login.php');
    }
    if ((current_user()['status'] ?? 'active') !== 'active') {
        $_SESSION['flash_error'] = 'Your account is suspended.';
        redirect('/login.php');
    }
}
function admin_only() {
    if (!is_admin()) { http_response_code(403); die('Forbidden'); }
}
?>
