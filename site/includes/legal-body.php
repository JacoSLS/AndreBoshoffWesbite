<?php /** Shared rendering for the two legal pages. Expects $doc. */ ?>
<section class="page-hero">
  <div class="wrap">
    <p class="crumbs"><a href="/">Home</a> &nbsp;/&nbsp; <?= e($doc['title'] ?? '') ?></p>
    <h1><?= e($doc['title'] ?? '') ?></h1>
    <?php if (!empty($doc['updated'])): ?><p>Last updated <?= e($doc['updated']) ?></p><?php endif; ?>
  </div>
</section>

<section class="section">
  <div class="wrap prose">
    <?php if (!empty($doc['show_notice']) && !empty($doc['notice'])): ?>
      <div class="alert alert--info"><strong>A note for the business owner:</strong> <?= e($doc['notice']) ?></div>
    <?php endif; ?>

    <?= bww_rich($doc['intro'] ?? '') ?>

    <?php foreach (($doc['sections'] ?? []) as $sec): ?>
      <?php if (trim($sec['heading'] ?? '') !== ''): ?><h2><?= e($sec['heading']) ?></h2><?php endif; ?>
      <?= bww_rich($sec['body'] ?? '') ?>
    <?php endforeach; ?>

    <?php if (!empty($doc['show_contact_block'])): ?>
      <h2>Contact</h2>
      <p><?= e(s('business')) ?><br>
      <?= e(s('base_town')) ?><br>
      Phone: <a href="tel:<?= e(s('phone_link')) ?>"><?= e(s('phone_display')) ?></a><br>
      <?php if (s('email')): ?>Email: <a href="mailto:<?= e(s('email')) ?>"><?= e(s('email')) ?></a><?php endif; ?></p>
    <?php endif; ?>
  </div>
</section>
