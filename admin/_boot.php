<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/stock.php';
header('Cache-Control: no-store');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
app_session();
set_exception_handler(function (Throwable $ex) {
  error_log((string)$ex);
  http_response_code(500);
  $debug = (cfg('app', [])['debug'] ?? false);
  echo 'Something went wrong. Check config/config.php, that MySQL is running, and the PHP error log.';
  if ($debug) { echo '<pre>' . e($ex->getMessage()) . '</pre>'; }
});
