<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/images.php';
require_once __DIR__ . '/includes/fields.php';
bww_require_login();

$GLOBALS['BWW_FIELD_ERRORS'] = [];
$services = bww_services();

define('BWW_SP_HERO', [
  ['key' => 'eyebrow', 'label' => 'Small line above the heading', 'max' => 60],
  ['key' => 'heading', 'label' => 'Main heading', 'max' => 140],
  ['key' => 'text',    'label' => 'Intro paragraph', 'type' => 'textarea', 'rows' => 3],
]);
define('BWW_SP_DETAIL', [
  ['key' => 'show',      'label' => 'Show the detailed list', 'type' => 'check'],
  ['key' => 'eyebrow',   'label' => 'Small line above the heading', 'max' => 60],
  ['key' => 'heading',   'label' => 'Heading', 'max' => 140],
  ['key' => 'text',      'label' => 'Intro paragraph', 'type' => 'textarea', 'rows' => 3],
  ['key' => 'cta_label', 'label' => 'Call button label on each service', 'max' => 40],
]);
define('BWW_SP_SYMP', [
  ['key' => 'show',    'label' => 'Show this section', 'type' => 'check'],
  ['key' => 'eyebrow', 'label' => 'Small line above the heading', 'max' => 60],
  ['key' => 'heading', 'label' => 'Heading', 'max' => 140],
  ['key' => 'text',    'label' => 'Intro paragraph', 'type' => 'textarea', 'rows' => 2],
  ['key' => 'items',   'label' => 'Symptoms', 'type' => 'repeat',
   'hint' => 'Each is a symptom a customer would recognise, and what it usually means.',
   'fields' => [
     ['key' => 'title', 'label' => 'Symptom', 'placeholder' => 'e.g. Rotten egg smell'],
     ['key' => 'text',  'label' => 'What it usually means', 'type' => 'textarea', 'rows' => 3],
   ]],
]);


/** Turn a name into a safe, unique id used in links and gallery categories. */
function bww_slug(string $name, array $taken): string {
    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
    if ($slug === '') $slug = 'service';
    $base = $slug; $n = 2;
    while (in_array($slug, $taken, true)) { $slug = $base . '-' . $n; $n++; }
    return $slug;
}

/** The Services page wording from the posted form, on top of what is saved. */
function bww_services_page_from_post(array $sp): array {
    foreach (['meta_title', 'meta_desc'] as $k) $sp[$k] = trim($_POST['meta'][$k] ?? '');
    $sp['hero']       = bww_collect(BWW_SP_HERO,    $_POST['hero']    ?? [], $sp['hero'] ?? [],    'hero');
    $sp['detail']     = bww_collect(BWW_SP_DETAIL,  $_POST['detail']  ?? [], $sp['detail'] ?? [],  'detail');
    $sp['symptoms']   = bww_collect(BWW_SP_SYMP,    $_POST['symptoms']?? [], $sp['symptoms'] ?? [],'symptoms');
    $sp['cards_show'] = !empty($_POST['cards_show']);
    return $sp;
}

