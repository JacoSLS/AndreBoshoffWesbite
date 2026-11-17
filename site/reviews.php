<?php
require_once __DIR__ . '/includes/data.php';
$PAGE  = 'reviews';
$TITLE = (c('reviews_page.meta_title') ?: 'Customer Reviews') . ' | ' . s('business');
$DESC  = c('reviews_page.meta_desc') ?: 'What customers say about ' . s('business') . ' — well pump, tank, water testing, treatment and plumbing work across northern Maryland.';
require __DIR__ . '/includes/header.php';
$reviews = bww_reviews();
// Long reviews are shortened to this many characters and open in full when tapped (0 = never shorten).
$trim = c('reviews_page.trim_chars');
$trim = ($trim === '' || $trim === null) ? 30 : max(0, (int)$trim);
$heading = c('reviews_page.hero.heading') ?: 'What Our Clients Are Saying';
$eyebrow = c('reviews_page.hero.eyebrow') ?: 'Customer Testimonials';
?>

<section class="page-hero">
  <div class="wrap">
    <p class="crumbs"><a href="/">Home</a> &nbsp;/&nbsp; <?= e($heading) ?></p>
    <span class="eyebrow"><?= e($eyebrow) ?></span>
    <h1><?= e($heading) ?></h1>
    <?php if (c('reviews_page.hero.text')): ?><p><?= e(c('reviews_page.hero.text')) ?></p><?php endif; ?>
  </div>
</section>

<?php if ($reviews): ?>
<section class="section">
  <div class="wrap">
    <div class="reviews<?= c('reviews_page.layout') === 'list' ? ' reviews--list' : '' ?>">
      <?php foreach ($reviews as $r):
        $q = trim($r['quote']);
        $short = '';
        if ($trim && mb_strlen($q) > $trim) {
            $short = mb_substr(preg_replace('/\s+/u', ' ', $q), 0, $trim);
            $sp = mb_strrpos($short, ' ');
            if ($sp !== false && $sp >= $trim / 2) $short = mb_substr($short, 0, $sp);   // end on a whole word
            $short = rtrim($short, " ,.;:!?-–—");
        } ?>
      <figure class="review<?= $short !== '' ? ' review--more' : '' ?>"<?= $short !== '' ? ' tabindex="0" role="button" aria-expanded="false"' : '' ?>>
        <svg class="review__icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3 10v4a1 1 0 0 0 1 1h2l1.6 5.2a1 1 0 0 0 1 .8h1.2a1 1 0 0 0 1-1.3L9.4 15H11l7 4V5l-7 4H4a1 1 0 0 0-1 1Zm17-2v8a3 3 0 0 0 0-8Z"/></svg>
        <?php if ($short !== ''): ?>
        <blockquote>&ldquo;<span class="review__short"><?= e($short) ?>&hellip;</span><span class="review__full" hidden><?= nl2br(e($q)) ?></span>&rdquo;</blockquote>
        <?php else: ?>
        <blockquote>&ldquo;<?= nl2br(e($q)) ?>&rdquo;</blockquote>
        <?php endif; ?>
        <?php if (trim($r['name'] ?? '') !== ''): ?><figcaption><?= e(trim($r['name'])) ?></figcaption><?php endif; ?>
      </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
