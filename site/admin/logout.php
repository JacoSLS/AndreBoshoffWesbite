<?php
require_once __DIR__ . '/includes/auth.php';
// Signing out is a form button (POST with the security token), so another
// website cannot sign Andre out with a plain link.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !bww_csrf_ok()) { header('Location: dashboard.php'); exit; }
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
header('Location: index.php?bye=1');
exit;
