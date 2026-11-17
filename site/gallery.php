<?php
require_once __DIR__ . '/includes/data.php';
$PAGE  = 'gallery';
$TITLE = c('gallery_page.meta_title', 'Our Work') . ' | ' . s('business');
$DESC  = c('gallery_page.meta_desc');
require __DIR__ . '/includes/header.php';

$posts    = bww_gallery();
$services = bww_services();

// Only offer a filter for categories that actually have posts in them.
$used = [];
foreach ($posts as $p) { $used[$p['category'] ?? 'general'] = true; }

$placeholders = [
  ['ph-1.svg', 'Well pump replacement', 'A failed submersible pulled and replaced, with the pressure back to normal.'],
  ['ph-2.svg', 'Well tank replacement', 'New pressure tank fitted and the cut-in pressure set correctly.'],
  ['ph-3.svg', 'Well yield testing', 'Draw-down testing in progress, with the results written up for the owner.'],
  ['ph-4.svg', 'Water testing', 'Samples taken on site before any treatment gets recommended.'],
  ['ph-5.svg', 'Water treatment', 'Filtration and softening installed and plumbed in tidily.'],
  ['ph-6.svg', 'Plumbing', 'Pipe work repaired and pressure restored throughout the house.'],
];
?>

<section class="page-hero">
  <div class="wrap">
    <p class="crumbs"><a href="/">Home</a> &nbsp;/&nbsp; <?= e(c('gallery_page.hero.heading')) ?></p>
    <?php if (c('gallery_page.hero.eyebrow')): ?><span class="eyebrow"><?= e(c('gallery_page.hero.eyebrow')) ?></span><?php endif; ?>
    <h1><?= e(c('gallery_page.hero.heading')) ?></h1>
    <?php if (c('gallery_page.hero.text')): ?><p><?= e(c('gallery_page.hero.text')) ?></p><?php endif; ?>
  </div>
</section>

