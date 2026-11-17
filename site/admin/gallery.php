<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/images.php';
require_once __DIR__ . '/includes/fields.php';
bww_require_login();

$services = bww_services();
$posts    = bww_gallery();

/** Take every file from one multi-file upload field. */
function bww_take_photos(array $field): array {
    $out = ['photos' => [], 'errors' => []];
    if (empty($field['name'])) return $out;
    $names = (array)$field['name'];
    foreach (array_keys($names) as $i) {
        $one = [
            'name'     => $field['name'][$i],
            'type'     => $field['type'][$i]     ?? '',
            'tmp_name' => $field['tmp_name'][$i] ?? '',
            'error'    => $field['error'][$i]    ?? UPLOAD_ERR_NO_FILE,
            'size'     => $field['size'][$i]     ?? 0,
        ];
        if ($one['error'] === UPLOAD_ERR_NO_FILE) continue;
        $res = bww_handle_upload($one, 'photo');
        if ($res['ok']) $out['photos'][] = ['file' => $res['file'], 'thumb' => $res['thumb']];
        else $out['errors'][] = $one['name'] . ': ' . $res['error'];
    }
    return $out;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!bww_csrf_ok()) {
        bww_flash('Your session timed out. Please try that again.', 'err');
        header('Location: gallery.php'); exit;
    }
    $action = $_POST['action'] ?? '';

    /* ---------- create a post ---------- */
    if ($action === 'add') {
        $title = trim($_POST['title'] ?? '');
        if ($title === '') {
            bww_flash('Please give the job a heading so visitors know what they are looking at.', 'err');
            header('Location: gallery.php'); exit;
        }
        $taken = bww_take_photos($_FILES['photos'] ?? []);
        if (!$taken['photos']) {
            bww_flash($taken['errors'] ? implode('<br>', array_map('e', $taken['errors']))
                                       : 'Please choose at least one photo.', 'err');
            header('Location: gallery.php'); exit;
        }
        array_unshift($posts, [
            'id'       => bin2hex(random_bytes(6)),
            'title'    => $title,
            'caption'  => trim($_POST['caption'] ?? ''),
            'body'     => trim($_POST['body'] ?? ''),
            'category' => trim($_POST['category'] ?? 'general'),
            'photos'   => $taken['photos'],
            'added'    => date('c'),
        ]);
        bww_write('gallery', $posts);
        $n = count($taken['photos']);
        bww_flash('Job added with ' . $n . ' photo' . ($n === 1 ? '' : 's') . '. <a href="../gallery.php" target="_blank" rel="noopener">See it on your website</a>.'
            . ($taken['errors'] ? '<br>Some files were skipped: ' . implode('; ', array_map('e', $taken['errors'])) : ''), 'ok');
        header('Location: gallery.php'); exit;
    }

    /* ---------- update a post, optionally adding more photos ---------- */
    if ($action === 'update') {
        $id = $_POST['id'] ?? '';
        $msg = 'Saved.';
        foreach ($posts as &$p) {
            if (($p['id'] ?? '') !== $id) continue;
            $p['title']    = trim($_POST['title'] ?? $p['title']);
            $p['caption']  = trim($_POST['caption'] ?? '');
            $p['body']     = trim($_POST['body'] ?? '');
            $p['category'] = trim($_POST['category'] ?? 'general');

            // remove any photos that were ticked
            $drop = (array)($_POST['drop'] ?? []);
            if ($drop) {
                $keep = [];
                foreach ($p['photos'] as $k => $ph) {
                    if (in_array((string)$k, array_map('strval', $drop), true)) { bww_delete_photos([$ph]); continue; }
                    $keep[] = $ph;
                }
                $p['photos'] = $keep;
            }
            // make one of them the cover
            $cover = $_POST['cover'] ?? '';
            if ($cover !== '' && isset($p['photos'][(int)$cover]) && (int)$cover > 0) {
                $ph = $p['photos'][(int)$cover];
                unset($p['photos'][(int)$cover]);
                array_unshift($p['photos'], $ph);
                $p['photos'] = array_values($p['photos']);
            }
            // add new ones
            $taken = bww_take_photos($_FILES['photos'] ?? []);
            if ($taken['photos']) {
                $p['photos'] = array_merge($p['photos'], $taken['photos']);
                $msg = 'Saved, and ' . count($taken['photos']) . ' more photo' . (count($taken['photos']) === 1 ? '' : 's') . ' added.';
            }
            if ($taken['errors']) $msg .= '<br>Some files were skipped: ' . implode('; ', array_map('e', $taken['errors']));
            if (!$p['photos']) $msg .= '<br><strong>This job has no photos left, so it no longer shows on the website.</strong>';
            break;
        }
        unset($p);
        bww_write('gallery', $posts);
        bww_flash($msg, 'ok');
        header('Location: gallery.php?edit=' . urlencode($id)); exit;
    }

    /* ---------- delete a whole post ---------- */
    if ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        $kept = [];
        foreach ($posts as $p) {
            if (($p['id'] ?? '') === $id) { bww_delete_photos($p['photos'] ?? []); continue; }
            $kept[] = $p;
        }
        bww_write('gallery', $kept);
        bww_flash('Job deleted, along with its photos.', 'ok');
        header('Location: gallery.php'); exit;
    }

    /* ---------- reorder ---------- */
    if ($action === 'move') {
        $id  = $_POST['id'] ?? '';
        $dir = ($_POST['dir'] ?? '') === 'up' ? -1 : 1;
        foreach ($posts as $i => $p) {
            if (($p['id'] ?? '') !== $id) continue;
            $j = $i + $dir;
            if ($j >= 0 && $j < count($posts)) {
                [$posts[$i], $posts[$j]] = [$posts[$j], $posts[$i]];
                bww_write('gallery', $posts);
            }
            break;
        }
        header('Location: gallery.php'); exit;
    }

    /* ---------- page wording ---------- */
    if ($action === 'page') {
        $all = bww_read('content', []);
        $gp  = $all['gallery_page'] ?? [];
        foreach (['meta_title', 'meta_desc', 'all_label', 'empty_heading', 'empty_text'] as $k) {
            $gp[$k] = trim($_POST['g'][$k] ?? '');
        }
        foreach (['eyebrow', 'heading', 'text'] as $k) $gp['hero'][$k] = trim($_POST['hero'][$k] ?? '');
        $gp['show_filters'] = !empty($_POST['g']['show_filters']);
        $all['gallery_page'] = $gp;
        bww_write('content', $all);
        bww_flash('Saved.', 'ok');
        header('Location: gallery.php'); exit;
    }
}

