<?php
require_once __DIR__ . '/includes/auth.php';
bww_require_login();

$services = bww_services();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!bww_csrf_ok()) {
        bww_flash('Your session timed out. Please try that again.', 'err');
        header('Location: services.php'); exit;
    }

    $id = $_POST['id'] ?? '';
    $found = false;
    foreach ($services as &$sv) {
        if (($sv['id'] ?? '') === $id) {
            $found = true;
            $name  = trim($_POST['name'] ?? '');
            $short = trim($_POST['short'] ?? '');
            $body  = trim($_POST['body'] ?? '');
            if ($name === '' || $short === '') {
                bww_flash('The service name and the short description both need to be filled in.', 'err');
                header('Location: services.php'); exit;
            }
            $sv['name']  = $name;
            $sv['short'] = $short;
            $sv['body']  = $body;
            $tag = trim($_POST['tag'] ?? '');
            $sv['tag']   = $tag !== '' ? $tag : $name;
            $bullets = array_values(array_filter(array_map('trim', (array)($_POST['bullets'] ?? []))));
            $sv['bullets'] = array_slice($bullets, 0, 10);
            break;
        }
    }
    unset($sv);

    if ($found) {
        bww_write('services', $services);
        bww_flash('Saved — the wording is live on your website now.', 'ok');
    } else {
        bww_flash('That service could not be found.', 'err');
    }
    header('Location: services.php'); exit;
}

$ADMIN_PAGE  = 'services';
$ADMIN_TITLE = 'Services';
require __DIR__ . '/includes/layout.php';
?>

<div class="page-title">
  <h1>Your services</h1>
  <p>Change the wording customers read. Click a service to open it.</p>
</div>

<div class="alert alert--info">
  <strong>Write like you would explain it to a customer standing in their kitchen.</strong>
  Short description is the one-liner on the home page. The longer description is what people read on the Services page.
</div>

<?php foreach ($services as $sv): ?>
<div class="svc">
  <div class="svc__head">
    <h3><?= e($sv['name']) ?></h3>
    <span class="chev">Edit</span>
  </div>
  <div class="svc__body">
    <form method="post">
      <?= bww_csrf_field() ?>
      <input type="hidden" name="id" value="<?= e($sv['id']) ?>">

      <div class="field">
        <label>Service name</label>
        <input type="text" name="name" value="<?= e($sv['name']) ?>" maxlength="80" required>
      </div>

      <div class="field">
        <label>Short label</label>
        <input type="text" name="tag" value="<?= e($sv['tag'] ?? $sv['name']) ?>" maxlength="28">
        <p class="hint">Two or three words. Used on the gallery filter buttons and the little badge on each photo, where the full name is too long to fit.</p>
      </div>

      <div class="field">
        <label>Short description</label>
        <textarea name="short" maxlength="220" required style="min-height:70px"><?= e($sv['short']) ?></textarea>
        <p class="hint">One or two sentences. This is what appears on the cards on your home page.</p>
      </div>

      <div class="field">
        <label>Full description</label>
        <textarea name="body" maxlength="1200" style="min-height:150px"><?= e($sv['body'] ?? '') ?></textarea>
        <p class="hint">A paragraph or two on the Services page. Explain what the job involves and who it is for.</p>
      </div>

      <div class="field">
        <label>Bullet points</label>
        <div id="b-<?= e($sv['id']) ?>">
          <?php foreach (($sv['bullets'] ?? []) as $b): ?>
            <div class="repeat-row"><input type="text" name="bullets[]" value="<?= e($b) ?>" maxlength="120"></div>
          <?php endforeach; ?>
          <div class="repeat-row"><input type="text" name="bullets[]" value="" maxlength="120" placeholder="Add another point"></div>
        </div>
        <button class="btn btn--ghost btn--sm" type="button" data-add-bullet="b-<?= e($sv['id']) ?>" data-name="bullets">+ Add a point</button>
        <p class="hint">Leave a box empty to remove that point.</p>
      </div>

      <div class="btn-row">
        <button class="btn btn--go" type="submit">Save changes</button>
        <a class="btn btn--ghost" href="../services.php#<?= e($sv['id']) ?>" target="_blank" rel="noopener">See it on the website</a>
      </div>
    </form>
  </div>
</div>
<?php endforeach; ?>

<div class="card">
  <h2>Need a service added or removed?</h2>
  <p>Adding or removing a whole service changes the menus, the home page and the gallery categories, so it is a small job rather than a text edit. Ask your web person and it will take them a few minutes.</p>
</div>

<?php require __DIR__ . '/includes/layout-end.php'; ?>