<section class="section">
  <div class="wrap">

    <?php if ($posts): ?>

      <?php if (shown('gallery_page.show_filters') && count($used) > 1): ?>
      <div class="gallery-filters">
        <button class="filter-btn is-active" data-filter="all" type="button"><?= e(c('gallery_page.all_label', 'All work')) ?></button>
        <?php foreach ($services as $sv): if (empty($used[$sv['id']])) continue; ?>
          <button class="filter-btn" data-filter="<?= e($sv['id']) ?>" type="button"><?= e($sv['tag'] ?? $sv['name']) ?></button>
        <?php endforeach; ?>
        <?php if (!empty($used['general'])): ?>
          <button class="filter-btn" data-filter="general" type="button">General work</button>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <h2 class="sr-only"><?= e(c('gallery_page.hero.heading') ?: 'Our work') ?></h2>
      <div class="gallery-grid">
        <?php $first = true; foreach ($posts as $i => $p):
          $photos = array_values(array_filter($p['photos'] ?? [], fn($ph) => !empty($ph['file'])));
          if (!$photos) continue;
          $cat = $p['category'] ?? 'general';
          $n   = count($photos);
        ?>
        <article class="post" data-category="<?= e($cat) ?>" data-post="<?= e($p['id']) ?>">
          <div class="post__media" data-rotate="<?= $n > 1 ? '1' : '0' ?>" style="--delay:<?= ($i % 4) * 0.9 ?>s">
            <?php foreach ($photos as $k => $ph): ?>
              <?php // only the first photo loads with the page; the others load just before they rotate in ?>
              <?php $gimg = bww_card_img('assets/uploads/' . basename($ph['file'] ?? $ph['thumb']), 720, 540); ?>
              <img <?= $k === 0 ? 'src' : 'data-src' ?>="<?= e(implode('/', array_map('rawurlencode', explode('/', $gimg)))) ?>"<?= img_size($gimg) ?>
                   alt="<?= e($p['title'] ?? 'Completed work') ?>"
                   class="post__img<?= $k === 0 ? ' is-active' : '' ?>"<?= $first && $k === 0 ? ' fetchpriority="high"' : ' loading="lazy"' ?>>
            <?php endforeach; ?>
            <?php if ($cat !== 'general'): ?>
              <span class="post__tag"><?= e(bww_service_tag($cat)) ?></span>
            <?php endif; ?>
            <?php if ($n > 1): ?>
              <span class="post__count"><?= icon('image') ?> <?= $n ?></span>
              <span class="post__dots">
                <?php for ($k = 0; $k < $n; $k++): ?><i<?= $k === 0 ? ' class="on"' : '' ?>></i><?php endfor; ?>
              </span>
            <?php endif; ?>
          </div>
          <div class="post__body">
            <h3><?= e($p['title'] ?? 'Completed work') ?></h3>
            <?php if (!empty($p['caption'])): ?><p><?= e($p['caption']) ?></p><?php endif; ?>
            <button type="button" class="post__more">View job<span class="sr-only">: <?= e($p['title'] ?? 'Completed work') ?></span> <?= icon('arrow') ?></button>
          </div>
        </article>
        <?php $first = false; endforeach; ?>
      </div>

      <div class="empty-state" id="gallery-none" style="display:none;margin-top:2rem">
        <h3><?= e(c('gallery_page.empty_heading')) ?></h3>
        <p><?= e(c('gallery_page.empty_text')) ?></p>
      </div>

      <script type="application/json" id="post-data"><?= json_encode(array_map(function ($p) {
        return [
          'id'    => $p['id'] ?? '',
          'title' => $p['title'] ?? '',
          'caption' => $p['caption'] ?? '',
          'body'  => $p['body'] ?? '',
          'tag'   => ($p['category'] ?? 'general') !== 'general' ? bww_service_tag($p['category']) : '',
          'photos'=> array_values(array_map(fn($ph) => 'assets/uploads/' . rawurlencode($ph['file']),
                       array_filter($p['photos'] ?? [], fn($ph) => !empty($ph['file'])))),
        ];
      }, $posts), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>

    <?php else: ?>

      <div class="alert alert--info">
        <strong>Placeholder images.</strong> These are stand-ins showing how the gallery will look. Real job photos replace them as soon as they are added through the staff login.
      </div>

      <div class="gallery-grid">
        <?php foreach ($placeholders as [$file, $title, $caption]): ?>
        <article class="post" style="cursor:default">
          <div class="post__media"><img src="assets/img/<?= e($file) ?>" alt="<?= e($title) ?> placeholder" class="post__img is-active" loading="lazy"></div>
          <div class="post__body"><h3><?= e($title) ?></h3><p><?= e($caption) ?></p></div>
        </article>
        <?php endforeach; ?>
      </div>

    <?php endif; ?>

  </div>
</section>

<div class="viewer" id="viewer" role="dialog" aria-modal="true" aria-label="Job details">
  <button class="viewer__close" type="button" aria-label="Close">&times;</button>
  <div class="viewer__inner">
    <div class="viewer__stage">
      <button class="viewer__nav viewer__nav--prev" type="button" aria-label="Previous photo">&#8249;</button>
      <img id="viewer-img" src="" alt="">
      <button class="viewer__nav viewer__nav--next" type="button" aria-label="Next photo">&#8250;</button>
      <span class="viewer__counter" id="viewer-counter"></span>
    </div>
    <div class="viewer__thumbs" id="viewer-thumbs"></div>
    <div class="viewer__info">
      <span class="viewer__tag" id="viewer-tag"></span>
      <h3 id="viewer-title"></h3>
      <p class="viewer__caption" id="viewer-caption"></p>
      <div class="viewer__body" id="viewer-body"></div>
      <a class="btn btn--call btn--sm" href="tel:<?= e(s('phone_link')) ?>"><?= icon('phone') ?> Call about a job like this</a>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
