<?php
// app/force_errors.php (TEMPORARY: remove after debugging)
// Include at the very top of a page to surface the actual PHP error instead of a blank page.
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');
ini_set('html_errors', '1');
echo "<!-- errors enabled -->\n";
?>
