<?php
require_once __DIR__ . '/includes/auth.php';
bww_require_login();

$posts    = bww_gallery();
$services = bww_services();
$photos   = array_sum(array_map(fn($p) => count($p['photos'] ?? []), $posts));

$editors = [
  ['page-home.php',     'home',   'Home page',        'Headings, photos, the four points, your services section, the "why call us back" block and the numbers.'],
  ['page-about.php',    'users',  'About Us page',    'Your story, how you work and who you help — with both photos.'],
  ['page-services.php', 'pump',   'Services page',    'Add, remove and reorder services. Everything you change here follows through the whole site.'],
  ['gallery.php',       'image',  'Gallery',          'Add jobs with several photos each, and write up what you did.'],
  ['page-contact.php',  'mail',   'Contact page',     'The wording and the enquiry form.'],
  ['branding.php',      'layout', 'Header &amp; Footer',  'Logos, the menu, the green call band and the footer.'],
  ['legal.php',         'file',   'Legal pages',      'Privacy policy and terms and conditions.'],
  ['settings.php',      'phone',  'Contact &amp; Settings','Phone, email, hours, service areas and tracking codes.'],
];

$ADMIN_PAGE  = 'dashboard';
$ADMIN_TITLE = 'Overview';
require __DIR__ . '/includes/layout.php';
?>

<div class="page-title">
  <h1>Hello<?= !empty($_SESSION['bww_user']) ? ', ' . e(ucfirst($_SESSION['bww_user'])) : '' ?></h1>
  <p>Every part of your website can be changed from here. Nothing needs a web person.</p>
</div>

<div class="tiles">
  <div class="tile"><strong><?= count($posts) ?></strong><span>Jobs in your gallery</span></div>
  <div class="tile"><strong><?= $photos ?></strong><span>Photos across those jobs</span></div>
  <div class="tile"><strong><?= count($services) ?></strong><span>Services on the site</span></div>
</div>

<div class="card">
  <h2>What would you like to change?</h2>
  <p class="card__hint">Each page below edits one part of your website. Changes go live the moment you save.</p>
  <div class="editor-grid">
    <?php foreach ($editors as [$href, $ic, $title, $desc]): ?>
    <a class="editor-tile" href="<?= e($href) ?>">
      <span class="editor-tile__icon"><?= icon($ic) ?></span>
      <span class="editor-tile__text">
        <strong><?= $title ?></strong>
        <span><?= $desc ?></span>
      </span>
    </a>
    <?php endforeach; ?>
  </div>
  <div class="btn-row"><a class="btn btn--ghost" href="../index.php" target="_blank" rel="noopener">See the website &nearr;</a></div>
</div>

<?php if (!$posts): ?>
<div class="card">
  <h2>Your gallery is empty</h2>
  <p class="card__hint">Until you add jobs, the website shows placeholder pictures with a note explaining they are stand-ins.</p>
  <p>Photos of your own work are the single most persuasive thing on the site. Take them in daylight, get the whole job in frame, and add several photos to one job so visitors can see it properly.</p>
  <div class="btn-row"><a class="btn btn--go" href="gallery.php">Add your first job</a></div>
</div>
<?php endif; ?>

<div class="card">
  <h2>A quick note on how this works</h2>
  <p class="card__hint">Worth reading once.</p>
  <p>Everything you change here is saved on your own hosting, not on somebody else's platform. Your photos live in a folder on your site and your wording lives in small files next to them, which means the whole website can be picked up and moved elsewhere without losing anything.</p>
  <p>Most sections have a switch to hide them. Hiding is safer than deleting — the wording stays put, so you can bring it back at any time. Deleting a service or a job is permanent, so it always asks first.</p>
</div>

<?php require __DIR__ . '/includes/layout-end.php'; ?>
