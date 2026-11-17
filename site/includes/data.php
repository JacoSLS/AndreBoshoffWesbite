<?php
/**
 * Freeland Well and Water Services — shared data layer and helpers.
 * All content lives in flat JSON files under /data. No database required.
 */

define('BWW_ROOT', dirname(__DIR__));
define('BWW_DATA', BWW_ROOT . '/data');
define('BWW_UPLOADS', BWW_ROOT . '/assets/uploads');

/** Read a JSON data file, returning $fallback if it is missing or corrupt. */
function bww_read(string $name, $fallback = []) {
    $path = BWW_DATA . '/' . basename($name) . '.json';
    if (!is_file($path)) return $fallback;
    $out = json_decode(file_get_contents($path), true);
    return is_array($out) ? $out : $fallback;
}

/** Write a JSON data file atomically. */
function bww_write(string $name, array $data): bool {
    $path = BWW_DATA . '/' . basename($name) . '.json';
    $tmp  = $path . '.tmp';
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) return false;
    if (file_put_contents($tmp, $json, LOCK_EX) === false) return false;
    return rename($tmp, $path);
}

function bww_site(): array     { static $x = null; if ($x === null) $x = bww_read('site', []);     return $x; }
function bww_content(): array  {
    static $x = null;
    if ($x === null) {
        $x = bww_read('content', []);
        foreach (bww_preview_overlay()['content'] ?? [] as $section => $data) $x[$section] = $data;
    }
    return $x;
}

/**
 * Admin sign-in session. Session files live in data/sessions rather than the
 * host's shared folder, so the host's clean-up job cannot log someone out in
 * the middle of editing (which used to make "Save" silently fail).
 */
function bww_start_session(bool $read_only = false): void {
    if (session_status() !== PHP_SESSION_NONE) return;
    $dir = BWW_DATA . '/sessions';
    if (!is_dir($dir)) @mkdir($dir, 0700, true);
    if (is_dir($dir) && !is_file($dir . '/.htaccess')) @file_put_contents($dir . '/.htaccess', "Require all denied\n");
    if (is_dir($dir) && is_writable($dir)) session_save_path($dir);
    ini_set('session.gc_maxlifetime', '43200');   // 12 hours
    session_name('bww_admin');
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'secure' => $https, 'samesite' => 'Lax']);
    $read_only ? session_start(['read_and_close' => true]) : session_start();
}

/**
 * Unsaved edits for the admin's live preview pane. Only used when the page is
 * opened with ?bww_preview=1 by someone signed in to the admin.
 * Shape: ['content' => [section => data], 'services' => [...], 'at' => time]
 */
function bww_preview_overlay(): array {
    static $p = null;
    if ($p !== null) return $p;
    $p = [];
    if (empty($_GET['bww_preview']) || empty($_COOKIE['bww_admin'])) return $p;
    bww_start_session(true);
    $o = $_SESSION['bww_preview'] ?? null;
    if (empty($_SESSION['bww_ok']) || !is_array($o) || time() - ($o['at'] ?? 0) > 7200) return $p;
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex');
    return $p = $o;
}
function bww_services(): array {
    static $x = null;
    if ($x === null) $x = bww_preview_overlay()['services'] ?? bww_read('services', []);
    return $x;
}
function bww_gallery(): array  { static $x = null; if ($x === null) $x = bww_read('gallery', []);  return $x; }
function bww_legal(): array    { static $x = null; if ($x === null) $x = bww_read('legal', []);    return $x; }

/** Customer reviews with text, in the order set in the admin. */
function bww_reviews(): array {
    return array_values(array_filter((array)c('reviews_page.items', []),
        fn($r) => is_array($r) && trim($r['quote'] ?? '') !== ''));
}

/**
 * Menu items. Adds the Reviews page (before Contact) on sites whose saved menu
 * predates it. On the public site Reviews stays hidden until a review exists.
 */
function bww_nav(bool $public = true): array {
    $nav = (array)c('header.nav', []);
    if (!in_array('reviews', array_column($nav, 'slug'), true)) {
        $at = array_search('contact', array_column($nav, 'slug'), true);
        $item = ['slug' => 'reviews', 'label' => 'Reviews', 'show' => true];
        if ($at === false) $nav[] = $item; else array_splice($nav, $at, 0, [$item]);
    }
    if ($public && !bww_reviews()) {
        $nav = array_values(array_filter($nav, fn($i) => ($i['slug'] ?? '') !== 'reviews'));
    }
    return $nav;
}

/** Clean address of a page, e.g. page_url('services', 'plumbing') -> /services#plumbing */
function page_url(string $slug, string $hash = ''): string {
    $path = ($slug === '' || $slug === 'index') ? '/' : '/' . $slug;
    return $path . ($hash !== '' ? '#' . $hash : '');
}

