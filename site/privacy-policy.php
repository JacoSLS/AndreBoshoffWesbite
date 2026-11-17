<?php
require_once __DIR__ . '/includes/data.php';
$doc   = bww_legal()['privacy'] ?? [];
$PAGE  = 'privacy-policy';
$TITLE = ($doc['title'] ?? 'Privacy Policy') . ' | ' . s('business');
$DESC  = 'How ' . s('business') . ' collects, uses and protects the information you give us through this website.';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/legal-body.php';
require __DIR__ . '/includes/footer.php';
