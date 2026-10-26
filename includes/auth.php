<?php
declare(strict_types=1);

function app_session(): void {
  if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
      'lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
      'secure' => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
  }
}

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function peso($n): string { return '₱' . number_format((float)$n, 0); }

function csrf_token(): string {
  app_session();
  if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
  return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_valid(): bool {
  $token = $_SESSION['csrf'] ?? '';
  return $token !== '' && hash_equals($token, (string)($_POST['csrf'] ?? ''));
}
function csrf_require(): void {
  if (!csrf_valid()) { http_response_code(403); exit('Invalid or expired form. Go back, refresh the page and try again.'); }
}

function flash(string $type, string $msg): void { app_session(); $_SESSION['flash'] = [$type, $msg]; }
function flash_pull(): ?array {
  app_session();
  $f = $_SESSION['flash'] ?? null;
  unset($_SESSION['flash']);
  return $f;
}

/** Redirects to login unless an admin session is active (2 hour idle timeout). */
function requireAdmin(bool $allowPasswordChange = false): void {
  app_session();
  if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
  if (time() - (int)($_SESSION['last_seen'] ?? 0) > 7200) {
    session_unset(); session_destroy();
    header('Location: login.php?expired=1'); exit;
  }
  $_SESSION['last_seen'] = time();
  if (!$allowPasswordChange && !empty($_SESSION['must_change'])) { header('Location: password.php'); exit; }
}