/** Escape for HTML output. */
function e($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Site setting with fallback. */
function s(string $key, $default = '') {
    $site = bww_site();
    return $site[$key] ?? $default;
}

/**
 * Content lookup by dot path, e.g. c('home.hero.heading').
 * Everything the pages render comes through here, which is what makes the
 * whole site editable from the admin panel.
 */
function c(string $path, $default = '') {
    $node = bww_content();
    foreach (explode('.', $path) as $part) {
        if (!is_array($node) || !array_key_exists($part, $node)) return $default;
        $node = $node[$part];
    }
    return $node;
}

/** True unless the admin has switched the section off. */
function shown(string $path): bool {
    $v = c($path, true);
    return !($v === false || $v === 0 || $v === '0' || $v === '');
}

/** Turn an asset reference stored in the data files into a usable URL. */
function asset(string $rel, string $fallback = ''): string {
    $rel = ltrim(trim($rel), '/');
    if ($rel === '') $rel = ltrim($fallback, '/');
    if ($rel === '') return '';
    if (preg_match('#^https?://#i', $rel)) return $rel;
    if (!str_starts_with($rel, 'img/') && !str_starts_with($rel, 'uploads/')) $rel = 'img/' . $rel;
    return 'assets/' . $rel;
}

/** The smaller copy of an image ("name-sm.jpg") when there is one, otherwise the image itself. */
function asset_sm(string $url): string {
    $sm = preg_replace('/(\.[a-z0-9]+)$/i', '-sm$1', $url);
    return ($sm !== $url && is_file(BWW_ROOT . '/' . $sm)) ? $sm : $url;
}

/**
 * A copy of an uploaded photo cropped to a card's shape and size, e.g. 720x450.
 * Cards only ever show the centre of a photo, so this looks identical but is a
 * fraction of the download. Made the first time a page needs it, then reused.
 * Anything that is not an upload, or that the server cannot process, is
 * returned unchanged.
 */
function bww_card_img(string $url, int $w, int $h): string {
    if (!str_starts_with($url, 'assets/uploads/')) return $url;
    $file = basename($url);
    $out  = preg_replace('/(-sm)?\.(jpe?g|png|webp)$/i', '', $file) . '-c' . $w . 'x' . $h . '.jpg';
    if (is_file(BWW_UPLOADS . '/' . $out)) return 'assets/uploads/' . $out;
    $src  = BWW_UPLOADS . '/' . $file;
    $info = is_file($src) && function_exists('imagecreatetruecolor') ? @getimagesize($src) : false;
    if (!$info) return $url;
    [$sw, $sh, $type] = $info;
    $im = match ($type) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($src),
        IMAGETYPE_PNG  => @imagecreatefrompng($src),
        IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($src) : false,
        default        => false,
    };
    if (!$im) return $url;
    // the centre of the photo, in the card's shape (the same part the card shows)
    $ratio = $w / $h;
    if ($sw / $sh > $ratio) { $ch = $sh; $cw = (int)round($sh * $ratio); }
    else                    { $cw = $sw; $ch = (int)round($sw / $ratio); }
    $cx = (int)(($sw - $cw) / 2); $cy = (int)(($sh - $ch) / 2);
    $tw = min($w, $cw); $th = max(1, (int)round($tw / $ratio));   // never enlarge
    $dst = imagecreatetruecolor($tw, $th);
    imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
    imagecopyresampled($dst, $im, 0, 0, $cx, $cy, $tw, $th, $cw, $ch);
    $tmp = BWW_UPLOADS . '/' . $out . '.tmp';
    $ok  = @imagejpeg($dst, $tmp, 80) && @rename($tmp, BWW_UPLOADS . '/' . $out);
    imagedestroy($dst); imagedestroy($im);
    if (!$ok) { @unlink($tmp); return $url; }
    @chmod(BWW_UPLOADS . '/' . $out, 0644);
    return 'assets/uploads/' . $out;
}

/** width/height attributes for a local image, so the page does not jump while it loads. */
function img_size(string $url): string {
    $s = $url !== '' && !preg_match('#^https?://#i', $url) ? @getimagesize(BWW_ROOT . '/' . $url) : false;
    return $s ? ' width="' . $s[0] . '" height="' . $s[1] . '"' : '';
}

/**
 * Render an editable block of text: blank lines become paragraphs, and lines
 * beginning with "- " become a bullet list. Keeps the admin free of a rich
 * text editor while still allowing structure.
 */
function bww_rich(?string $text, string $class = ''): string {
    $text = trim((string)$text);
    if ($text === '') return '';
    $out = '';
    foreach (preg_split('/\n\s*\n/', str_replace("\r\n", "\n", $text)) as $block) {
        $block = trim($block);
        if ($block === '') continue;
        $lines = preg_split('/\n/', $block);
        $bullets = array_filter($lines, fn($l) => preg_match('/^\s*[-*]\s+/', $l));
        if (count($bullets) === count($lines)) {
            $out .= '<ul' . ($class ? ' class="' . e($class) . '"' : '') . '>';
            foreach ($lines as $l) $out .= '<li>' . e(preg_replace('/^\s*[-*]\s+/', '', $l)) . '</li>';
            $out .= '</ul>';
        } else {
            $out .= '<p>' . nl2br(e($block)) . '</p>';
        }
    }
    return $out;
}

