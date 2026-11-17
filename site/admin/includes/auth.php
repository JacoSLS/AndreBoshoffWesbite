<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__, 2) . '/includes/data.php';

bww_start_session();

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
        unset($_SESSION['bww_fails'], $_SESSION['bww_lock']);
    }
    return $ok;
}

function bww_locked_out(): int {
    if (!empty($_SESSION['bww_lock']) && $_SESSION['bww_lock'] > time()) {
        return (int)ceil(($_SESSION['bww_lock'] - time()) / 60);
    }
    return 0;
}

function bww_register_failure(): void {
    $_SESSION['bww_fails'] = ($_SESSION['bww_fails'] ?? 0) + 1;
    if ($_SESSION['bww_fails'] >= BWW_MAX_ATTEMPTS) {
        $_SESSION['bww_lock']  = time() + BWW_LOCKOUT_MINUTES * 60;
        $_SESSION['bww_fails'] = 0;
    }
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