/** One service's wording from the posted form (photos are handled separately). */
function bww_service_text_from_post(array $sv): array {
    $sv['name']    = trim($_POST['name'] ?? '') ?: ($sv['name'] ?? '');
    $sv['tag']     = trim($_POST['tag'] ?? '') ?: $sv['name'];
    $icon          = (string)($_POST['icon'] ?? '');
    $sv['icon']    = array_key_exists($icon, bww_icon_choices()) ? $icon : ($sv['icon'] ?? 'drop');
    $sv['short']   = trim($_POST['short'] ?? '');
    $sv['body']    = trim($_POST['body'] ?? '');
    $sv['bullets'] = array_slice(array_values(array_filter(array_map('trim', (array)($_POST['bullets'] ?? [])), fn($b) => $b !== '')), 0, 12);
    return $sv;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!bww_csrf_ok()) {
        bww_flash('Your session timed out. Please try that again.', 'err');
        header('Location: page-services.php'); exit;
    }
    $action = $_POST['action'] ?? '';

    /* ---------- live preview: show unsaved wording, save nothing ---------- */
    if (!empty($_POST['_preview'])) {
        $GLOBALS['BWW_PREVIEW'] = true;
        if ($action === 'update') {
            foreach ($services as &$sv) {
                if (($sv['id'] ?? '') !== ($_POST['id'] ?? '')) continue;
                $sv = bww_service_text_from_post($sv);
                if (!empty($_POST['clear_image'])) $sv['image'] = '';
            }
            unset($sv);
            bww_preview_store('services', $services);
        }
        if ($action === 'page') {
            bww_preview_store('content', bww_services_page_from_post(bww_read('content', [])['services_page'] ?? []), 'services_page');
        }
        header('Content-Type: application/json');
        echo '{"ok":true}';
        exit;
    }
    unset($_SESSION['bww_preview']);

    /* ---------- page wording ---------- */
    if ($action === 'page') {
        $all = bww_read('content', []);
        $all['services_page'] = bww_services_page_from_post($all['services_page'] ?? []);
        if (bww_save('content', $all)) bww_flash('Saved.', 'ok');
        header('Location: page-services.php'); exit;
    }

    /* ---------- add a service ---------- */
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            bww_flash('Please give the new service a name.', 'err');
        } else {
            $ids = array_column($services, 'id');
            $ids[] = 'general';   // reserved for the gallery's "General work"
            $icon = (string)($_POST['icon'] ?? '');
            $sv = [
                'id'      => bww_slug($name, $ids),
                'name'    => $name,
                'tag'     => trim($_POST['tag'] ?? '') ?: $name,
                'icon'    => array_key_exists($icon, bww_icon_choices()) ? $icon : 'drop',
                'short'   => trim($_POST['short'] ?? ''),
                'body'    => '',
                'bullets' => [],
                'image'   => '',
            ];
            $upErr = '';
            $up = $_FILES['image'] ?? null;
            if ($up && ($up['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $res = bww_handle_upload($up, 'photo');
                if ($res['ok']) { $sv['image'] = 'uploads/' . $res['file']; bww_new_upload($sv['image']); }
                else $upErr = $res['error'];
            }
            $services[] = $sv;
            if (bww_save('services', $services)) {
                $msg = 'Service added. It now appears on the home page, the services page, the footer, the gallery filters and the contact form.';
                bww_flash($upErr ? $msg . '<br>But the photo did not upload: ' . e($upErr) : $msg, $upErr ? 'warn' : 'ok');
            }
        }
        header('Location: page-services.php'); exit;
    }

    /* ---------- update one service ---------- */
    if ($action === 'update') {
        $id = $_POST['id'] ?? '';
        $upErr = '';
        foreach ($services as &$sv) {
            if (($sv['id'] ?? '') !== $id) continue;
            if (trim($_POST['name'] ?? '') === '') { bww_flash('A service needs a name.', 'err'); header('Location: page-services.php'); exit; }
            $sv = bww_service_text_from_post($sv);

            if (!empty($_POST['clear_image'])) {
                bww_delete_later($sv['image'] ?? '');   // removed only once the save succeeds
                $sv['image'] = '';
            }
            $up = $_FILES['image'] ?? null;
            if ($up && ($up['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $res = bww_handle_upload($up, 'photo');
                if ($res['ok']) { bww_delete_later($sv['image'] ?? ''); $sv['image'] = 'uploads/' . $res['file']; bww_new_upload($sv['image']); }
                else $upErr = $res['error'];
            }
            break;
        }
        unset($sv);
        if (bww_save('services', $services)) {
            bww_flash($upErr ? 'Saved, but the photo did not upload: ' . e($upErr) : 'Saved — the wording is live on your website now.', $upErr ? 'warn' : 'ok');
        }
        header('Location: page-services.php#' . urlencode($id)); exit;
    }

    /* ---------- delete a service ---------- */
    if ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        $name = bww_service_name($id);
        $kept = [];
        foreach ($services as $sv) {
            if (($sv['id'] ?? '') === $id) { bww_delete_later($sv['image'] ?? ''); continue; }
            $kept[] = $sv;
        }
        if (!bww_save('services', $kept)) { header('Location: page-services.php'); exit; }

        // Photos filed under this service are kept and moved to General work —
        // deleting somebody's job photos because they renamed a service is not
        // a surprise worth having.
        $gallery = bww_gallery(); $moved = 0;
        foreach ($gallery as &$p) {
            if (($p['category'] ?? '') === $id) { $p['category'] = 'general'; $moved++; }
        }
        unset($p);
        if ($moved && !bww_save('gallery', $gallery)) { header('Location: page-services.php'); exit; }

        bww_flash('“' . e($name) . '” removed from every page.' .
            ($moved ? ' ' . $moved . ' gallery ' . ($moved === 1 ? 'post was' : 'posts were') . ' moved to General work rather than deleted.' : ''), 'ok');
        header('Location: page-services.php'); exit;
    }

    /* ---------- reorder ---------- */
    if ($action === 'move') {
        $id  = $_POST['id'] ?? '';
        $dir = ($_POST['dir'] ?? '') === 'up' ? -1 : 1;
        foreach ($services as $i => $sv) {
            if (($sv['id'] ?? '') !== $id) continue;
            $j = $i + $dir;
            if ($j >= 0 && $j < count($services)) {
                [$services[$i], $services[$j]] = [$services[$j], $services[$i]];
                bww_save('services', $services);
            }
            break;
        }
        header('Location: page-services.php'); exit;
    }
}

$content = bww_read('content', []);
$sp = $content['services_page'] ?? [];

$ADMIN_PAGE  = 'page-services';
$ADMIN_TITLE = 'Services';
require __DIR__ . '/includes/layout.php';
?>

<div class="page-title">
  <h1>Services page</h1>
  <p>Add, remove and reorder your services, and edit everything on the page.</p>
</div>

<div class="editor-actions">
  <a class="btn btn--ghost btn--sm" href="../services.php" target="_blank" rel="noopener">See this page on the website &nearr;</a>
</div>

<div class="alert alert--info">
  <strong>Removing a service removes it everywhere.</strong> It disappears from the home page cards, this page, the footer menu, the gallery filters and the contact form dropdown. Any gallery posts filed under it are moved to General work, not deleted.
</div>

<div class="card">
  <h2>Your services (<?= count($services) ?>)</h2>
  <p class="card__hint">The order here is the order they appear everywhere on the site. Click a service to open it.</p>

  <?php if (!$services): ?>
    <div class="empty"><h3>No services yet</h3><p>Add your first one below.</p></div>
  <?php endif; ?>

  <?php foreach ($services as $i => $sv): ?>
  <div class="svc" id="<?= e($sv['id']) ?>">
    <div class="svc__head">
      <div class="svc__title">
        <span class="svc__icon"><?= icon($sv['icon'] ?? 'drop') ?></span>
        <h3><?= e($sv['name']) ?></h3>
      </div>
      <div class="svc__tools">
        <?php if ($i > 0): ?>
        <form method="post" class="inline"><?= bww_csrf_field() ?>
          <input type="hidden" name="action" value="move"><input type="hidden" name="id" value="<?= e($sv['id']) ?>">
          <input type="hidden" name="dir" value="up">
          <button class="btn btn--ghost btn--sm" type="submit" title="Move up">&uarr;</button>
        </form>
        <?php endif; ?>
        <?php if ($i < count($services) - 1): ?>
        <form method="post" class="inline"><?= bww_csrf_field() ?>
          <input type="hidden" name="action" value="move"><input type="hidden" name="id" value="<?= e($sv['id']) ?>">
          <input type="hidden" name="dir" value="down">
          <button class="btn btn--ghost btn--sm" type="submit" title="Move down">&darr;</button>
        </form>
        <?php endif; ?>
        <span class="chev">Edit</span>
      </div>
    </div>

    <div class="svc__body">
      <form method="post" enctype="multipart/form-data" data-pv-live>
        <?= bww_csrf_field() ?>
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?= e($sv['id']) ?>">

        <div class="row">
          <div class="field">
            <label>Service name</label>
            <input type="text" name="name" value="<?= e($sv['name']) ?>" maxlength="80" required>
          </div>
          <div class="field">
            <label>Short label</label>
            <input type="text" name="tag" value="<?= e($sv['tag'] ?? $sv['name']) ?>" maxlength="28">
            <p class="hint">Two or three words, used on gallery filter buttons and photo badges.</p>
          </div>
        </div>

        <div class="field">
          <label>Picture</label>
          <div class="icon-pick"><span class="icon-pick__preview"><?= icon($sv['icon'] ?? 'drop') ?></span>
            <select name="icon" class="icon-select">
              <?php foreach (bww_icon_choices() as $k => $lbl): ?>
                <option value="<?= e($k) ?>"<?= ($sv['icon'] ?? '') === $k ? ' selected' : '' ?>><?= e($lbl) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <p class="hint">Used when this service has no photo.</p>
        </div>

        <div class="field image-field">
          <label>Service photo (optional)</label>
          <div class="image-field__row">
            <?php if (!empty($sv['image'])): ?>
              <div class="image-field__preview"><img src="../<?= e(asset($sv['image'])) ?>" alt=""></div>
            <?php endif; ?>
            <div class="image-field__controls">
              <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
              <?php if (!empty($sv['image'])): ?>
                <label class="minor"><input type="checkbox" name="clear_image" value="1"> Remove this photo</label>
              <?php endif; ?>
              <p class="hint">Shown on the service card instead of the picture above.</p>
            </div>
          </div>
        </div>

        <div class="field">
          <label>Short description</label>
          <textarea name="short" rows="3" maxlength="240" required><?= e($sv['short'] ?? '') ?></textarea>
          <p class="hint">One or two sentences. This is what appears on the cards.</p>
        </div>

        <div class="field">
          <label>Full description</label>
          <textarea name="body" rows="7"><?= e($sv['body'] ?? '') ?></textarea>
          <p class="hint">Leave a blank line between paragraphs.</p>
        </div>

        <div class="field">
          <label>Bullet points</label>
          <div class="repeat" data-repeat>
            <?php foreach (array_merge($sv['bullets'] ?? [], ['']) as $b): ?>
              <div class="repeat-row"><input type="text" name="bullets[]" value="<?= e($b) ?>" maxlength="120">
              <button type="button" class="row-x" data-remove-row aria-label="Remove">&times;</button></div>
            <?php endforeach; ?>
          </div>
          <button type="button" class="btn btn--ghost btn--sm" data-add-row>+ Add a point</button>
        </div>

        <div class="btn-row">
          <button class="btn btn--go" type="submit">Save this service</button>
          <a class="btn btn--ghost" href="../services.php#<?= e($sv['id']) ?>" target="_blank" rel="noopener">See it on the website</a>
        </div>
      </form>

      <form method="post" class="danger-zone" data-confirm="Remove “<?= e($sv['name']) ?>” from the whole website? Gallery posts filed under it will move to General work.">
        <?= bww_csrf_field() ?>
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?= e($sv['id']) ?>">
        <button class="btn btn--danger btn--sm" type="submit">Remove this service from the website</button>
      </form>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="card">
  <h2>Add a service</h2>
  <p class="card__hint">It appears immediately on the home page, this page, the footer, the gallery filters and the contact form. You can fill in the longer wording afterwards.</p>
  <form method="post" enctype="multipart/form-data">
    <?= bww_csrf_field() ?>
    <input type="hidden" name="action" value="add">
    <div class="row">
      <div class="field">
        <label for="new-name">Service name</label>
        <input type="text" id="new-name" name="name" maxlength="80" required placeholder="e.g. Well Inspections">
      </div>
      <div class="field">
        <label for="new-tag">Short label</label>
        <input type="text" id="new-tag" name="tag" maxlength="28" placeholder="e.g. Inspections">
      </div>
    </div>
    <div class="field">
      <label for="new-icon">Picture</label>
      <div class="icon-pick"><span class="icon-pick__preview"><?= icon('drop') ?></span>
        <select id="new-icon" name="icon" class="icon-select">
          <?php foreach (bww_icon_choices() as $k => $lbl): ?><option value="<?= e($k) ?>"<?= $k === 'drop' ? ' selected' : '' ?>><?= e($lbl) ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="field">
      <label for="new-short">Short description</label>
      <textarea id="new-short" name="short" rows="3" maxlength="240" placeholder="One or two sentences for the card."></textarea>
    </div>
    <div class="field">
      <label for="new-image">Photo (optional)</label>
      <input type="file" id="new-image" name="image" accept="image/jpeg,image/png,image/webp">
    </div>
    <button class="btn btn--go" type="submit">Add service</button>
  </form>
</div>

<form method="post" enctype="multipart/form-data" data-pv-live>
  <?= bww_csrf_field() ?>
  <input type="hidden" name="action" value="page">

  <div class="card">
    <h2>Page title for Google</h2>
    <div class="field"><label>Page title</label><input type="text" name="meta[meta_title]" value="<?= e($sp['meta_title'] ?? '') ?>" maxlength="120"></div>
    <div class="field"><label>Description for search results</label><textarea name="meta[meta_desc]" rows="3"><?= e($sp['meta_desc'] ?? '') ?></textarea></div>
  </div>

  <?php bww_group('Top of the page', '', BWW_SP_HERO, $sp['hero'] ?? [], 'hero'); ?>

  <div class="card">
    <h2>Service cards</h2>
    <div class="field toggle-field">
      <input type="hidden" name="cards_show" value="0">
      <label class="toggle"><input type="checkbox" name="cards_show" value="1" <?= !isset($sp['cards_show']) || $sp['cards_show'] ? 'checked' : '' ?>><span>Show the row of cards at the top of the services page</span></label>
    </div>
  </div>

  <?php bww_group('The detailed list', 'The long write-up of each service further down the page.', BWW_SP_DETAIL, $sp['detail'] ?? [], 'detail'); ?>
  <?php bww_group('"Describe the symptom" section', '', BWW_SP_SYMP, $sp['symptoms'] ?? [], 'symptoms'); ?>

  <div class="save-bar">
    <button class="btn btn--go" type="submit">Save page wording</button>
    <span>The services themselves save separately, above.</span>
  </div>
</form>

<?php require __DIR__ . '/includes/layout-end.php'; ?>
