<?php
require_once __DIR__ . '/includes/auth.php';

if (bww_logged_in()) { header('Location: dashboard.php'); exit; }

$error = '';
$locked = bww_locked_out();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$locked) {
    if (!bww_csrf_ok()) {
        $error = 'Your session timed out. Please try again.';
    } else {
        $u = trim($_POST['user'] ?? '');
        $p = (string)($_POST['pass'] ?? '');
        if (bww_attempt_login($u, $p)) {
            header('Location: dashboard.php');
            exit;
        }
        bww_register_failure();
        $locked = bww_locked_out();
        $error = $locked
            ? 'Too many attempts. Please wait ' . $locked . ' minutes and try again.'
            : 'That username or password was not right. Please try again.';
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Staff Login — <?= e(s('business')) ?></title>
<link rel="icon" href="../assets/img/favicon-32.png" sizes="32x32">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/admin.css?v=<?= @filemtime(__DIR__ . '/assets/admin.css') ?>">
</head>
<body>
<div class="login-wrap">
  <div class="login">
    <img src="../assets/img/logo.png" alt="<?= e(s('business')) ?>">
    <h1>Staff login</h1>
    <p class="sub">Sign in to manage your photos and website details.</p>

    <?php if (!empty($_GET['expired'])): ?>
      <div class="alert alert--info">Please sign in to continue.</div>
    <?php endif; ?>
    <?php if (!empty($_GET['bye'])): ?>
      <div class="alert alert--ok">You have been signed out.</div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert--err"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="on">
      <?= bww_csrf_field() ?>
      <div class="field">
        <label for="user">Username</label>
        <input type="text" id="user" name="user" autocomplete="username" required autofocus <?= $locked ? 'disabled' : '' ?>>
      </div>
      <div class="field">
        <label for="pass">Password</label>
        <div class="pw">
          <input type="password" id="pass" name="pass" autocomplete="current-password" required <?= $locked ? 'disabled' : '' ?>>
          <button type="button" class="pw-eye" id="pwEye" aria-label="Show password" aria-pressed="false">
            <svg class="eye-on" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg class="eye-off" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.94 17.94A10.5 10.5 0 0 1 12 19C5 19 1 12 1 12a18.5 18.5 0 0 1 5.06-5.94M9.9 4.24A9.6 9.6 0 0 1 12 4c7 0 11 7 11 7a18.6 18.6 0 0 1-2.16 3.19M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
          </button>
        </div>
      </div>
      <button class="btn btn--primary btn--block" type="submit" <?= $locked ? 'disabled' : '' ?>>Sign in</button>
    </form>

    <a class="back" href="../index.php">&larr; Back to the website</a>
  </div>
</div>
<script>
(function(){var b=document.getElementById('pwEye'),i=document.getElementById('pass');
b.addEventListener('click',function(){var show=i.type==='password';i.type=show?'text':'password';
b.setAttribute('aria-pressed',show);b.setAttribute('aria-label',show?'Hide password':'Show password');i.focus();});})();
</script>
</body>
</html>
