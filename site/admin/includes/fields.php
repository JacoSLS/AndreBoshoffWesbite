<?php
/**
 * A tiny form framework so every editor page is described as data rather than
 * hand-written HTML plus hand-written save logic. Keeps the seven page editors
 * consistent and keeps the save path in one place.
 *
 * Field types:
 *   text     single line
 *   textarea multi-line; blank lines become paragraphs, "- " lines become bullets
 *   check    on/off switch for showing a section
 *   image    upload with preview, keep-existing and revert-to-default
 *   list     repeatable single values
 *   repeat   repeatable groups of sub-fields (text / textarea / icon)
 *   icon     pick from the built-in icon set
 */

function bww_field(array $f, array $data, string $prefix): void {
    $key   = $f['key'];
    $name  = $prefix . '[' . $key . ']';
    $id    = 'f_' . preg_replace('/[^a-z0-9]+/i', '_', $prefix . '_' . $key);
    $val   = $data[$key] ?? ($f['default'] ?? '');
    $label = $f['label'] ?? $key;
    $hint  = $f['hint'] ?? '';
    $type  = $f['type'] ?? 'text';

    switch ($type) {

    case 'check':
        $on = !($val === false || $val === 0 || $val === '0' || $val === '');
        echo '<div class="field toggle-field">';
        echo '<input type="hidden" name="' . e($name) . '" value="0">';
        echo '<label class="toggle"><input type="checkbox" name="' . e($name) . '" value="1" ' . ($on ? 'checked' : '') . '>';
        echo '<span>' . e($label) . '</span></label>';
        if ($hint) echo '<p class="hint">' . e($hint) . '</p>';
        echo '</div>';
        break;

    case 'textarea':
        echo '<div class="field"><label for="' . e($id) . '">' . e($label) . '</label>';
        echo '<textarea id="' . e($id) . '" name="' . e($name) . '" rows="' . (int)($f['rows'] ?? 5) . '">' . e($val) . '</textarea>';
        echo '<p class="hint">' . e($hint ?: 'Leave a blank line between paragraphs. Start a line with "- " to make a bullet point.') . '</p>';
        echo '</div>';
        break;

    case 'select':
        $val = ($val === '' || $val === null) ? ($f['default'] ?? '') : $val;
        echo '<div class="field"><label for="' . e($id) . '">' . e($label) . '</label>';
        echo '<select id="' . e($id) . '" name="' . e($name) . '">';
        foreach ($f['options'] as $k => $lbl) {
            echo '<option value="' . e($k) . '"' . ((string)$val === (string)$k ? ' selected' : '') . '>' . e($lbl) . '</option>';
        }
        echo '</select>';
        if ($hint) echo '<p class="hint">' . e($hint) . '</p>';
        echo '</div>';
        break;

    case 'icon':
        echo '<div class="field"><label for="' . e($id) . '">' . e($label) . '</label>';
        echo '<div class="icon-pick"><span class="icon-pick__preview">' . icon($val ?: 'checkc') . '</span>';
        echo '<select id="' . e($id) . '" name="' . e($name) . '" class="icon-select">';
        foreach (bww_icon_choices() as $k => $lbl) {
            echo '<option value="' . e($k) . '"' . ($val === $k ? ' selected' : '') . '>' . e($lbl) . '</option>';
        }
        echo '</select></div>';
        if ($hint) echo '<p class="hint">' . e($hint) . '</p>';
        echo '</div>';
        break;

    case 'image':
        $cur  = trim((string)$val);
        $show = $cur !== '' ? $cur : trim((string)($f['default'] ?? ''));
        echo '<div class="field image-field"><label>' . e($label) . '</label>';
        echo '<div class="image-field__row">';
        if ($show) {
            echo '<div class="image-field__preview"><img src="../' . e(asset($show)) . '" alt=""></div>';
        }
        echo '<div class="image-field__controls">';
        echo '<input type="file" name="' . e($prefix . '_file_' . $key) . '" accept="image/jpeg,image/png,image/webp">';
        echo '<input type="hidden" name="' . e($name) . '" value="' . e($cur) . '">';
        if ($cur) {
            echo '<label class="minor"><input type="checkbox" name="' . e($prefix . '_clear_' . $key) . '" value="1"> Remove this image</label>';
        }
        echo '<p class="hint">' . e($hint ?: 'Choose a file to replace it. Leave empty to keep the current one.') . '</p>';
        echo '</div></div></div>';
        break;

    case 'list':
        $items = is_array($val) ? $val : [];
        $items[] = '';
        echo '<div class="field"><label>' . e($label) . '</label><div class="repeat" data-repeat>';
        foreach ($items as $item) {
            echo '<div class="repeat-row"><input type="text" name="' . e($name) . '[]" value="' . e($item) . '"'
               . ' placeholder="' . e($f['placeholder'] ?? '') . '"><button type="button" class="row-x" data-remove-row aria-label="Remove">&times;</button></div>';
        }
        echo '</div><button type="button" class="btn btn--ghost btn--sm" data-add-row>+ Add another</button>';
        echo '<p class="hint">' . e($hint ?: 'Empty lines are ignored. Clear a box to remove that item.') . '</p></div>';
        break;

    case 'repeat':
        $rows = is_array($val) ? array_values($val) : [];
        $rows[] = [];   // one blank row ready to fill in
        echo '<div class="field"><label>' . e($label) . '</label>';
        if ($hint) echo '<p class="hint" style="margin-top:-.15rem;margin-bottom:.6rem">' . e($hint) . '</p>';
        echo '<div class="repeat repeat--group" data-repeat>';
        foreach ($rows as $ri => $row) {
            echo '<div class="repeat-group">';
            echo '<button type="button" class="row-x" data-remove-row aria-label="Remove">&times;</button>';
            foreach ($f['fields'] as $sub) {
                $sk = $sub['key'];
                $sn = $name . '[' . $ri . '][' . $sk . ']';
                $sv = $row[$sk] ?? '';
                echo '<div class="sub-field"><label>' . e($sub['label'] ?? $sk) . '</label>';
                if (($sub['type'] ?? 'text') === 'textarea') {
                    echo '<textarea name="' . e($sn) . '" rows="' . (int)($sub['rows'] ?? 3) . '">' . e($sv) . '</textarea>';
                } elseif (($sub['type'] ?? '') === 'icon') {
                    echo '<select name="' . e($sn) . '" class="icon-select">';
                    foreach (bww_icon_choices() as $k => $lbl) {
                        echo '<option value="' . e($k) . '"' . ($sv === $k ? ' selected' : '') . '>' . e($lbl) . '</option>';
                    }
                    echo '</select>';
                } else {
                    echo '<input type="text" name="' . e($sn) . '" value="' . e($sv) . '" placeholder="' . e($sub['placeholder'] ?? '') . '">';
                }
                echo '</div>';
            }
            echo '</div>';
        }
        echo '</div><button type="button" class="btn btn--ghost btn--sm" data-add-group>+ Add another</button>';
        echo '<p class="hint">Clear the first box of a block to delete it.</p></div>';
        break;

    default: // text
        echo '<div class="field"><label for="' . e($id) . '">' . e($label) . '</label>';
        echo '<input type="text" id="' . e($id) . '" name="' . e($name) . '" value="' . e($val) . '"'
           . ' maxlength="' . (int)($f['max'] ?? 300) . '" placeholder="' . e($f['placeholder'] ?? '') . '">';
        if ($hint) echo '<p class="hint">' . e($hint) . '</p>';
        echo '</div>';
    }
}

