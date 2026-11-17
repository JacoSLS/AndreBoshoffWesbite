<?php
$phoneLink = s('phone_link');
$phoneShow = s('phone_display');
$services  = bww_services();
$nav       = bww_nav();
?>
</main>

<?php if (shown('footer.band_show')): ?>
<section class="band">
  <div class="wrap">
    <div>
      <h2><?= e(c('footer.band_heading')) ?></h2>
      <?php if (s('emergency_line')): ?><p><?= e(s('emergency_line')) ?></p><?php endif; ?>
    </div>
    <a class="btn btn--light" href="tel:<?= e($phoneLink) ?>"><?= icon('phone') ?> <?= e($phoneShow) ?></a>
  </div>
</section>
<?php endif; ?>

<footer class="footer">
  <div class="wrap">
    <div class="footer__grid">

      <div>
        <div class="footer__logo">
          <?php $logo_w = asset_sm(asset(c('header.logo_white'), 'img/logo-white.png')); ?>
          <img src="<?= e($logo_w) ?>"<?= img_size($logo_w) ?> alt="<?= e(s('business')) ?>" loading="lazy">
        </div>
        <?php if (c('footer.tagline')): ?><p class="footer__tagline"><?= e(c('footer.tagline')) ?></p><?php endif; ?>
        <p><?= e(c('footer.about_text')) ?></p>
        <?php if (s('facebook')): ?>
        <div class="socials">
          <a href="<?= e(s('facebook')) ?>" target="_blank" rel="noopener" aria-label="Facebook"><?= icon('facebook') ?></a>
        </div>
        <?php endif; ?>
      </div>

      <div>
        <h3><?= e(c('footer.col_pages', 'Pages')) ?></h3>
        <ul>
          <?php foreach ($nav as $item):
            if (isset($item['show']) && !$item['show']) continue; ?>
            <li><a href="<?= e(page_url($item['slug'])) ?>"><?= e($item['label'] ?? $item['slug']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div>
        <h3><?= e(c('footer.col_services', 'Services')) ?></h3>
        <ul>
          <?php foreach ($services as $sv): ?>
          <li><a href="<?= e(shown('services_page.detail.show') ? page_url('services', $sv['id'])
                                : (shown('services_page.cards_show') ? page_url('services', 'card-' . $sv['id']) : page_url('services'))) ?>"><?= e($sv['name']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div>
        <h3><?= e(c('footer.col_contact', 'Get in touch')) ?></h3>
        <ul>
          <li><a href="tel:<?= e($phoneLink) ?>"><?= e($phoneShow) ?></a></li>
          <?php if (s('email')): ?><li><a href="mailto:<?= e(s('email')) ?>"><?= e(s('email')) ?></a></li><?php endif; ?>
          <?php if (s('areas')): ?><li><?= e(bww_list(s('areas', []))) ?>, Maryland</li><?php endif; ?>
          <?php foreach (s('hours', []) as $h): ?>
          <li><?= e($h['days']) ?>: <?= e($h['time']) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>

    </div>

    <div class="footer__bottom">
      <p>&copy; <?= date('Y') ?> <?= e(s('business')) ?>. <?= e(c('footer.copyright', 'All rights reserved.')) ?></p>
      <ul>
        <li><a href="/privacy-policy"><?= e(bww_legal()['privacy']['title'] ?? 'Privacy Policy') ?></a></li>
        <li><a href="/terms"><?= e(bww_legal()['terms']['title'] ?? 'Terms of Service') ?></a></li>
        <?php if (shown('footer.show_staff_login')): ?><li><a href="admin/">Staff Login</a></li><?php endif; ?>
      </ul>
    </div>
  </div>
</footer>

<?php if (shown('footer.callbar_show')): ?>
<div class="callbar">
  <a class="btn btn--call" href="tel:<?= e($phoneLink) ?>"><?= icon('phone') ?> Call Now</a>
  <a class="btn btn--ghost" href="/contact">Contact</a>
</div>
<?php endif; ?>

<script src="assets/js/main.js?v=<?= @filemtime(dirname(__DIR__) . '/assets/js/main.js') ?: 1 ?>" defer></script>
</body>
</html>
