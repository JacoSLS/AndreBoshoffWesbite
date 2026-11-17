<?php
require_once __DIR__ . '/includes/data.php';
$PAGE  = 'services';
$TITLE = c('services_page.meta_title', 'Our Services') . ' | ' . s('business');
$DESC  = c('services_page.meta_desc');
require __DIR__ . '/includes/header.php';
$services = bww_services();
?>

<section class="page-hero">
  <div class="wrap">
    <p class="crumbs"><a href="/">Home</a> &nbsp;/&nbsp; <?= e(c('services_page.hero.heading')) ?></p>
    <?php if (c('services_page.hero.eyebrow')): ?><span class="eyebrow"><?= e(c('services_page.hero.eyebrow')) ?></span><?php endif; ?>
    <h1><?= e(c('services_page.hero.heading')) ?></h1>
    <?php if (c('services_page.hero.text')): ?><p><?= e(c('services_page.hero.text')) ?></p><?php endif; ?>
  </div>
</section>

<?php if (!$services): ?>
<section class="section"><div class="wrap"><div class="empty-state">
  <h3>No services listed yet</h3>
  <p>Services added in the admin panel appear here automatically.</p>
</div></div></section>
<?php endif; ?>

<?php if (shown('services_page.cards_show') && $services): ?>
<section class="section--tight section">
  <div class="wrap">
    <h2 class="sr-only">Services at a glance</h2>
    <div class="grid grid-3">
      <?php foreach ($services as $sv): ?>
      <article class="card reveal" id="card-<?= e($sv['id']) ?>">
        <?php if (!empty($sv['image'])): ?>
          <?php $cimg = bww_card_img(asset($sv['image']), 720, 450); ?>
          <div class="card__photo"><img src="<?= e($cimg) ?>"<?= img_size($cimg) ?> alt="<?= e($sv['name']) ?>"<?= empty($first_photo_done) ? ' fetchpriority="high"' : ' loading="lazy"' ?>></div>
          <?php $first_photo_done = true; ?>
        <?php else: ?>
          <div class="card__icon"><?= icon($sv['icon'] ?? 'drop') ?></div>
        <?php endif; ?>
        <h3><?= e($sv['name']) ?></h3>
        <p><?= e($sv['short'] ?? '') ?></p>
        <?php if (shown('services_page.detail.show')): ?>
        <a class="card__link" href="#<?= e($sv['id']) ?>">Details <?= icon('arrow') ?></a>
        <?php endif; ?>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (shown('services_page.detail.show') && $services): ?>
<section class="section section--soft">
  <div class="wrap">
    <div class="section-head">
      <?php if (c('services_page.detail.eyebrow')): ?><span class="eyebrow"><?= e(c('services_page.detail.eyebrow')) ?></span><?php endif; ?>
      <h2><?= e(c('services_page.detail.heading')) ?></h2>
      <?php if (c('services_page.detail.text')): ?><p class="lead"><?= e(c('services_page.detail.text')) ?></p><?php endif; ?>
    </div>

    <div class="detail-card">
      <?php foreach ($services as $sv): ?>
      <article class="service-detail" id="<?= e($sv['id']) ?>">
        <div class="card__icon"><?= icon($sv['icon'] ?? 'drop') ?></div>
        <div>
          <h3><?= e($sv['name']) ?></h3>
          <?= bww_rich($sv['body'] ?? '') ?>
          <?php if (!empty($sv['bullets'])): ?>
          <ul><?php foreach ($sv['bullets'] as $b): ?><li><?= e($b) ?></li><?php endforeach; ?></ul>
          <?php endif; ?>
          <?php if (!empty($sv['image'])): ?>
          <?php $dimg = asset_sm(asset($sv['image'])); ?>
          <div class="service-detail__photo"><img src="<?= e($dimg) ?>"<?= img_size($dimg) ?> alt="<?= e($sv['name']) ?>" loading="lazy"></div>
          <?php endif; ?>
          <?php if (c('services_page.detail.cta_label')): ?>
          <div class="btn-row" style="margin-top:1.2rem">
            <a class="btn btn--call btn--sm" href="tel:<?= e(s('phone_link')) ?>"><?= icon('phone') ?> <?= e(c('services_page.detail.cta_label')) ?></a>
          </div>
          <?php endif; ?>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php $sym = c('services_page.symptoms.items', []); if (shown('services_page.symptoms.show') && $sym): ?>
<section class="section">
  <div class="wrap">
    <div class="section-head center">
      <?php if (c('services_page.symptoms.eyebrow')): ?><span class="eyebrow"><?= e(c('services_page.symptoms.eyebrow')) ?></span><?php endif; ?>
      <h2><?= e(c('services_page.symptoms.heading')) ?></h2>
      <?php if (c('services_page.symptoms.text')): ?><p class="lead"><?= e(c('services_page.symptoms.text')) ?></p><?php endif; ?>
    </div>
    <div class="grid grid-3">
      <?php foreach ($sym as $s2): ?>
      <article class="card"><h3><?= e($s2['title'] ?? '') ?></h3><p><?= e($s2['text'] ?? '') ?></p></article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
