<?php
declare(strict_types=1);

$__cfgFile = __DIR__ . '/../config/config.php';
if (!is_file($__cfgFile)) {
  http_response_code(500);
  exit('Missing config/config.php. Copy config/config.example.php to config/config.php and fill it in.');
}
$GLOBALS['CONFIG'] = require $__cfgFile;

function cfg(string $key, $default = null) {
  return $GLOBALS['CONFIG'][$key] ?? $default;
}

/** Shared PDO connection. Throws on failure so each page can decide how to react. */
function getDB(): PDO {
  static $pdo = null;
  if ($pdo === null) {
    $c = $GLOBALS['CONFIG']['db'];
    $pdo = new PDO(
      "mysql:host={$c['host']};dbname={$c['name']};charset=utf8mb4",
      $c['user'], $c['pass'],
      [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
      ]
    );
  }
  return $pdo;
}
