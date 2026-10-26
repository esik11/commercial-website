<?php
require __DIR__ . '/../site.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/stock.php';
app_session();
$tel = preg_replace('/[^+\d]/', '', $site['phone']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($site['name']) ?> | Computer Parts, Accessories and Custom PC Builds</title>
  <meta name="description" content="<?= htmlspecialchars(str_replace('*', '', $site['tagline'])) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Poppins:wght@600;700;800;900&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
  <script type="application/ld+json">
  <?= json_encode(['@context'=>'https://schema.org','@type'=>'ComputerStore','name'=>$site['full_name'],'telephone'=>$site['phone'],'address'=>$site['address'],'openingHours'=>$site['hours']], JSON_UNESCAPED_SLASHES) ?>
  </script>
</head>
<body data-bs-spy="scroll" data-bs-target="#mainNav" data-bs-offset="90">
<div id="progress" aria-hidden="true"></div>
<header>
  <nav id="mainNav" class="navbar navbar-expand-lg sticky-top navbar-dark site-nav">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center gap-2" href="#top" aria-label="<?= htmlspecialchars($site['full_name']) ?>">
        <span class="wordmark"><b class="wm-d">D</b><b class="wm-o">O</b><b class="wm-i">i</b> <b class="wm-pc">PC</b></span>
        <span class="brand-sub d-none d-xl-inline">Computer Parts &amp; Accessories</span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
      <div class="collapse navbar-collapse" id="navMenu">
        <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
          <li class="nav-item"><a class="nav-link" href="#services">Services</a></li>
          <li class="nav-item"><a class="nav-link" href="#packages">Builds</a></li>
          <li class="nav-item"><a class="nav-link" href="#process">How it works</a></li>
          <li class="nav-item"><a class="nav-link" href="#why-us">Why us</a></li>
          <li class="nav-item"><a class="nav-link" href="#gallery">Gallery</a></li>
          <li class="nav-item"><a class="nav-link" href="#faq">FAQ</a></li>
          <li class="nav-item ms-lg-2"><a class="btn btn-accent" href="#quote">Get a free quote</a></li>
        </ul>
      </div>
    </div>
  </nav>
</header>
<main id="top">