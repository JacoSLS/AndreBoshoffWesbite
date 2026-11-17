<?php
require_once __DIR__ . '/data.php';

$PAGE  = $PAGE  ?? bww_page();
$TITLE = $TITLE ?? s('business');
$DESC  = $DESC  ?? '';
$CANON = rtrim(s('site_url'), '/') . '/' . ($PAGE === 'index' ? '' : $PAGE);
$phoneLink = s('phone_link');
$phoneShow = s('phone_display');
$nav = bww_nav();

// Always serve the latest saved version: browsers re-check every time, and the
// host's LiteSpeed page cache (if switched on) never keeps an old copy.
// Admin preview pages (unsaved edits) are never stored at all.
header('Cache-Control: ' . (bww_preview_overlay() ? 'no-store' : 'no-cache, must-revalidate, max-age=0'));
header('X-LiteSpeed-Cache-Control: no-cache');

// Full address of a site image for Google and social sharing (absolute addresses are kept as they are).
$absUrl = fn(string $u) => preg_match('#^https?://#i', $u) ? $u : rtrim(s('site_url'), '/') . '/' . $u;
$ogImage = is_file(dirname(__DIR__) . '/assets/img/og-image.jpg') ? 'assets/img/og-image.jpg' : asset(c('header.logo'), 'img/logo.png');
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($TITLE) ?></title>
<meta name="description" content="<?= e($DESC) ?>">
<?php if ($PAGE === '404'): ?><base href="/"><?php else: ?><link rel="canonical" href="<?= e($CANON) ?>"><?php endif; ?>
<meta name="robots" content="<?= $PAGE === '404' ? 'noindex, follow' : 'index, follow' ?>">
<meta property="og:type" content="website">
<meta property="og:title" content="<?= e($TITLE) ?>">
<meta property="og:description" content="<?= e($DESC) ?>">
<meta property="og:url" content="<?= e($CANON) ?>">
<meta property="og:site_name" content="<?= e(s('business')) ?>">
<meta property="og:image" content="<?= e($absUrl($ogImage)) ?>">
<?php if ($ogImage === 'assets/img/og-image.jpg'): ?>
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<?php endif; ?>
<meta name="twitter:card" content="summary_large_image">
<meta name="theme-color" content="#0b5fa5">
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="assets/img/favicon-32.png" sizes="32x32">
<link rel="apple-touch-icon" href="assets/img/favicon-180.png">
<link rel="preload" href="assets/fonts/montserrat-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
<?php $cssv = @filemtime(dirname(__DIR__) . '/assets/css/style.css') ?: 1; ?>
<link rel="stylesheet" href="assets/css/style.css?v=<?= $cssv ?>">
<?php if (s('gsc_verify')): ?>
<meta name="google-site-verification" content="<?= e(s('gsc_verify')) ?>">
<?php endif; ?>
<script type="application/ld+json">
<?= json_encode(array_filter([
  '@context' => 'https://schema.org',
  '@type'    => 'Plumber',
  'name'     => s('business'),
  'slogan'   => c('footer.tagline', s('tagline')),
  'url'      => s('site_url'),
  'image'    => $absUrl(asset(c('header.logo'), 'img/logo.png')),
  'telephone'=> s('phone_display'),
  'email'    => s('email'),
  'founder'  => ['@type' => 'Person', 'name' => s('owner')],
  'foundingDate' => s('founded'),
  'address'  => ['@type' => 'PostalAddress', 'addressLocality' => 'Freeland', 'addressRegion' => 'MD', 'addressCountry' => 'US'],
  'areaServed' => array_map(fn($a) => ['@type' => 'AdministrativeArea', 'name' => $a . ', Maryland'], s('areas', [])),
  'makesOffer' => array_map(fn($sv) => [
    '@type' => 'Offer',
    'itemOffered' => ['@type' => 'Service', 'name' => $sv['name'] ?? '', 'description' => $sv['short'] ?? ''],
  ], bww_services()),
]), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_AMP) ?>
</script>
<?php if (s('ga4_id')): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e(s('ga4_id')) ?>"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());gtag('config','<?= e(s('ga4_id')) ?>');</script>
<?php endif; ?>
<?php if (s('meta_pixel_id')): ?>
<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','<?= e(s('meta_pixel_id')) ?>');fbq('track','PageView');</script>
<?php endif; ?>
</head>
<body>

<?php if (shown('header.show_topbar')): ?>
<div class="topbar">
  <div class="wrap">
    <div class="topbar__list">
      <span class="topbar__item"><?= icon('phone') ?> <a href="tel:<?= e($phoneLink) ?>"><?= e($phoneShow) ?></a></span>
      <?php if (s('email')): ?>
      <span class="topbar__item"><?= icon('mail') ?> <a href="mailto:<?= e(s('email')) ?>"><?= e(s('email')) ?></a></span>
      <?php endif; ?>
    </div>
    <?php if (c('header.topbar_note')): ?>
    <div class="topbar__list topbar__list--secondary">
      <span class="topbar__item"><?= icon('clock') ?> <?= e(c('header.topbar_note')) ?></span>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<header class="header">
  <div class="wrap">
    <a class="brand" href="/" aria-label="<?= e(s('business')) ?> — home">
      <?php $logo = asset_sm(asset(c('header.logo'), 'img/logo.png')); ?>
      <img src="<?= e($logo) ?>"<?= img_size($logo) ?> alt="<?= e(s('business')) ?>">
    </a>

    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="mainnav" aria-label="Menu"><span></span></button>

    <nav class="nav" id="mainnav">
      <?php foreach ($nav as $item):
        if (isset($item['show']) && !$item['show']) continue;
        $slug = $item['slug'] ?? ''; if ($slug === '') continue; ?>
        <a href="<?= e(page_url($slug)) ?>"<?= $PAGE === $slug ? ' aria-current="page"' : '' ?>><?= e($item['label'] ?? $slug) ?></a>
      <?php endforeach; ?>
      <a class="btn btn--call" href="tel:<?= e($phoneLink) ?>"><?= icon('phone') ?> Call <?= e($phoneShow) ?></a>
    </nav>

    <?php if (shown('header.show_header_cta')): ?>
    <a class="btn btn--call header__cta" href="tel:<?= e($phoneLink) ?>"><?= icon('phone') ?> <?= e($phoneShow) ?></a>
    <?php endif; ?>
  </div>
</header>

<main id="main">
