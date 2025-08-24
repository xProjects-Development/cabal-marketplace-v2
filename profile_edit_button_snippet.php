<?php
// Include this file inside profile.php where the header actions live.
if (function_exists('current_user')) {
  $me = current_user();
  if ($me && isset($is_self) && $is_self) {
    // Show only on *my* own profile
    echo '<a href="'.e(BASE_URL).'/profile_edit.php" class="inline-flex items-center gap-2 bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-900"><i class="fas fa-user-edit"></i> Edit profile</a>';
  }
}
?>