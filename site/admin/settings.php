<?php
require_once __DIR__ . '/includes/auth.php';
bww_require_login();

$site = bww_site();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!bww_csrf_ok()) {
        bww_flash('Your session timed out. Please try that again.', 'err');
        header('Location: settings.php'); exit;
    }
    $action = $_POST['action'] ?? '';

    /* ---------- contact details ---------- */
    if ($action === 'contact') {
        $phone = trim($_POST['phone_display'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $to    = trim($_POST['form_recipient'] ?? '');
        $errs  = [];

        $fb    = trim($_POST['facebook'] ?? '');

        if ($phone === '') $errs[] = 'Please enter a phone number.';
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errs[] = 'That email address does not look right.';
        if ($to !== '' && !filter_var($to, FILTER_VALIDATE_EMAIL)) $errs[] = 'The address for enquiries does not look right.';
        if ($fb !== '' && (!filter_var($fb, FILTER_VALIDATE_URL) || !preg_match('#^https://#i', $fb))) {
            $errs[] = 'The Facebook address must be a full link starting with https://';
        }

        if ($errs) {
            bww_flash(implode('<br>', array_map('e', $errs)), 'err');
        } else {
            $site['phone_display']  = $phone;
            // Rebuild the dial-able version: digits only, assume US if 10 digits.
            $digits = preg_replace('/\D+/', '', $phone);
            if (strlen($digits) === 10) $digits = '1' . $digits;
            $site['phone_link']     = '+' . $digits;
            $site['email']          = $email;
            $site['form_recipient'] = $to ?: $email;
            $site['facebook']       = $fb;
            $site['emergency_line'] = trim($_POST['emergency_line'] ?? '');
            $site['area_note']      = trim($_POST['area_note'] ?? '');

            $areas = array_values(array_filter(array_map('trim', (array)($_POST['areas'] ?? []))));
            $site['areas'] = array_slice($areas, 0, 12);

            $days  = (array)($_POST['h_days'] ?? []);
            $times = (array)($_POST['h_time'] ?? []);
            $hours = [];
            foreach ($days as $i => $d) {
                $d = trim($d); $t = trim($times[$i] ?? '');
                if ($d !== '' && $t !== '') $hours[] = ['days' => $d, 'time' => $t];
            }
            $site['hours'] = $hours;

            if (bww_save('site', $site)) bww_flash('Saved — your website has been updated.', 'ok');
        }
        header('Location: settings.php'); exit;
    }

    /* ---------- tracking ---------- */
    if ($action === 'tracking') {
        $ga  = trim($_POST['ga4_id'] ?? '');
        $gsc = trim($_POST['gsc_verify'] ?? '');
        $px  = trim($_POST['meta_pixel_id'] ?? '');
        $url = rtrim(trim($_POST['site_url'] ?? ''), '/');
        $errs = [];
        if ($url !== '' && (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url))) $errs[] = 'The website address must start with https:// (for example https://www.freelandwellandwater.com).';
        if ($ga  !== '' && !preg_match('/^G-[A-Z0-9]{4,20}$/', $ga))      $errs[] = 'The Google Analytics ID should look like G-XXXXXXXXXX.';
        if ($gsc !== '' && !preg_match('/^[A-Za-z0-9_-]{10,100}$/', $gsc)) $errs[] = 'The Search Console code should be only the code itself (letters, numbers, - and _), not the whole tag.';
        if ($px  !== '' && !preg_match('/^\d{5,20}$/', $px))              $errs[] = 'The Facebook Pixel ID should be numbers only.';
        if ($errs) {
            bww_flash('Nothing was saved:<br>' . implode('<br>', array_map('e', $errs)), 'err');
        } else {
            $site['ga4_id']        = $ga;
            $site['gsc_verify']    = $gsc;
            $site['meta_pixel_id'] = $px;
            $site['site_url']      = $url;
            if (bww_save('site', $site)) bww_flash('Saved.', 'ok');
        }
        header('Location: settings.php'); exit;
    }

    /* ---------- password ---------- */
    if ($action === 'password') {
        $cur  = (string)($_POST['current'] ?? '');
        $new  = (string)($_POST['new'] ?? '');
        $conf = (string)($_POST['confirm'] ?? '');
        $auth = bww_auth();

        if (!password_verify($cur, $auth['hash'])) {
            bww_flash('Your current password was not right.', 'err');
        } elseif (strlen($new) < 8) {
            bww_flash('Please choose a password of at least 8 characters. Three unrelated words works well.', 'err');
        } elseif ($new !== $conf) {
            bww_flash('The two new passwords did not match.', 'err');
        } else {
            $auth['hash'] = password_hash($new, PASSWORD_DEFAULT);
            $auth['is_default'] = false;
            $auth['changed'] = date('c');
            $newUser = trim($_POST['username'] ?? '');
            if ($newUser !== '') { $auth['user'] = $newUser; $_SESSION['bww_user'] = $newUser; }
            if (bww_save('auth', $auth)) bww_flash('Password changed. Keep it somewhere safe.', 'ok');
        }
        header('Location: settings.php#password'); exit;
    }
}

$ADMIN_PAGE  = 'settings';
$ADMIN_TITLE = 'Contact & Settings';
require __DIR__ . '/includes/layout.php';
?>

<div class="page-title">
  <h1>Contact details &amp; settings</h1>
  <p>Change these and they update everywhere on the website at once.</p>
</div>

