<?php
// Expects $pageTitle and $active to be set by the page.
$flash = flash_pull();
$links = ['index' => 'Dashboard', 'inventory' => 'Inventory', 'bundles' => 'Bundles', 'quotes' => 'Quotes'];
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title><?= e($pageTitle) ?> | DOi PC Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg admin-nav mb-4">
  <div class="container">
    <a class="brand-mark me-3" href="index.php"><b>D</b><b>O</b><b>i</b> <b style="color:var(--green)">PC</b> Admin</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#an" aria-label="Menu"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="an">
      <ul class="navbar-nav me-auto">
        <?php foreach ($links as $k => $label): ?>
        <li class="nav-item"><a class="nav-link <?= $active === $k ? 'active' : '' ?>" href="<?= $k ?>.php"><?= $label ?></a></li>
        <?php endforeach; ?>
      </ul>
      <div class="d-flex gap-2 align-items-center">
        <a class="btn btn-sm btn-outline-light" href="../index.php" target="_blank" rel="noopener">View website</a>
        <a class="btn btn-sm btn-outline-light" href="password.php">Password</a>
        <form method="post" action="logout.php" class="d-inline"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger">Log out</button></form>
      </div>
    </div>
  </div>
</nav>
<main class="container pb-5">
<?php if ($flash): ?><div class="alert alert-<?= e($flash[0]) ?> alert-dismissible fade show"><?= e($flash[1]) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
