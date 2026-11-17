<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/images.php';
require_once __DIR__ . '/includes/fields.php';
bww_require_login();

$GLOBALS['BWW_FIELD_ERRORS'] = [];

$LOGOS = [
  ['key' => 'logo',       'label' => 'Main logo (header)',  'type' => 'image', 'mode' => 'logo',
   'hint' => 'PNG with a transparent background works best. Shown top-left on every page.'],
  ['key' => 'logo_white', 'label' => 'White logo (footer)', 'type' => 'image', 'mode' => 'logo',
   'hint' => 'A white version of the same logo, for the dark footer.'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!bww_csrf_ok()) {
        bww_flash('Your session timed out. Please try that again.', 'err');
        header('Location: branding.php'); exit;
    }

    $all    = bww_read('content', []);
    $header = $all['header'] ?? [];
    $footer = $all['footer'] ?? [];

    // logos (handles upload, keep and remove)
    $header = bww_collect($LOGOS, $_POST['logos'] ?? [], $header, 'logos');

    // top bar / header
    $header['show_topbar']     = !empty($_POST['show_topbar']);
    $header['show_header_cta'] = !empty($_POST['show_header_cta']);
    $header['topbar_note']     = trim($_POST['topbar_note'] ?? '');

    // menu labels and visibility — slugs are the page addresses and stay fixed
    $nav = [];
    foreach (bww_nav(false) as $i => $item) {
        $slug  = $item['slug'] ?? '';
        $label = trim($_POST['nav_label'][$slug] ?? ($item['label'] ?? $slug));
        $nav[] = [
            'slug'  => $slug,
            'label' => $label !== '' ? $label : ($item['label'] ?? $slug),
            'show'  => !empty($_POST['nav_show'][$slug]),
        ];
    }
    $header['nav'] = $nav;

    // footer
    foreach (['tagline', 'about_text', 'col_pages', 'col_services', 'col_contact', 'copyright', 'band_heading'] as $k) {
        $footer[$k] = trim($_POST['footer'][$k] ?? '');
    }
    $footer['show_staff_login'] = !empty($_POST['footer']['show_staff_login']);
    $footer['band_show']        = !empty($_POST['footer']['band_show']);
    $footer['callbar_show']     = !empty($_POST['footer']['callbar_show']);

    $all['header'] = $header;
    $all['footer'] = $footer;
    if (!bww_save('content', $all)) { header('Location: branding.php'); exit; }

    $errs = $GLOBALS['BWW_FIELD_ERRORS'];
    bww_flash($errs ? 'Saved, but a logo did not upload:<br>' . implode('<br>', array_map('e', $errs))
                    : 'Saved — the header and footer are updated on every page.', $errs ? 'warn' : 'ok');
    header('Location: branding.php'); exit;
}

$content = bww_read('content', []);
$header  = $content['header'] ?? [];
$footer  = $content['footer'] ?? [];

$ADMIN_PAGE  = 'branding';
$ADMIN_TITLE = 'Header & Footer';
require __DIR__ . '/includes/layout.php';
?>

<div class="page-title">
  <h1>Header, footer and logos</h1>
  <p>The parts that appear on every single page of your website.</p>
</div>

<div class="editor-actions">
  <a class="btn btn--ghost btn--sm" href="../index.php" target="_blank" rel="noopener">See the website &nearr;</a>
</div>

