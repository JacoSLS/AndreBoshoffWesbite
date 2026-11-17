<?php
require_once __DIR__ . '/includes/data.php';
$PAGE  = 'index';
$TITLE = c('home.meta_title') ? c('home.meta_title') . ' | ' . s('business') : s('business');
$DESC  = c('home.meta_desc');
require __DIR__ . '/includes/header.php';
$services = bww_services();
?>

<?php
$ban_full = asset(c('home.banner.image'), 'img/hero-banner.jpg');
$ban_mob  = asset(c('home.banner.image_mobile'));
$ban_alt  = c('home.banner.alt') ?: s('business') . ' - ' . s('tagline');
?>
<section class="hero-banner">
  <h1 class="sr-only"><?= e(c('home.banner.heading') ?: s('business') . ' — well pump, water testing and water treatment services in northern Maryland') ?></h1>
  <picture>
    <?php if ($ban_mob): ?>
    <source media="(max-width: 700px)" srcset="<?= e($ban_mob) ?>">
    <?php endif; ?>
    <?php $ban_sm = asset_sm($ban_full); ?>
    <img src="<?= e($ban_full) ?>"<?php if ($ban_sm !== $ban_full): $sw = @getimagesize(BWW_ROOT . '/' . $ban_sm)[0]; $fw = @getimagesize(BWW_ROOT . '/' . $ban_full)[0]; ?> srcset="<?= e($ban_sm) ?> <?= (int)$sw ?>w, <?= e($ban_full) ?> <?= (int)$fw ?>w" sizes="100vw"<?php endif; ?><?= img_size($ban_full) ?> alt="<?= e($ban_alt) ?>" style="display:block;width:100%;height:auto" fetchpriority="high" decoding="async">
  </picture>
</section>

<?php $trust = c('home.trust.items', []); if (shown('home.trust.show') && $trust): ?>
<section class="trust">
  <div class="wrap">
    <div class="trust__grid">
      <?php foreach ($trust as $t): ?>
      <div class="trust__block">
        <h2><?= e($t['title'] ?? '') ?></h2>
        <p><?= e($t['text'] ?? '') ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php
// Services highlights: the cards from the Services page, without photos.
// Wording is edited under Admin > Services; Admin > Home only shows/hides it,
// turns the icons on or off, and decides whether it sits above or below "Why".
ob_start();
if (shown('home.services_section.show') && $services): ?>
<section class="section">
  <div class="wrap">
    <div class="section-head center">
      <?php if (c('services_page.hero.eyebrow')): ?><span class="eyebrow"><?= e(c('services_page.hero.eyebrow')) ?></span><?php endif; ?>
      <h2><?= e(c('services_page.hero.heading') ?: 'Our services') ?></h2>
    </div>

    <div class="grid grid-3">
      <?php foreach ($services as $sv): ?>
      <article class="card reveal">
        <?php if (shown('home.services_section.icons')): ?>
          <div class="card__icon"><?= icon($sv['icon'] ?? 'drop') ?></div>
        <?php endif; ?>
        <h3><?= e($sv['name']) ?></h3>
        <p><?= e($sv['short'] ?? '') ?></p>
        <a class="card__link" href="<?= e(page_url('services', (shown('services_page.cards_show') ? 'card-' : '') . $sv['id'])) ?>">Details <?= icon('arrow') ?></a>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif;
$services_html = ob_get_clean();

ob_start(); ?>
<?php if (shown('home.why.show')): ?>
<section class="section section--soft">
  <div class="wrap">
    <div class="split">
      <div>
        <?php if (c('home.why.eyebrow')): ?><span class="eyebrow"><?= e(c('home.why.eyebrow')) ?></span><?php endif; ?>
        <h2><?= e(c('home.why.heading')) ?></h2>
        <?= bww_rich(c('home.why.body')) ?>
        <?php $wp = c('home.why.points', []); if ($wp): ?>
        <ul class="checklist">
          <?php foreach ($wp as $p): ?>
          <li><?= icon('check') ?><span><strong><?= e($p['strong'] ?? '') ?></strong> <?= e($p['text'] ?? '') ?></span></li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
        <?php if (c('home.why.cta_label')): ?>
        <div class="btn-row"><a class="btn btn--primary" href="/about"><?= e(c('home.why.cta_label')) ?></a></div>
        <?php endif; ?>
      </div>
      <?php if (asset(c('home.why.image'))): ?>
      <div><img src="<?= e(asset(c('home.why.image'))) ?>"<?= img_size(asset(c('home.why.image'))) ?> alt="" style="border-radius:10px" loading="lazy"></div>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<?php
$why_html = ob_get_clean();

echo c('home.services_section.order') === 'why_first' ? $why_html . $services_html : $services_html . $why_html;
?>

<?php if (shown('home.area.show')): ?>
<section class="section">
  <div class="wrap">
    <div class="section-head center">
      <?php if (c('home.area.eyebrow')): ?><span class="eyebrow"><?= e(c('home.area.eyebrow')) ?></span><?php endif; ?>
      <h2><?= e(c('home.area.heading')) ?></h2>
      <?php if (c('home.area.text')): ?><p class="lead"><?= e(s('area_note')) ?> <?= e(c('home.area.text')) ?></p><?php endif; ?>
    </div>
    <div class="center">
      <ul class="area-tags" style="justify-content:center">
        <?php foreach (s('areas', []) as $a): ?><li><?= e($a) ?></li><?php endforeach; ?>
        <?php if (c('home.area.extra_tag')): ?><li><?= e(c('home.area.extra_tag')) ?></li><?php endif; ?>
      </ul>
    </div>

    <?php $stats = c('home.stats.items', []); if (shown('home.stats.show') && $stats): ?>
    <div class="stats" style="margin-top:3rem">
      <?php foreach ($stats as $st): ?>
      <div class="stat"><strong><?= e($st['value'] ?? '') ?></strong><span><?= e($st['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
