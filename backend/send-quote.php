<?php
declare(strict_types=1);

// Production error handling
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
app_session();
header('Content-Type: application/json');

function out(bool $ok, string $msg, int $code = 200): never {
  http_response_code($code);
  echo json_encode(['ok' => $ok, 'message' => $msg]);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(false, 'Invalid request.', 405);
if (!csrf_valid()) out(false, 'Session expired. Please refresh the page and try again.', 403);
if (!empty($_POST['website'])) out(true, 'Thanks! We will contact you soon.'); // honeypot: pretend success
if (time() - (int)($_SESSION['last_quote'] ?? 0) < 30) out(false, 'Please wait a moment before sending again.', 429);

$clean = fn(string $k, int $max) => mb_substr(trim(strip_tags((string)($_POST[$k] ?? ''))), 0, $max);
$name = $clean('name', 80); $phone = $clean('phone', 30); $email = $clean('email', 120);
$usecase = $clean('usecase', 40); $budget = $clean('budget', 30); $message = $clean('message', 1500);

$errors = [];
if ($name === '') $errors[] = 'name';
if (!preg_match('/^[+\d][\d\s\-()]{6,}$/', $phone)) $errors[] = 'a valid phone number';
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'a valid email';
if ($usecase === '') $errors[] = 'what you need';
if ($errors) out(false, 'Please provide: ' . implode(', ', $errors) . '.', 422);

try {
  $db = getDB();
  $ip = substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);

  $st = $db->prepare('SELECT COUNT(*) FROM quote_requests WHERE ip = ? AND created_at > (NOW() - INTERVAL 1 HOUR)');
  $st->execute([$ip]);
  if ((int)$st->fetchColumn() >= 10) out(false, 'Too many requests from your connection. Please message us on Messenger.', 429);

  $bundleId = (int)($_POST['bundle_id'] ?? 0); $bundleName = null;
  if ($bundleId) {
    $st = $db->prepare('SELECT name FROM bundles WHERE id = ? AND is_active = 1');
    $st->execute([$bundleId]);
    $bundleName = $st->fetchColumn() ?: null;
    if (!$bundleName) $bundleId = 0;
  }

  $db->prepare('INSERT INTO quote_requests (name, email, phone, build_type, budget, message, bundle_id, bundle_name, ip) VALUES (?,?,?,?,?,?,?,?,?)')
     ->execute([$name, $email ?: null, $phone, $usecase, $budget, $message, $bundleId ?: null, $bundleName, $ip]);
} catch (Throwable $ex) {
  error_log('Quote save failed: ' . $ex->getMessage());
  out(false, 'We could not save your request right now. Please message us on Messenger instead.', 500);
}

$_SESSION['last_quote'] = time();

// Email the owner. The request is already saved, so a mail failure is only logged.
$mail = cfg('mail', []);
if (!empty($mail['to'])) {
  $subject = 'Quote request from ' . preg_replace('/[\r\n]+/', ' ', $name);
  $body = "New quote request\n\nName: $name\nPhone: $phone\nEmail: $email\nInterested in: " . ($bundleName ?: $usecase) . "\nBudget: $budget\n\n$message\n";
  $headers = 'From: ' . ($mail['from'] ?? 'noreply@localhost') . "\r\n" . ($email ? "Reply-To: $email\r\n" : '');
  if (!@mail($mail['to'], $subject, $body, $headers)) error_log('Quote email could not be sent (check mail settings).');
}
out(true, 'Thanks! We received your request and will contact you soon.');