/** Render a titled group of fields. */
function bww_group(string $title, string $intro, array $fields, array $data, string $prefix): void {
    echo '<div class="card"><h2>' . e($title) . '</h2>';
    if ($intro) echo '<p class="card__hint">' . e($intro) . '</p>';
    foreach ($fields as $f) bww_field($f, $data, $prefix);
    echo '</div>';
}

/** A submitted text value: trimmed and cut to the field's maximum length (10000 if none is set). */
function bww_clean_text($raw, array $f): string {
    if (!is_string($raw)) return '';
    $max = (int)($f['max'] ?? 10000);
    if (function_exists('mb_substr')) return mb_substr(trim($raw), 0, $max);
    // without mbstring: count whole UTF-8 characters, never split one
    return preg_match('/^.{0,' . $max . '}/su', trim($raw), $m) ? $m[0] : '';
}

/**
 * Collect submitted values for a field spec, merging over what is already
 * stored so untouched keys survive.
 */
function bww_collect(array $fields, array $posted, array $existing, string $prefix): array {
    $out = $existing;

    foreach ($fields as $f) {
        $key  = $f['key'];
        $type = $f['type'] ?? 'text';
        $raw  = $posted[$key] ?? null;

        switch ($type) {

        case 'check':
            $out[$key] = !empty($raw) && $raw !== '0';
            break;

        case 'image':
            $keep = trim((string)($raw ?? ''));
            if (!empty($GLOBALS['BWW_PREVIEW'])) {   // preview: never upload or delete files
                $out[$key] = empty($_POST[$prefix . '_clear_' . $key]) ? $keep : '';
                break;
            }
            if (!empty($_POST[$prefix . '_clear_' . $key])) {
                bww_delete_later($keep);   // removed only once the save succeeds
                $out[$key] = '';
                break;
            }
            $up = $_FILES[$prefix . '_file_' . $key] ?? null;
            if ($up && ($up['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $mode = ($f['mode'] ?? 'photo');
                $res = bww_handle_upload($up, $mode);
                if ($res['ok']) {
                    bww_delete_later($keep);
                    bww_new_upload('uploads/' . $res['file']);
                    $out[$key] = 'uploads/' . $res['file'];
                } else {
                    $GLOBALS['BWW_FIELD_ERRORS'][] = ($f['label'] ?? $key) . ': ' . $res['error'];
                    $out[$key] = $keep;
                }
            } else {
                $out[$key] = $keep;
            }
            break;

        case 'select':
            $v = is_string($raw) ? $raw : '';
            if (array_key_exists($v, $f['options'] ?? [])) $out[$key] = $v;   // otherwise keep the old value
            break;

        case 'icon':
            $v = is_string($raw) ? $raw : '';
            if (array_key_exists($v, bww_icon_choices())) $out[$key] = $v;
            break;

        case 'list':
            $vals = array_values(array_filter(array_map(fn($v) => bww_clean_text($v, $f), (array)($raw ?? [])), fn($v) => $v !== ''));
            $out[$key] = array_slice($vals, 0, 30);
            break;

        case 'repeat':
            $rows = [];
            foreach ((array)($raw ?? []) as $row) {
                if (!is_array($row)) continue;
                $clean = [];
                foreach ($f['fields'] as $sub) {
                    $v = $row[$sub['key']] ?? '';
                    $clean[$sub['key']] = ($sub['type'] ?? '') === 'icon'
                        ? (is_string($v) && array_key_exists($v, bww_icon_choices()) ? $v : 'checkc')
                        : bww_clean_text($v, $sub);
                }
                $first = $f['fields'][0]['key'];
                if (($clean[$first] ?? '') === '') continue;   // blank first field = deleted row
                $rows[] = $clean;
            }
            $out[$key] = array_slice($rows, 0, (int)($f['max_rows'] ?? 30));
            break;

        default:
            $out[$key] = bww_clean_text($raw ?? '', $f);
        }
    }
    return $out;
}

/** Save a top-level section of content.json. */
function bww_save_content(string $section, array $values): bool {
    $all = bww_read('content', []);
    $all[$section] = $values;
    return bww_write('content', $all);
}
