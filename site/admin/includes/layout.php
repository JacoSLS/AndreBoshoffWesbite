<?php
/** Shared chrome for the admin pages. Set $ADMIN_PAGE and $ADMIN_TITLE first. */
$ADMIN_PAGE  = $ADMIN_PAGE  ?? '';
$ADMIN_TITLE = $ADMIN_TITLE ?? 'Admin';
$flash = bww_flash();

// Unsaved preview edits belong to the page they were typed on; opening any
// admin page starts clean so the preview never shows half-finished work.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') unset($_SESSION['bww_preview']);

// Which page of the website the live preview pane shows for each admin tab.
$PV_URL = [
  'page-about'    => '../about',
  'page-services' => '../services',
  'gallery'       => '../gallery',
  'page-reviews'  => '../reviews',
  'page-contact'  => '../contact',
  'legal'         => '../privacy-policy',
][$ADMIN_PAGE] ?? '../';

$tabs = [
  'dashboard'     => 'Overview',
  'page-home'     => 'Home',
  'page-about'    => 'About Us',
  'page-services' => 'Services',
  'gallery'       => 'Gallery',
  'page-reviews'  => 'Reviews',
  'page-contact'  => 'Contact',
  'branding'      => 'Header &amp; Footer',
  'legal'         => 'Legal',
  'settings'      => 'Settings',
];
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($ADMIN_TITLE) ?> — <?= e(s('business')) ?></title>
<link rel="icon" href="../assets/img/favicon-32.png" sizes="32x32">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/admin.css?v=<?= @filemtime(dirname(__DIR__) . '/assets/admin.css') ?>">
</head>
<body data-upload-limit="<?= bww_upload_limit() ?>">

<div class="abar">
  <div class="wrap">
    <img src="../assets/img/logo-white.png" alt="<?= e(s('business')) ?>">
    <button type="button" class="pv-open" id="pvOpen" aria-pressed="false">Live preview</button>
    <span class="who">Signed in as <?= e($_SESSION['bww_user'] ?? '') ?></span>
    <form method="post" action="logout" class="out-form"><?= bww_csrf_field() ?><button type="submit" class="out">Sign out</button></form>
  </div>
</div>

<nav class="anav" id="anav">
  <button type="button" class="anav-toggle" id="anavToggle" aria-expanded="false" aria-controls="anavLinks">
    <span class="anav-burger" aria-hidden="true"></span>
    <span><?= $tabs[$ADMIN_PAGE] ?? 'Menu' ?></span>
  </button>
  <div class="wrap" id="anavLinks">
    <?php foreach ($tabs as $slug => $label): ?>
      <a href="<?= e($slug) ?>.php" class="<?= $ADMIN_PAGE === $slug ? 'on' : '' ?>"><?= $label ?></a>
    <?php endforeach; ?>
    <a href="../index.php" target="_blank" rel="noopener">View website &nearr;</a>
  </div>
</nav>
<script>
document.getElementById('anavToggle').addEventListener('click', function () {
  var open = document.getElementById('anav').classList.toggle('is-open');
  this.setAttribute('aria-expanded', open);
});
</script>

<main>
  <div class="wrap">

    <?php if (bww_using_default_password()): ?>
      <div class="alert alert--warn">
        <strong>You are still using the password that came with the site.</strong>
        Please <a href="settings.php#password">choose your own password</a> now — it takes ten seconds and it matters.
      </div>
    <?php endif; ?>

    <?php if ($flash): ?>
      <div class="alert alert--<?= e($flash['t']) ?>"><?= $flash['m'] ?></div>
    <?php endif; ?>
