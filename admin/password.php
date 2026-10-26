<?php
require __DIR__ . '/_boot.php';
requireAdmin(true);
$db = getDB();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_require();
  $st = $db->prepare('SELECT password_hash FROM admin_users WHERE id = ?'); $st->execute([$_SESSION['admin_id']]);
  $hash = (string)$st->fetchColumn();
  $new = (string)($_POST['new'] ?? '');
  if (!password_verify((string)($_POST['current'] ?? ''), $hash)) { flash('danger', 'Current password is incorrect.'); }
  elseif (strlen($new) < 10) { flash('danger', 'Use a new password of at least 10 characters.'); }
  elseif ($new !== (string)($_POST['confirm'] ?? '')) { flash('danger', 'New passwords do not match.'); }
  else {
    $db->prepare('UPDATE admin_users SET password_hash = ?, must_change_password = 0 WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $_SESSION['admin_id']]);
    $_SESSION['must_change'] = 0;
    session_regenerate_id(true);
    flash('success', 'Password updated.');
  }
  header('Location: password.php'); exit;
}
$pageTitle = 'Change password'; $active = 'password';
require __DIR__ . '/_top.php';
?>
<div class="panel" style="max-width:520px">
  <h1 class="h4 mb-3">Change password</h1>
  <?php if (!empty($_SESSION['must_change'])): ?><div class="alert alert-warning">You must change your password before continuing.</div><?php endif; ?>
  <form method="post"><?= csrf_field() ?>
    <div class="mb-3"><label class="form-label" for="c">Current password</label><input class="form-control" id="c" name="current" type="password" required></div>
    <div class="mb-3"><label class="form-label" for="n">New password (10+ characters)</label><input class="form-control" id="n" name="new" type="password" required minlength="10"></div>
    <div class="mb-3"><label class="form-label" for="m">Confirm new password</label><input class="form-control" id="m" name="confirm" type="password" required></div>
    <button class="btn btn-accent">Update password</button>
  </form>
</div>
<?php require __DIR__ . '/_bottom.php'; ?>
