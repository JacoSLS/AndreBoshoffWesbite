<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/fields.php';
bww_require_login();

$DOCS = ['privacy' => 'Privacy Policy', 'terms' => 'Terms and Conditions'];

$SPEC = [
  ['key' => 'title',   'label' => 'Page title', 'max' => 80],
  ['key' => 'updated', 'label' => '"Last updated" date', 'max' => 40, 'hint' => 'Written however you like, e.g. 21 August 2026.'],
  ['key' => 'show_notice', 'label' => 'Show the yellow note to yourself at the top', 'type' => 'check',
   'hint' => 'This note is visible to the public too. Switch it off before the site goes live if you do not want visitors reading it.'],
  ['key' => 'notice',  'label' => 'That note', 'type' => 'textarea', 'rows' => 3],
  ['key' => 'intro',   'label' => 'Opening paragraphs', 'type' => 'textarea', 'rows' => 4],
  ['key' => 'sections','label' => 'Sections', 'type' => 'repeat',
   'hint' => 'Each section is a heading and its text. Clear the heading to delete a section. Start a line with "- " to make a bullet point.',
   'fields' => [
     ['key' => 'heading', 'label' => 'Section heading'],
     ['key' => 'body',    'label' => 'Section text', 'type' => 'textarea', 'rows' => 6],
   ]],
  ['key' => 'show_contact_block', 'label' => 'Add your contact details at the bottom', 'type' => 'check',
   'hint' => 'Pulled from Contact & Settings, so it stays correct automatically.'],
];

$which = isset($_GET['doc']) && isset($DOCS[$_GET['doc']]) ? $_GET['doc'] : 'privacy';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $which = isset($DOCS[$_POST['doc'] ?? '']) ? $_POST['doc'] : 'privacy';

    if (!bww_csrf_ok()) {
        bww_flash('Your session timed out. Please try that again.', 'err');
        header('Location: legal.php?doc=' . $which); exit;
    }
    $legal = bww_read('legal', []);
    $legal[$which] = bww_collect($SPEC, $_POST['d'] ?? [], $legal[$which] ?? [], 'd');
    bww_write('legal', $legal);
    bww_flash('Saved — the page is updated on your website.', 'ok');
    header('Location: legal.php?doc=' . $which); exit;
}

$legal = bww_read('legal', []);
$doc   = $legal[$which] ?? [];
$view  = $which === 'privacy' ? '../privacy-policy.php' : '../terms.php';

$ADMIN_PAGE  = 'legal';
$ADMIN_TITLE = 'Legal pages';
require __DIR__ . '/includes/layout.php';
?>

<div class="page-title">
  <h1>Privacy policy and terms</h1>
  <p>Both legal pages, fully editable.</p>
</div>

<div class="doc-switch">
  <?php foreach ($DOCS as $k => $lbl): ?>
    <a class="<?= $which === $k ? 'on' : '' ?>" href="legal.php?doc=<?= e($k) ?>"><?= e($lbl) ?></a>
  <?php endforeach; ?>
  <a class="ghost" href="<?= e($view) ?>" target="_blank" rel="noopener">See this page on the website &nearr;</a>
</div>

<div class="alert alert--warn">
  <strong>These are drafts, not legal advice.</strong> They were written to match how this website actually behaves, which is a sound starting point — but have an attorney read them before you rely on them, especially if you start taking payments online.
</div>

<form method="post">
  <?= bww_csrf_field() ?>
  <input type="hidden" name="doc" value="<?= e($which) ?>">
  <?php bww_group($DOCS[$which], 'Everything on this page.', $SPEC, $doc, 'd'); ?>
  <div class="save-bar">
    <button class="btn btn--go" type="submit">Save changes</button>
    <span>Saves the <?= e($DOCS[$which]) ?> page only.</span>
  </div>
</form>

<?php require __DIR__ . '/includes/layout-end.php'; ?>
