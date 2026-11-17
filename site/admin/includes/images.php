<?php
/**
 * Image intake: validate, correct orientation, resize, save.
 * Uses GD, which is present on effectively every shared host.
 */

function bww_gd_available(): bool {
    return function_exists('imagecreatetruecolor') && function_exists('imagejpeg');
}

/**
 * Handle one uploaded image.
 * @param array  $file  entry from $_FILES
 * @param string $mode  'photo' (JPEG, makes a thumbnail) or 'logo' (keeps PNG transparency)
 * @return array{ok:bool, error:string, file:string, thumb:string}
 */
function bww_handle_upload(array $file, string $mode = 'photo'): array {
    $fail = fn(string $m) => ['ok' => false, 'error' => $m, 'file' => '', 'thumb' => ''];

    if (!isset($file['error']) || is_array($file['error'])) return $fail('Something went wrong with that upload.');

    switch ($file['error']) {
        case UPLOAD_ERR_OK: break;
        case UPLOAD_ERR_NO_FILE: return $fail('Please choose a file first.');
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return $fail('That file is larger than the server allows. Try a smaller one, or ask your web person to raise the upload limit.');
        default: return $fail('The file did not upload correctly. Please try again.');
    }

    if ($file['size'] > BWW_MAX_UPLOAD_MB * 1048576) {
        return $fail('That file is bigger than ' . BWW_MAX_UPLOAD_MB . ' MB. Please choose a smaller one.');
    }
    if (!bww_gd_available()) {
        return $fail('This server cannot process images (the GD extension is switched off). Ask your web person to enable it.');
    }

    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        return $fail('That file is not an image the website can read. If it came from an iPhone it may be a HEIC file — set your camera to "Most Compatible" and try again, or email the photo to yourself first.');
    }

    [$w, $h, $type] = $info;
    $src = match ($type) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($file['tmp_name']),
        IMAGETYPE_PNG  => @imagecreatefrompng($file['tmp_name']),
        IMAGETYPE_GIF  => @imagecreatefromgif($file['tmp_name']),
        IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($file['tmp_name']) : false,
        default        => false,
    };
    if (!$src) return $fail('That image format is not supported. Please use a JPG or PNG.');

    // Phones record orientation in EXIF rather than rotating the pixels.
    if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif = @exif_read_data($file['tmp_name']);
        $o = $exif['Orientation'] ?? 1;
        if ($o === 3)     $src = imagerotate($src, 180, 0);
        elseif ($o === 6) $src = imagerotate($src, -90, 0);
        elseif ($o === 8) $src = imagerotate($src, 90, 0);
        if (in_array($o, [6, 8], true)) { [$w, $h] = [$h, $w]; }
    }

    if (!is_dir(BWW_UPLOADS)) @mkdir(BWW_UPLOADS, 0755, true);
    if (!is_writable(BWW_UPLOADS)) {
        imagedestroy($src);
        return $fail('The uploads folder is not writable on the server. Set /assets/uploads to permission 755 and try again.');
    }

    $base = date('Ymd-His') . '-' . bin2hex(random_bytes(3));

    if ($mode === 'logo') {
        // Logos keep their transparency, so they stay PNG and are not thumbnailed.
        $out = $base . '.png';
        $ok = bww_resize_png($src, $w, $h, BWW_LOGO_MAX_W, BWW_UPLOADS . '/' . $out);
        imagedestroy($src);
        return $ok
            ? ['ok' => true, 'error' => '', 'file' => $out, 'thumb' => $out]
            : $fail('The logo could not be saved. Please try again.');
    }

    $full  = $base . '.jpg';
    $thumb = $base . '-sm.jpg';
    $okFull  = bww_resize_to($src, $w, $h, BWW_IMG_MAX_W, BWW_UPLOADS . '/' . $full);
    $okThumb = bww_resize_to($src, $w, $h, BWW_THUMB_W,   BWW_UPLOADS . '/' . $thumb);
    imagedestroy($src);

    if (!$okFull) {
        @unlink(BWW_UPLOADS . '/' . $thumb);
        return $fail('The image could not be saved. Please try again.');
    }
    return ['ok' => true, 'error' => '', 'file' => $full, 'thumb' => $okThumb ? $thumb : $full];
}

function bww_resize_to($src, int $w, int $h, int $maxW, string $dest): bool {
    $scale = $w > $maxW ? $maxW / $w : 1;
    $nw = max(1, (int)round($w * $scale));
    $nh = max(1, (int)round($h * $scale));

    $out = imagecreatetruecolor($nw, $nh);
    $white = imagecolorallocate($out, 255, 255, 255);
    imagefilledrectangle($out, 0, 0, $nw, $nh, $white);
    imagecopyresampled($out, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

    $ok = imagejpeg($out, $dest, BWW_JPEG_QUALITY);
    imagedestroy($out);
    if ($ok) @chmod($dest, 0644);
    return (bool)$ok;
}

function bww_resize_png($src, int $w, int $h, int $maxW, string $dest): bool {
    $scale = $w > $maxW ? $maxW / $w : 1;
    $nw = max(1, (int)round($w * $scale));
    $nh = max(1, (int)round($h * $scale));

    $out = imagecreatetruecolor($nw, $nh);
    imagealphablending($out, false);
    imagesavealpha($out, true);
    $transparent = imagecolorallocatealpha($out, 0, 0, 0, 127);
    imagefilledrectangle($out, 0, 0, $nw, $nh, $transparent);
    imagecopyresampled($out, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

    $ok = imagepng($out, $dest, 6);
    imagedestroy($out);
    if ($ok) @chmod($dest, 0644);
    return (bool)$ok;
}

/** Delete an uploaded file (and its thumbnail) if it lives in /assets/uploads. */
function bww_delete_upload(?string $ref): void {
    $ref = trim((string)$ref);
    if ($ref === '' || !str_starts_with($ref, 'uploads/')) return;   // never touch shipped /img assets
    $name = basename($ref);
    foreach ([$name, preg_replace('/\.jpg$/', '-sm.jpg', $name)] as $f) {
        $path = BWW_UPLOADS . '/' . $f;
        if (is_file($path)) @unlink($path);
    }
    // card-sized copies made by bww_card_img(), e.g. name-c720x450.jpg
    foreach (glob(BWW_UPLOADS . '/' . preg_replace('/\.[a-z]+$/i', '', $name) . '-c*x*.jpg') ?: [] as $path) @unlink($path);
}

/** Remove every photo belonging to a gallery post. */
function bww_delete_photos(array $photos): void {
    foreach ($photos as $ph) {
        foreach (['file', 'thumb'] as $k) {
            $n = $ph[$k] ?? '';
            if ($n && is_file(BWW_UPLOADS . '/' . basename($n))) @unlink(BWW_UPLOADS . '/' . basename($n));
        }
    }
}