/** Look up a service name by id. */
function bww_service_name(string $id): string {
    foreach (bww_services() as $sv) if (($sv['id'] ?? '') === $id) return $sv['name'] ?? $id;
    return 'General';
}

/** Short label for gallery pills and filter buttons. */
function bww_service_tag(string $id): string {
    foreach (bww_services() as $sv) if (($sv['id'] ?? '') === $id) return $sv['tag'] ?? $sv['name'] ?? $id;
    return 'General';
}

/** Current page slug, used for nav highlighting. */
function bww_page(): string {
    return preg_replace('/\.php$/', '', basename($_SERVER['SCRIPT_NAME'] ?? 'index.php'));
}

/** Human-friendly list, e.g. "A, B and C". */
function bww_list(array $items): string {
    $items = array_values(array_filter($items));
    $n = count($items);
    if ($n === 0) return '';
    if ($n === 1) return $items[0];
    return implode(', ', array_slice($items, 0, -1)) . ' and ' . end($items);
}

/** Inline SVG icon set — no icon font, no CDN. */
function icon(string $name, string $class = ''): string {
    $c = $class ? ' class="' . e($class) . '"' : '';
    $open = '<svg' . $c . ' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">';
    $paths = [
        'phone'  => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2Z"/>',
        'mail'   => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>',
        'pin'    => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
        'clock'  => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'check'  => '<path d="M20 6 9 17l-5-5"/>',
        'checkc' => '<circle cx="12" cy="12" r="10"/><path d="m8 12 3 3 5-6"/>',
        'arrow'  => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/>',
        'star'   => '<path d="m12 2 3.1 6.3 6.9 1-5 4.9 1.2 6.8L12 17.8 5.8 21l1.2-6.8-5-4.9 6.9-1Z"/>',
        'well'   => '<path d="M4 9h16M5 9l1.5 12h11L19 9M3 9l9-6 9 6"/><path d="M10 13h4v5h-4z"/>',
        'pump'   => '<path d="M12 2v6M9 4h6"/><rect x="8" y="8" width="8" height="13" rx="2"/><path d="M10 12h4M10 16h4"/><path d="M5 11v7M19 11v7"/>',
        'tank'   => '<rect x="6" y="3" width="12" height="18" rx="4"/><path d="M6 12h12M9 7h.01M12 7h.01"/>',
        'gauge'  => '<path d="M12 22a10 10 0 1 0-10-10"/><path d="M12 12 8 8"/><circle cx="12" cy="12" r="1.6"/><path d="M2 12h2M20 12h2M12 2v2"/>',
        'flask'  => '<path d="M9 2v6L4 18a2 2 0 0 0 1.8 3h12.4A2 2 0 0 0 20 18l-5-10V2"/><path d="M8 2h8M6.5 14h11"/>',
        'drop'   => '<path d="M12 2.7c3.5 4.2 6 7.1 6 10.3a6 6 0 0 1-12 0c0-3.2 2.5-6.1 6-10.3Z"/>',
        'wrench' => '<path d="M14.7 6.3a4 4 0 0 0 5 5l-9 9a2.8 2.8 0 0 1-4-4l9-9Z"/><path d="M14.7 6.3 18 3l3 3-3.3 3.3"/>',
        'facebook' => '<path d="M17 2h-3a5 5 0 0 0-5 5v3H6v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3Z" fill="currentColor" stroke="none"/>',
        'image'  => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/>',
        'lock'   => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'zap'    => '<path d="M13 2 3 14h9l-1 8 10-12h-9Z"/>',
        'home'   => '<path d="m3 10 9-7 9 7v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/><path d="M9 22V12h6v10"/>',
        'users'  => '<circle cx="9" cy="8" r="3"/><path d="M2 21v-1a5 5 0 0 1 5-5h4a5 5 0 0 1 5 5v1M17 8a3 3 0 1 0 0-5"/>',
        'file'   => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6M8 13h8M8 17h5"/>',
        'layout' => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/>',
    ];
    return $open . ($paths[$name] ?? $paths['check']) . '</svg>';
}

/** Icons a non-technical admin can pick from, with plain-language names. */
function bww_icon_choices(): array {
    return [
        'pump' => 'Pump', 'tank' => 'Tank', 'gauge' => 'Gauge / testing', 'flask' => 'Lab flask',
        'drop' => 'Water drop', 'wrench' => 'Spanner', 'well' => 'Well head', 'checkc' => 'Tick in a circle',
        'check' => 'Tick', 'shield' => 'Shield', 'star' => 'Star', 'clock' => 'Clock',
        'zap' => 'Lightning', 'pin' => 'Map pin', 'phone' => 'Phone', 'mail' => 'Envelope',
        'image' => 'Photo', 'home' => 'House', 'users' => 'People', 'file' => 'Document',
    ];
}
