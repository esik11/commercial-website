<?php
// One-time setup: creates the first admin account. DELETE THIS FILE after you use it.
require __DIR__ . '/_boot.php';
$db = getDB();
if ((int)$db->query('SELECT COUNT(*) FROM admin_users')->fetchColumn() > 0) {
  http_response_code(404); exit('Setup is already complete. Delete admin/setup.php from the server.');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_require();
  $u = trim($_POST['username'] ?? ''); $p = (string)($_POST['password'] ?? '');
  if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $u)) { $error = 'Username: 3-50 letters, numbers, dot, dash or underscore.'; }
  elseif (strlen($p) < 10) { $error = 'Use a password of at least 10 characters.'; }
  elseif ($p !== (string)($_POST['confirm'] ?? '')) { $error = 'Passwords do not match.'; }
  else {
    $db->prepare('INSERT INTO admin_users (username, password_hash) VALUES (?, ?)')->execute([$u, password_hash($p, PASSWORD_DEFAULT)]);
    flash('success', 'Admin created. Log in, then delete admin/setup.php.');
    header('Location: login.php'); exit;
  }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow">
<title>Setup | DOi PC</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="../assets/css/admin.css" rel="stylesheet">
</head><body>
<div class="login-wrap"><div class="panel login-card">
  <h1 class="h4 mb-1">Create the admin account</h1>
  <p class="text-muted-2 small">This page only works while no admin exists. Delete <code>admin/setup.php</code> afterwards.</p>
  <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
  <form method="post"><?= csrf_field() ?>
    <div class="mb-3"><label class="form-label" for="u">Username</label><input class="form-control" id="u" name="username" required></div>
    <div class="mb-3"><label class="form-label" for="p">Password (10+ characters)</label><input class="form-control" id="p" name="password" type="password" required minlength="10"></div>
    <div class="mb-3"><label class="form-label" for="c">Confirm password</label><input class="form-control" id="c" name="confirm" type="password" required></div>
    <button class="btn btn-accent w-100">Create admin</button>
  </form>
</div></div>
</body></html>
