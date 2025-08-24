<?php
// snippets/register_guard_and_store.php
// 1) Enforce acceptance BEFORE creating user
if (empty($_POST['accept_terms'])) {
  $errors[] = 'You must agree to the Terms to create an account.';
}

// 2) After you create the user and have $new_user_id:
if (empty($errors) && !empty($new_user_id)) {
  require_once __DIR__ . '/../app/terms_helpers.php';
  terms_store_acceptance((int)$new_user_id); // safe even if column missing
}
