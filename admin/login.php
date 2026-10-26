<?php
require __DIR__ . '/_boot.php';
if (!empty($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
$db = getDB();
if ((int)$db->query('SELECT COUNT(*) FROM admin_users')->fetchColumn() === 0) { header('Location: setup.php'); exit; }

$notice = flash_pull();
$error = isset($_GET['expired']) ? 'Your session expired. Please log in again.' : '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_require();
  $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
  $st = $db->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > (NOW() - INTERVAL 15 MINUTE)');
  $st->execute([$ip]);
  if ((int)$st->fetchColumn() >= 5) {
    $error = 'Too many failed attempts. Please wait 15 minutes and try again.';
  } else {
    $st = $db->prepare('SELECT * FROM admin_users WHERE username = ?');
    $st->execute([trim($_POST['username'] ?? '')]);
    $user = $st->fetch();
    if ($user && password_verify((string)($_POST['password'] ?? ''), $user['password_hash'])) {
      session_regenerate_id(true);
      $_SESSION['admin_id'] = (int)$user['id'];
      $_SESSION['admin_name'] = $user['username'];
      $_SESSION['must_change'] = (int)$user['must_change_password'];
      $_SESSION['last_seen'] = time();
      $db->prepare('DELETE FROM login_attempts WHERE ip = ?')->execute([$ip]);
      $db->prepare('UPDATE admin_users SET last_login = NOW() WHERE id = ?')->execute([$user['id']]);
      header('Location: ' . ($_SESSION['must_change'] ? 'password.php' : 'index.php')); exit;
    }
    $db->prepare('INSERT INTO login_attempts (ip) VALUES (?)')->execute([$ip]);
    $error = 'Invalid username or password.';
  }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow">
<title>Admin login | DOi PC</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="../assets/css/admin.css" rel="stylesheet">
</head><body>
<div class="login-wrap"><div class="panel login-card">
  <h1 class="h4 mb-3">Admin login</h1>
  <?php if ($notice): ?><div class="alert alert-<?= e($notice[0]) ?>"><?= e($notice[1]) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
  <form method="post" autocomplete="off"><?= csrf_field() ?>
    <div class="mb-3"><label class="form-label" for="u">Username</label><input class="form-control" id="u" name="username" required autofocus></div>
    <div class="mb-3"><label class="form-label" for="p">Password</label><input class="form-control" id="p" name="password" type="password" required></div>
    <button class="btn btn-accent w-100">Log in</button>
  </form>
  <p class="text-center mt-3 mb-0 small"><a href="../index.php">Back to website</a></p>
</div></div>
</body></html>
