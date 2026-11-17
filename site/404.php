<?php
require_once __DIR__ . '/includes/data.php';
http_response_code(404);
$PAGE  = '404';
$TITLE = 'Page not found | ' . s('business');
$DESC  = 'That page could not be found.';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
  <div class="wrap">
    <h1>That page has gone missing</h1>
    <p>The link may be out of date, or the address may have a typo in it. Nothing to worry about — everything else is where it should be.</p>
  </div>
</section>

<section class="section">
  <div class="wrap center">
    <h2>Where would you like to go?</h2>
    <div class="btn-row" style="justify-content:center">
      <a class="btn btn--primary" href="/">Home page</a>
      <a class="btn btn--ghost" href="/services">Our services</a>
      <a class="btn btn--ghost" href="/contact">Contact us</a>
    </div>
    <p style="margin-top:2rem">Or simply call us on <a href="tel:<?= e(s('phone_link')) ?>"><strong><?= e(s('phone_display')) ?></strong></a>.</p>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
