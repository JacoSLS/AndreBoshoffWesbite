<?php
/**
 * Runs one editor page from a declarative list of groups.
 * Each group maps to a sub-object of a section in content.json.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/images.php';
require_once __DIR__ . '/fields.php';

function bww_run_editor(string $section, array $groups, array $opts): void {
    bww_require_login();
    $GLOBALS['BWW_FIELD_ERRORS'] = [];

    $self = basename($_SERVER['SCRIPT_NAME']);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!bww_csrf_ok()) {
            bww_flash('Your session timed out. Please try that again.', 'err');
            header('Location: ' . $self); exit;
        }

        $all  = bww_read('content', []);
        $data = $all[$section] ?? [];
        $GLOBALS['BWW_PREVIEW'] = !empty($_POST['_preview']);

        foreach ($groups as $g) {
            $prefix   = $g['key'];
            $path     = $g['path'] ?? $g['key'];
            $posted   = $_POST[$prefix] ?? [];
            if ($path === '') {
                $data = bww_collect($g['fields'], $posted, $data, $prefix);
            } else {
                $data[$path] = bww_collect($g['fields'], $posted, $data[$path] ?? [], $prefix);
            }
        }

        if ($GLOBALS['BWW_PREVIEW']) {   // live preview: hold the unsaved version in the session only
            bww_preview_store('content', $data, $section);
        }
        unset($_SESSION['bww_preview']);

        $all[$section] = $data;
        bww_write('content', $all);

        $errs = $GLOBALS['BWW_FIELD_ERRORS'];
        bww_flash($errs
            ? 'Saved, but some images did not upload:<br>' . implode('<br>', array_map('e', $errs))
            : 'Saved — your website has been updated. <a href="' . e($opts['view'] ?? '../index.php') . '" target="_blank" rel="noopener">View the page</a>.',
            $errs ? 'warn' : 'ok');
        header('Location: ' . $self); exit;
    }

    $content = bww_read('content', []);
    $data    = $content[$section] ?? [];

    $ADMIN_PAGE  = $opts['admin_page'];
    $ADMIN_TITLE = $opts['title'];
    require __DIR__ . '/layout.php';
    ?>
    <div class="page-title">
      <h1><?= e($opts['title']) ?></h1>
      <p><?= e($opts['intro']) ?></p>
    </div>

    <div class="editor-actions">
      <a class="btn btn--ghost btn--sm" href="<?= e($opts['view'] ?? '../index.php') ?>" target="_blank" rel="noopener">See this page on the website &nearr;</a>
    </div>

    <form method="post" enctype="multipart/form-data" data-pv-live>
      <?= bww_csrf_field() ?>
      <?php foreach ($groups as $g):
        $path = $g['path'] ?? $g['key'];
        $gd   = $path === '' ? $data : ($data[$path] ?? []);
      ?>
        <?php bww_group($g['title'], $g['intro'] ?? '', $g['fields'], $gd, $g['key']); ?>
      <?php endforeach; ?>

      <div class="save-bar">
        <button class="btn btn--go" type="submit">Save changes</button>
        <span>Changes go live on the website as soon as you save.</span>
      </div>
    </form>
    <?php
    require __DIR__ . '/layout-end.php';
}