$editId  = $_GET['edit'] ?? '';
$content = bww_read('content', []);
$gp      = $content['gallery_page'] ?? [];
$photoCount = array_sum(array_map(fn($p) => count($p['photos'] ?? []), $posts));

$ADMIN_PAGE  = 'gallery';
$ADMIN_TITLE = 'Gallery';
require __DIR__ . '/includes/layout.php';
?>

<div class="page-title">
  <h1>Gallery</h1>
  <p>Each job is one post that can hold several photos. On the website the photos take turns showing on the card, and clicking it opens the full job.</p>
</div>

<div class="editor-actions">
  <a class="btn btn--ghost btn--sm" href="../gallery.php" target="_blank" rel="noopener">See this page on the website &nearr;</a>
</div>

<?php if (!bww_gd_available()): ?>
  <div class="alert alert--err"><strong>This server cannot process images.</strong> The GD extension is switched off, so uploads will fail. Ask your web person to enable GD in the hosting control panel.</div>
<?php elseif (!is_writable(BWW_UPLOADS)): ?>
  <div class="alert alert--err"><strong>The photo folder is not writable.</strong> Set <code>/assets/uploads</code> to permission 755 in your hosting file manager.</div>
<?php endif; ?>

<div class="card">
  <h2>Add a job</h2>
  <p class="card__hint">Pick as many photos of the same job as you like — hold Ctrl (or Cmd on a Mac) to select several at once.</p>

  <form method="post" enctype="multipart/form-data">
    <?= bww_csrf_field() ?>
    <input type="hidden" name="action" value="add">

    <div class="field">
      <label for="photo-input">Photos of this job</label>
      <input type="file" id="photo-input" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required>
      <p class="hint">JPG or PNG, up to <?= BWW_MAX_UPLOAD_MB ?> MB each. The first one becomes the cover — you can change that later. iPhone users: if a photo will not upload, set Settings &rarr; Camera &rarr; Formats to "Most Compatible".</p>
      <div id="photo-preview" class="thumb-strip"></div>
    </div>

    <div class="row">
      <div class="field">
        <label for="title">Heading</label>
        <input type="text" id="title" name="title" required maxlength="90" placeholder="e.g. Well pump replaced, Freeland">
      </div>
      <div class="field">
        <label for="category">Which service was this?</label>
        <select id="category" name="category">
          <option value="general">General work</option>
          <?php foreach ($services as $sv): ?>
            <option value="<?= e($sv['id']) ?>"><?= e($sv['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="field">
      <label for="caption">One-line summary</label>
      <input type="text" id="caption" name="caption" maxlength="180" placeholder="e.g. Replaced a failed 40-year-old pump for a family who had been three days without water.">
      <p class="hint">This shows on the card in the gallery.</p>
    </div>

    <div class="field">
      <label for="body">The full story (optional)</label>
      <textarea id="body" name="body" rows="5" placeholder="What the problem was, what you found, and what you did about it."></textarea>
      <p class="hint">Shown when somebody clicks the job open. Leave a blank line between paragraphs.</p>
    </div>

    <button class="btn btn--go" type="submit">Add this job</button>
  </form>
</div>

<div class="card">
  <h2>Your jobs<?= $posts ? ' (' . count($posts) . ' jobs, ' . $photoCount . ' photos)' : '' ?></h2>
  <p class="card__hint">The order here is the order they appear on your website.</p>

  <?php if (!$posts): ?>
    <div class="empty">
      <h3>No jobs yet</h3>
      <p>Your website is showing placeholder pictures until you add some. Add your first job above.</p>
    </div>
  <?php else: ?>
    <?php foreach ($posts as $i => $p):
      $photos = $p['photos'] ?? [];
      $cover  = $photos[0]['thumb'] ?? ($photos[0]['file'] ?? '');
      $open   = $editId === ($p['id'] ?? '');
    ?>
    <div class="job<?= $open ? ' open' : '' ?>" id="<?= e($p['id']) ?>">
      <div class="job__head">
        <div class="job__ident">
          <?php if ($cover): ?><img class="job__cover" src="../assets/uploads/<?= e(rawurlencode($cover)) ?>" alt=""><?php endif; ?>
          <div>
            <h3><?= e($p['title'] ?? 'Untitled') ?></h3>
            <span class="job__meta">
              <?= count($photos) ?> photo<?= count($photos) === 1 ? '' : 's' ?>
              &middot; <?= e(($p['category'] ?? 'general') === 'general' ? 'General work' : bww_service_tag($p['category'])) ?>
            </span>
          </div>
        </div>
        <div class="job__tools">
          <?php if ($i > 0): ?>
          <form method="post" class="inline"><?= bww_csrf_field() ?>
            <input type="hidden" name="action" value="move"><input type="hidden" name="id" value="<?= e($p['id']) ?>">
            <input type="hidden" name="dir" value="up"><button class="btn btn--ghost btn--sm" type="submit" title="Move up">&uarr;</button>
          </form>
          <?php endif; ?>
          <?php if ($i < count($posts) - 1): ?>
          <form method="post" class="inline"><?= bww_csrf_field() ?>
            <input type="hidden" name="action" value="move"><input type="hidden" name="id" value="<?= e($p['id']) ?>">
            <input type="hidden" name="dir" value="down"><button class="btn btn--ghost btn--sm" type="submit" title="Move down">&darr;</button>
          </form>
          <?php endif; ?>
          <span class="chev"><?= $open ? 'Close' : 'Edit' ?></span>
        </div>
      </div>

      <div class="job__body">
        <form method="post" enctype="multipart/form-data">
          <?= bww_csrf_field() ?>
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="id" value="<?= e($p['id']) ?>">

          <div class="row">
            <div class="field">
              <label>Heading</label>
              <input type="text" name="title" value="<?= e($p['title'] ?? '') ?>" maxlength="90" required>
            </div>
            <div class="field">
              <label>Which service was this?</label>
              <select name="category">
                <option value="general"<?= ($p['category'] ?? '') === 'general' ? ' selected' : '' ?>>General work</option>
                <?php foreach ($services as $sv): ?>
                  <option value="<?= e($sv['id']) ?>"<?= ($p['category'] ?? '') === $sv['id'] ? ' selected' : '' ?>><?= e($sv['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="field">
            <label>One-line summary</label>
            <input type="text" name="caption" value="<?= e($p['caption'] ?? '') ?>" maxlength="180">
          </div>

          <div class="field">
            <label>The full story</label>
            <textarea name="body" rows="5"><?= e($p['body'] ?? '') ?></textarea>
          </div>

          <div class="field">
            <label>Photos in this job</label>
            <div class="photo-manage">
              <?php foreach ($photos as $k => $ph): ?>
              <div class="photo-manage__item">
                <img src="../assets/uploads/<?= e(rawurlencode($ph['thumb'] ?? $ph['file'])) ?>" alt="">
                <?php if ($k === 0): ?>
                  <span class="badge-cover">Cover</span>
                <?php else: ?>
                  <label class="minor"><input type="radio" name="cover" value="<?= $k ?>"> Make cover</label>
                <?php endif; ?>
                <label class="minor danger"><input type="checkbox" name="drop[]" value="<?= $k ?>"> Delete</label>
              </div>
              <?php endforeach; ?>
              <?php if (!$photos): ?><p class="hint">This job has no photos, so it is hidden from the website. Add one below.</p><?php endif; ?>
            </div>
          </div>

          <div class="field">
            <label>Add more photos to this job</label>
            <input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple>
          </div>

          <div class="btn-row">
            <button class="btn btn--go" type="submit">Save this job</button>
            <a class="btn btn--ghost" href="gallery.php">Close</a>
          </div>
        </form>

        <form method="post" class="danger-zone" data-confirm="Delete this job and all of its photos? This cannot be undone.">
          <?= bww_csrf_field() ?>
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= e($p['id']) ?>">
          <button class="btn btn--danger btn--sm" type="submit">Delete this job and its photos</button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<form method="post">
  <?= bww_csrf_field() ?>
  <input type="hidden" name="action" value="page">

  <div class="card">
    <h2>Gallery page wording</h2>
    <div class="field"><label>Small line above the heading</label><input type="text" name="hero[eyebrow]" value="<?= e($gp['hero']['eyebrow'] ?? '') ?>" maxlength="60"></div>
    <div class="field"><label>Main heading</label><input type="text" name="hero[heading]" value="<?= e($gp['hero']['heading'] ?? '') ?>" maxlength="140"></div>
    <div class="field"><label>Intro paragraph</label><textarea name="hero[text]" rows="3"><?= e($gp['hero']['text'] ?? '') ?></textarea></div>

    <div class="field toggle-field">
      <input type="hidden" name="g[show_filters]" value="0">
      <label class="toggle"><input type="checkbox" name="g[show_filters]" value="1" <?= !isset($gp['show_filters']) || $gp['show_filters'] ? 'checked' : '' ?>>
      <span>Let visitors filter the gallery by service</span></label>
    </div>

    <div class="row">
      <div class="field"><label>Label on the "show everything" button</label><input type="text" name="g[all_label]" value="<?= e($gp['all_label'] ?? '') ?>" maxlength="40"></div>
      <div class="field"><label>Heading when a filter finds nothing</label><input type="text" name="g[empty_heading]" value="<?= e($gp['empty_heading'] ?? '') ?>" maxlength="80"></div>
    </div>
    <div class="field"><label>Line when a filter finds nothing</label><input type="text" name="g[empty_text]" value="<?= e($gp['empty_text'] ?? '') ?>" maxlength="160"></div>

    <div class="row">
      <div class="field"><label>Page title for Google</label><input type="text" name="g[meta_title]" value="<?= e($gp['meta_title'] ?? '') ?>" maxlength="120"></div>
      <div class="field"><label>Description for search results</label><input type="text" name="g[meta_desc]" value="<?= e($gp['meta_desc'] ?? '') ?>" maxlength="200"></div>
    </div>
  </div>

  <div class="save-bar">
    <button class="btn btn--go" type="submit">Save page wording</button>
    <span>Jobs above save separately.</span>
  </div>
</form>

<?php require __DIR__ . '/includes/layout-end.php'; ?>