<div class="card">
  <h2>Contact details</h2>
  <p class="card__hint">These appear in the top bar, the footer, the call buttons and on the contact page.</p>

  <form method="post">
    <?= bww_csrf_field() ?>
    <input type="hidden" name="action" value="contact">

    <div class="row">
      <div class="field">
        <label for="phone">Phone number</label>
        <input type="text" id="phone" name="phone_display" value="<?= e($site['phone_display'] ?? '') ?>" required>
        <p class="hint">Write it how you want it read, e.g. (443) 547-4605. The call button works it out.</p>
      </div>
      <div class="field">
        <label for="email">Email address shown on the site</label>
        <input type="email" id="email" name="email" value="<?= e($site['email'] ?? '') ?>">
      </div>
    </div>

    <div class="field">
      <label for="to">Send contact form enquiries to</label>
      <input type="email" id="to" name="form_recipient" value="<?= e($site['form_recipient'] ?? '') ?>">
      <p class="hint">Where messages from the website's contact form land. Usually the same as above.</p>
    </div>

    <div class="field">
      <label for="fb">Facebook page address (optional)</label>
      <input type="url" id="fb" name="facebook" value="<?= e($site['facebook'] ?? '') ?>" placeholder="https://www.facebook.com/yourpage">
      <p class="hint">Leave empty and the Facebook icon simply will not show.</p>
    </div>

    <div class="field">
      <label>Areas you cover</label>
      <?php $areas = $site['areas'] ?? []; $areas[] = ''; foreach ($areas as $a): ?>
        <div class="repeat-row"><input type="text" name="areas[]" value="<?= e($a) ?>" maxlength="60" placeholder="e.g. Baltimore County"></div>
      <?php endforeach; ?>
      <p class="hint">Empty boxes are ignored. These show on the home page and in the footer.</p>
    </div>

    <div class="field">
      <label for="an">Line about your service area</label>
      <input type="text" id="an" name="area_note" value="<?= e($site['area_note'] ?? '') ?>" maxlength="180">
    </div>

    <div class="field">
      <label>Opening hours</label>
      <?php
        $hours = $site['hours'] ?? [];
        $hours[] = ['days' => '', 'time' => ''];
        foreach ($hours as $h): ?>
        <div class="repeat-row">
          <input type="text" name="h_days[]" value="<?= e($h['days']) ?>" placeholder="e.g. Monday – Friday">
          <input type="text" name="h_time[]" value="<?= e($h['time']) ?>" placeholder="e.g. 6:00am – 6:00pm">
        </div>
      <?php endforeach; ?>
      <p class="hint">Fill both boxes on a row for it to show. Leave a row blank to remove it.</p>
    </div>

    <div class="field">
      <label for="el">Emergency line</label>
      <input type="text" id="el" name="emergency_line" value="<?= e($site['emergency_line'] ?? '') ?>" maxlength="200">
      <p class="hint">The sentence in the green band at the bottom of every page.</p>
    </div>

    <button class="btn btn--go" type="submit">Save contact details</button>
  </form>
</div>

<div class="card">
  <h2>Website address &amp; tracking</h2>
  <p class="card__hint">Your web person normally fills these in once. There is no harm in leaving them empty.</p>

  <form method="post">
    <?= bww_csrf_field() ?>
    <input type="hidden" name="action" value="tracking">

    <div class="field">
      <label for="su">Website address</label>
      <input type="url" id="su" name="site_url" value="<?= e($site['site_url'] ?? '') ?>" placeholder="https://www.freelandwellandwater.com">
      <p class="hint">Used by Google to know the site's proper address. Set this to the real address at go-live.</p>
    </div>

    <div class="row">
      <div class="field">
        <label for="ga">Google Analytics ID</label>
        <input type="text" id="ga" name="ga4_id" value="<?= e($site['ga4_id'] ?? '') ?>" placeholder="G-XXXXXXXXXX">
      </div>
      <div class="field">
        <label for="px">Facebook Pixel ID</label>
        <input type="text" id="px" name="meta_pixel_id" value="<?= e($site['meta_pixel_id'] ?? '') ?>" placeholder="1234567890">
      </div>
    </div>

    <div class="field">
      <label for="gsc">Google Search Console verification code</label>
      <input type="text" id="gsc" name="gsc_verify" value="<?= e($site['gsc_verify'] ?? '') ?>">
      <p class="hint">The long code Google gives you when verifying ownership by "HTML tag".</p>
    </div>

    <button class="btn btn--go" type="submit">Save settings</button>
  </form>
</div>

<div class="card" id="password">
  <h2>Your login</h2>
  <p class="card__hint">Change your password here. Three unrelated words with a number is easy to remember and hard to guess.</p>

  <form method="post" autocomplete="off">
    <?= bww_csrf_field() ?>
    <input type="hidden" name="action" value="password">

    <div class="field">
      <label for="un">Username</label>
      <input type="text" id="un" name="username" value="<?= e(bww_auth()['user'] ?? '') ?>" autocomplete="username">
    </div>
    <div class="field">
      <label for="cp">Current password</label>
      <input type="password" id="cp" name="current" required autocomplete="current-password">
    </div>
    <div class="row">
      <div class="field">
        <label for="np">New password</label>
        <input type="password" id="np" name="new" required minlength="8" autocomplete="new-password">
      </div>
      <div class="field">
        <label for="cf">Repeat new password</label>
        <input type="password" id="cf" name="confirm" required minlength="8" autocomplete="new-password">
      </div>
    </div>

    <button class="btn btn--primary" type="submit">Change password</button>
  </form>
</div>

<?php require __DIR__ . '/includes/layout-end.php'; ?>
