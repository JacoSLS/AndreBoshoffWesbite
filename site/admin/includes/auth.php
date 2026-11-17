<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__, 2) . '/includes/data.php';

bww_start_session();

// One admin save at a time: every change reads the data, edits it and writes it
// back, so two saves at the same moment could undo each other. The lock is
// released automatically when the request ends.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $GLOBALS['BWW_SAVE_LOCK'] = @fopen(BWW_DATA . '/admin-save.lock', 'c');
    if (!$GLOBALS['BWW_SAVE_LOCK'] || !flock($GLOBALS['BWW_SAVE_LOCK'], LOCK_EX)) {
        error_log('Admin: could not lock data/admin-save.lock');
        bww_flash('Nothing was saved: the website could not lock its data folder. Please try again. If it keeps happening, ask your web person to check the data folder.', 'err');
        header('Location: ' . basename($_SERVER['SCRIPT_NAME'] ?? 'dashboard.php')); exit;
    }
}

/** Largest upload the server accepts in one save, in bytes. */
function bww_upload_limit(): int {
    $b = function ($v) { $v = trim((string)$v); $n = (float)$v; $u = strtolower(substr($v, -1));
        return (int)($u === 'g' ? $n * 1073741824 : ($u === 'm' ? $n * 1048576 : ($u === 'k' ? $n * 1024 : $n))); };
    $lim = array_filter([$b(ini_get('upload_max_filesize')), $b(ini_get('post_max_size')), BWW_MAX_UPLOAD_MB * 1048576]);
    return $lim ? min($lim) : BWW_MAX_UPLOAD_MB * 1048576;
}

// When a save is bigger than the server allows (usually a large phone photo),
// PHP throws the whole form away. Say so plainly instead of "session timed out".
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && empty($_POST) && empty($_FILES) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    bww_flash('Nothing was saved: that photo is too big for the server (the limit is ' . round(bww_upload_limit() / 1048576, 1) . ' MB). Please choose a smaller photo and save again.', 'err');
    header('Location: ' . basename($_SERVER['SCRIPT_NAME'] ?? 'dashboard.php')); exit;
}

/** Load the credential file. There is no built-in password: until data/auth.json exists, nobody can sign in. */
function bww_auth(): array {
    return bww_read('auth', []);
}

/*
 * Saving with uploads. Files replaced or removed during a save are only deleted
 * once the data file has been written; if the write fails, the photos uploaded
 * in this request are removed again and the old ones stay.
 */
function bww_new_upload(string $ref): void   { $GLOBALS['BWW_NEW_UPLOADS'][] = $ref; }
function bww_delete_later(?string $ref): void { if (trim((string)$ref) !== '') $GLOBALS['BWW_DELETE_LATER'][] = $ref; }

/** Write a data file. Returns false (and shows an error) if it could not be saved. */
function bww_save(string $name, array $data): bool {
    $ok = bww_write($name, $data);
    $files = $ok ? ($GLOBALS['BWW_DELETE_LATER'] ?? []) : ($GLOBALS['BWW_NEW_UPLOADS'] ?? []);
    if ($files && function_exists('bww_delete_upload')) foreach ($files as $f) bww_delete_upload($f);
    $GLOBALS['BWW_DELETE_LATER'] = $GLOBALS['BWW_NEW_UPLOADS'] = [];
    if (!$ok) bww_flash('Nothing was saved: the website could not write its data file. Please try again. If it keeps happening, ask your web person to check the data folder.', 'err');
    return $ok;
}

function bww_logged_in(): bool {
    return !empty($_SESSION['bww_ok']) && $_SESSION['bww_ok'] === true;
}

function bww_require_login(): void {
    if (!bww_logged_in()) {
        header('Location: index.php?expired=1');
        exit;
    }
    // Roll the session id occasionally to limit fixation risk.
    if (empty($_SESSION['bww_seen']) || time() - $_SESSION['bww_seen'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['bww_seen'] = time();
    }
}

function bww_attempt_login(string $user, string $pass): bool {
    $auth = bww_auth();
    $ok = hash_equals(strtolower($auth['user'] ?? ''), strtolower($user))
        && password_verify($pass, $auth['hash'] ?? '');
    if ($ok) {
        session_regenerate_id(true);
        $_SESSION['bww_ok']   = true;
        $_SESSION['bww_user'] = $auth['user'];
        $_SESSION['bww_seen'] = time();
        bww_rate_clear('login');
    }
    return $ok;
}

/** Minutes this visitor is still locked out of signing in (0 = not locked). Stored on the server, not in the session. */
function bww_locked_out(): int {
    $s = bww_rate_blocked('login');
    return $s > 0 ? (int)ceil($s / 60) : 0;
}

/** Claim one sign-in attempt before checking the password (false = locked out, or the limit could not be checked). */
function bww_take_login_attempt(): bool {
    return bww_rate_take('login', BWW_MAX_ATTEMPTS, BWW_LOCKOUT_MINUTES * 60, BWW_LOCKOUT_MINUTES * 60);
}

/* ---- CSRF ---- */
function bww_csrf(): string {
    if (empty($_SESSION['bww_csrf'])) $_SESSION['bww_csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['bww_csrf'];
}
function bww_csrf_ok(): bool {
    return hash_equals($_SESSION['bww_csrf'] ?? '', $_POST['csrf'] ?? '');
}
function bww_csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . e(bww_csrf()) . '">';
}

/* ---- live preview ---- */
/**
 * Hold unsaved edits in the session so the preview pane can show them, then
 * answer the background request. $kind is 'content' (one section of
 * content.json) or 'services' (the whole services list).
 */
function bww_preview_store(string $kind, array $data, string $section = ''): void {
    $p = $_SESSION['bww_preview'] ?? [];
    if ($kind === 'content') $p['content'][$section] = $data; else $p[$kind] = $data;
    $p['at'] = time();
    $_SESSION['bww_preview'] = $p;
    header('Content-Type: application/json');
    echo '{"ok":true}';
    exit;
}

/* ---- flash messages ---- */
function bww_flash(string $msg = null, string $type = 'ok') {
    if ($msg !== null) { $_SESSION['bww_flash'] = ['m' => $msg, 't' => $type]; return null; }
    $f = $_SESSION['bww_flash'] ?? null;
    unset($_SESSION['bww_flash']);
    return $f;
}

/** Is the panel still on the factory password? */
function bww_using_default_password(): bool {
    $auth = bww_read('auth', []);
    return !empty($auth['is_default']);
}
