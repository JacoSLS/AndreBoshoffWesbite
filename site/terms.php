<?php
require_once __DIR__ . '/includes/data.php';
$doc   = bww_legal()['terms'] ?? [];
$PAGE  = 'terms';
$TITLE = ($doc['title'] ?? 'Terms of Service') . ' | ' . s('business');
$DESC  = 'The terms on which ' . s('business') . ' provides this website and the information on it.';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/legal-body.php';
require __DIR__ . '/includes/footer.php';
