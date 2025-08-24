<?php
/**
 * cm_early_logger.php
 * Include this as the VERY FIRST line of a PHP page to capture fatal errors.
 * It writes to __cm_error.log in the same directory.
 */
if (!defined('CM_EARLY_LOGGER')) {
  define('CM_EARLY_LOGGER', 1);
  $cm_logfile = __DIR__ . '/__cm_error.log';
  @ini_set('log_errors', '1');
  @ini_set('display_errors', '0'); // keep off; we'll log to file
  @ini_set('error_reporting', (string)E_ALL);
  @error_reporting(E_ALL);

  function __cm_log($msg) {
    $f = fopen(__DIR__ . '/__cm_error.log', 'a');
    if ($f) {
      fwrite($f, '['.date('Y-m-d H:i:s').'] ' . $msg . PHP_EOL);
      fclose($f);
    }
  }

  set_error_handler(function($severity, $message, $file, $line){
    __cm_log("PHP ERROR [$severity] $message in $file:$line");
    return false; // allow normal handling too
  });

  set_exception_handler(function($ex){
    __cm_log("UNCAUGHT " . get_class($ex) . ": " . $ex->getMessage() . " in " . $ex->getFile() . ":" . $ex->getLine());
  });

  register_shutdown_function(function(){
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
      __cm_log("FATAL: {$e['message']} in {$e['file']}:{$e['line']}");
    }
  });

  __cm_log("---- early logger attached at " . basename($_SERVER['SCRIPT_NAME']) . " ----");
}
?>