<form method="post" enctype="multipart/form-data">
  <?= bww_csrf_field() ?>

  <?php bww_group('Logos', 'Upload a new file to replace one. Leave both empty to keep what you have.', $LOGOS, $header, 'logos'); ?>

  <div class="card">
    <h2>Top bar and header</h2>
    <p class="card__hint">Your phone number and email in the top bar come from Contact &amp; Settings, so they only ever need changing in one place.</p>

    <div class="field toggle-field">
      <input type="hidden" name="show_topbar" value="0">
      <label class="toggle"><input type="checkbox" name="show_topbar" value="1" <?= !isset($header['show_topbar']) || $header['show_topbar'] ? 'checked' : '' ?>>
      <span>Show the dark bar above the menu</span></label>
    </div>

    <div class="field">
      <label for="tn">Note on the right of that bar</label>
      <input type="text" id="tn" name="topbar_note" value="<?= e($header['topbar_note'] ?? '') ?>" maxlength="90">
      <p class="hint">e.g. Mon–Fri 6am–6pm · 24/7 emergencies. Leave empty to hide it.</p>
    </div>

    <div class="field toggle-field">
      <input type="hidden" name="show_header_cta" value="0">
      <label class="toggle"><input type="checkbox" name="show_header_cta" value="1" <?= !isset($header['show_header_cta']) || $header['show_header_cta'] ? 'checked' : '' ?>>
      <span>Show the green call button in the header</span></label>
    </div>
  </div>

  <div class="card">
    <h2>Menu</h2>
    <p class="card__hint">Rename any menu item, or untick one to take that page out of the menu and the footer. The page itself still works if somebody has the link.</p>
    <table class="nav-table">
      <thead><tr><th>Page</th><th>Menu label</th><th>Show</th></tr></thead>
      <tbody>
        <?php foreach (bww_nav(false) as $item): $slug = $item['slug']; ?>
        <tr>
          <td><code><?= e($slug === 'index' ? 'home page' : $slug) ?></code></td>
          <td><input type="text" name="nav_label[<?= e($slug) ?>]" value="<?= e($item['label'] ?? $slug) ?>" maxlength="40"></td>
          <td class="mid">
            <input type="checkbox" name="nav_show[<?= e($slug) ?>]" value="1" <?= !isset($item['show']) || $item['show'] ? 'checked' : '' ?>>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="card">
    <h2>The green call band</h2>
    <p class="card__hint">The wide green strip just above the footer on every page.</p>
    <div class="field toggle-field">
      <input type="hidden" name="footer[band_show]" value="0">
      <label class="toggle"><input type="checkbox" name="footer[band_show]" value="1" <?= !isset($footer['band_show']) || $footer['band_show'] ? 'checked' : '' ?>>
      <span>Show the green band</span></label>
    </div>
    <div class="field">
      <label for="bh">Heading on the band</label>
      <input type="text" id="bh" name="footer[band_heading]" value="<?= e($footer['band_heading'] ?? '') ?>" maxlength="140">
      <p class="hint">The line under it is your emergency note, edited under Contact &amp; Settings.</p>
    </div>
  </div>

  <div class="card">
    <h2>Footer</h2>

    <div class="field">
      <label for="ft">Tagline under the logo</label>
      <input type="text" id="ft" name="footer[tagline]" value="<?= e($footer['tagline'] ?? '') ?>" maxlength="80">
    </div>

    <div class="field">
      <label for="fa">Paragraph about the business</label>
      <textarea id="fa" name="footer[about_text]" rows="4"><?= e($footer['about_text'] ?? '') ?></textarea>
    </div>

    <div class="row">
      <div class="field">
        <label for="c1">Heading above the page links</label>
        <input type="text" id="c1" name="footer[col_pages]" value="<?= e($footer['col_pages'] ?? '') ?>" maxlength="40">
      </div>
      <div class="field">
        <label for="c2">Heading above the services list</label>
        <input type="text" id="c2" name="footer[col_services]" value="<?= e($footer['col_services'] ?? '') ?>" maxlength="40">
      </div>
    </div>
    <div class="row">
      <div class="field">
        <label for="c3">Heading above your contact details</label>
        <input type="text" id="c3" name="footer[col_contact]" value="<?= e($footer['col_contact'] ?? '') ?>" maxlength="40">
      </div>
      <div class="field">
        <label for="c4">Line after the copyright year</label>
        <input type="text" id="c4" name="footer[copyright]" value="<?= e($footer['copyright'] ?? '') ?>" maxlength="80">
      </div>
    </div>

    <div class="field toggle-field">
      <input type="hidden" name="footer[show_staff_login]" value="0">
      <label class="toggle"><input type="checkbox" name="footer[show_staff_login]" value="1" <?= !isset($footer['show_staff_login']) || $footer['show_staff_login'] ? 'checked' : '' ?>>
      <span>Show the "Staff Login" link in the footer</span></label>
      <p class="hint">Switch this off and you reach this admin by typing /admin/ on the end of your web address.</p>
    </div>

    <div class="field toggle-field">
      <input type="hidden" name="footer[callbar_show]" value="0">
      <label class="toggle"><input type="checkbox" name="footer[callbar_show]" value="1" <?= !isset($footer['callbar_show']) || $footer['callbar_show'] ? 'checked' : '' ?>>
      <span>Show the fixed Call Now bar at the bottom on phones</span></label>
    </div>
  </div>

  <div class="save-bar">
    <button class="btn btn--go" type="submit">Save changes</button>
    <span>These changes appear on every page at once.</span>
  </div>
</form>

<?php require __DIR__ . '/includes/layout-end.php'; ?>
