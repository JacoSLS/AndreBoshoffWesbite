<?php
require_once __DIR__ . '/includes/data.php';
$PAGE  = 'about';
$TITLE = c('about.meta_title', 'About Us') . ' | ' . s('business');
$DESC  = c('about.meta_desc');
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
  <div class="wrap">
    <p class="crumbs"><a href="/">Home</a> &nbsp;/&nbsp; <?= e(c('about.hero.heading')) ?></p>
    <?php if (c('about.hero.eyebrow')): ?><span class="eyebrow"><?= e(c('about.hero.eyebrow')) ?></span><?php endif; ?>
    <h1><?= e(c('about.hero.heading')) ?></h1>
    <?php if (c('about.hero.text')): ?><p><?= e(c('about.hero.text')) ?></p><?php endif; ?>
  </div>
</section>

<?php if (shown('about.story.show')): ?>
<section class="section">
  <div class="wrap">
    <div class="split">
      <div>
        <?php if (c('about.story.eyebrow')): ?><span class="eyebrow"><?= e(c('about.story.eyebrow')) ?></span><?php endif; ?>
        <h2><?= e(c('about.story.heading')) ?></h2>
        <?= bww_rich(c('about.story.body')) ?>
      </div>
      <?php if (asset(c('about.story.image'))): ?>
      <div><img src="<?= e(asset(c('about.story.image'))) ?>"<?= img_size(asset(c('about.story.image'))) ?> alt="" style="border-radius:10px"></div>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php $vals = c('about.values.items', []); if (shown('about.values.show') && $vals): ?>
<section class="section section--soft">
  <div class="wrap">
    <div class="section-head center">
      <?php if (c('about.values.eyebrow')): ?><span class="eyebrow"><?= e(c('about.values.eyebrow')) ?></span><?php endif; ?>
      <h2><?= e(c('about.values.heading')) ?></h2>
    </div>
    <div class="grid grid-<?= count($vals) >= 4 ? 4 : max(2, count($vals)) ?>">
      <?php foreach ($vals as $v): ?>
      <article class="card reveal">
        <div class="card__icon"><?= icon($v['icon'] ?? 'checkc') ?></div>
        <h3><?= e($v['title'] ?? '') ?></h3>
        <p><?= e($v['text'] ?? '') ?></p>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (shown('about.who.show')): ?>
<section class="section">
  <div class="wrap">
    <div class="split">
      <?php if (asset(c('about.who.image'))): ?>
      <div><img src="<?= e(asset(c('about.who.image'))) ?>"<?= img_size(asset(c('about.who.image'))) ?> alt="" style="border-radius:10px" loading="lazy"></div>
      <?php endif; ?>
      <div>
        <?php if (c('about.who.eyebrow')): ?><span class="eyebrow"><?= e(c('about.who.eyebrow')) ?></span><?php endif; ?>
        <h2><?= e(c('about.who.heading')) ?></h2>
        <?= bww_rich(c('about.who.body')) ?>
        <?php $wp = c('about.who.points', []); if ($wp): ?>
        <ul class="checklist">
          <?php foreach ($wp as $p): ?><li><?= icon('check') ?><span><?= e($p) ?></span></li><?php endforeach; ?>
        </ul>
        <?php endif; ?>
        <div class="btn-row">
          <a class="btn btn--call" href="tel:<?= e(s('phone_link')) ?>"><?= icon('phone') ?> Call <?= e(s('phone_display')) ?></a>
          <a class="btn btn--ghost" href="/contact">Send a message</a>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
