<?php
require_once __DIR__ . '/includes/data.php';
require_once __DIR__ . '/includes/mailer.php';

session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));

$L      = c('contact_page.labels', []);
$sent   = false;
$errors = [];
$old = ['name' => '', 'email' => '', 'phone' => '', 'service' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && shown('contact_page.show_form')) {
    foreach ($old as $k => $_) $old[$k] = trim($_POST[$k] ?? '');

    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        $errors[] = 'Your session expired. Please try sending the message again.';
    }
    if (!empty($_POST['website'])) $errors[] = 'Your message could not be sent.';
    if ($old['name'] === '')    $errors[] = 'Please tell us your name.';
    if ($old['message'] === '') $errors[] = 'Please tell us what you need help with.';
    if ($old['email'] === '' && $old['phone'] === '') {
        $errors[] = 'Please give us either an email address or a phone number so we can reply.';
    }
    if ($old['email'] !== '' && !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'That email address does not look right — please check it.';
    }
    if (strlen($old['message']) > 5000) $errors[] = 'That message is too long. Please shorten it a little.';

    if (!$errors) {
        $serviceName = $old['service'] ? bww_service_name($old['service']) : 'Not specified';
        $body = "New enquiry from the website\r\n" . str_repeat('-', 46) . "\r\n\r\n"
              . "Name:     {$old['name']}\r\n"
              . "Email:    " . ($old['email'] ?: '—') . "\r\n"
              . "Phone:    " . ($old['phone'] ?: '—') . "\r\n"
              . "Service:  {$serviceName}\r\n"
              . "Sent:     " . date('D, j M Y, H:i') . "\r\n\r\n"
              . "Message\r\n" . str_repeat('-', 46) . "\r\n"
              . $old['message'] . "\r\n\r\n" . str_repeat('-', 46) . "\r\n"
              . "Reply directly to this email to answer " . $old['name'] . ".\r\n";

        $res = bww_send_mail(
            s('form_recipient', s('email')),
            'Website enquiry — ' . $old['name'] . ($serviceName !== 'Not specified' ? ' (' . $serviceName . ')' : ''),
            $body, $old['email'], $old['name']
        );

        if ($res['ok']) {
            $sent = true;
            $old = array_map(fn() => '', $old);
            $_SESSION['csrf'] = bin2hex(random_bytes(16));
        } else {
            $errors[] = 'We could not send your message just now. Please call us on ' . s('phone_display') . ' instead. (' . $res['error'] . ')';
        }
    }
}

$PAGE  = 'contact';
$TITLE = c('contact_page.meta_title', 'Contact Us') . ' | ' . s('business');
$DESC  = c('contact_page.meta_desc');
require __DIR__ . '/includes/header.php';
$services = bww_services();
$hasForm  = shown('contact_page.show_form');
?>

<section class="page-hero">
  <div class="wrap">
    <p class="crumbs"><a href="/">Home</a> &nbsp;/&nbsp; <?= e(c('contact_page.hero.heading')) ?></p>
    <?php if (c('contact_page.hero.eyebrow')): ?><span class="eyebrow"><?= e(c('contact_page.hero.eyebrow')) ?></span><?php endif; ?>
    <h1><?= e(c('contact_page.hero.heading')) ?></h1>
    <?php if (c('contact_page.hero.text')): ?><p><?= e(c('contact_page.hero.text')) ?></p><?php endif; ?>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="contact-grid<?= $hasForm ? '' : ' contact-grid--single' ?>">

      <div>
        <h2><?= e(c('contact_page.details_heading', 'Contact details')) ?></h2>
        <ul class="info-list">
          <li>
            <span class="ico"><?= icon('phone') ?></span>
            <div><strong>Phone</strong><a href="tel:<?= e(s('phone_link')) ?>"><?= e(s('phone_display')) ?></a></div>
          </li>
          <?php if (s('email')): ?>
          <li>
            <span class="ico"><?= icon('mail') ?></span>
            <div><strong>Email</strong><a href="mailto:<?= e(s('email')) ?>"><?= e(s('email')) ?></a></div>
          </li>
          <?php endif; ?>
          <?php if (s('areas')): ?>
          <li>
            <span class="ico"><?= icon('pin') ?></span>
            <div><strong>Service area</strong><span><?= e(bww_list(s('areas', []))) ?>, Maryland</span></div>
          </li>
          <?php endif; ?>
        </ul>

        <?php if (s('hours')): ?>
        <h3 style="margin-top:2.4rem"><?= e(c('contact_page.hours_heading', 'Opening hours')) ?></h3>
        <table class="hours-table">
          <?php foreach (s('hours', []) as $h): ?>
          <tr><th><?= e($h['days']) ?></th><td><?= e($h['time']) ?></td></tr>
          <?php endforeach; ?>
        </table>
        <?php endif; ?>

        <?php if (s('emergency_line')): ?>
        <div class="alert alert--ok" style="margin-top:1.6rem">
          <strong><?= e(c('contact_page.emergency_label', 'Emergencies, any hour.')) ?></strong> <?= e(s('emergency_line')) ?>
        </div>
        <?php endif; ?>

        <div class="btn-row">
          <a class="btn btn--call btn--block" href="tel:<?= e(s('phone_link')) ?>"><?= icon('phone') ?> Call <?= e(s('phone_display')) ?></a>
        </div>
      </div>

      <?php if ($hasForm): ?>
      <div class="form-card">
        <h2><?= e(c('contact_page.form_heading', 'Send a message')) ?></h2>
        <?php if (c('contact_page.form_intro')): ?><p><?= e(c('contact_page.form_intro')) ?></p><?php endif; ?>

        <?php if ($sent): ?>
          <div class="alert alert--ok"><strong><?= e(c('contact_page.form_success')) ?></strong></div>
        <?php endif; ?>

        <?php if ($errors): ?>
          <div class="alert alert--err">
            <?php foreach ($errors as $i => $err): ?><?= $i ? '<br>' : '' ?><?= e($err) ?><?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form method="post" action="/contact#form" id="form" data-validate novalidate>
          <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
          <div class="hp" aria-hidden="true">
            <label>Do not fill this in <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
          </div>

          <div class="field">
            <label for="f-name"><?= e($L['name'] ?? 'Your name') ?> <span class="req">*</span></label>
            <input type="text" id="f-name" name="name" value="<?= e($old['name']) ?>" required autocomplete="name">
          </div>

          <div class="field">
            <label for="f-phone"><?= e($L['phone'] ?? 'Phone number') ?></label>
            <input type="tel" id="f-phone" name="phone" value="<?= e($old['phone']) ?>" autocomplete="tel">
          </div>

          <div class="field">
            <label for="f-email"><?= e($L['email'] ?? 'Email address') ?></label>
            <input type="email" id="f-email" name="email" value="<?= e($old['email']) ?>" autocomplete="email">
            <p class="field-note">Give us at least one of phone or email so we can reply.</p>
          </div>

          <?php if ($services): ?>
          <div class="field">
            <label for="f-service"><?= e($L['service'] ?? 'What do you need help with?') ?></label>
            <select id="f-service" name="service">
              <option value="">Not sure / something else</option>
              <?php foreach ($services as $sv): ?>
                <option value="<?= e($sv['id']) ?>"<?= $old['service'] === $sv['id'] ? ' selected' : '' ?>><?= e($sv['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php endif; ?>

          <div class="field">
            <label for="f-message"><?= e($L['message'] ?? 'Tell us what is happening') ?> <span class="req">*</span></label>
            <textarea id="f-message" name="message" required placeholder="<?= e(c('contact_page.message_placeholder')) ?>"><?= e($old['message']) ?></textarea>
          </div>

          <button class="btn btn--primary btn--block" type="submit"><?= e($L['submit'] ?? 'Send message') ?></button>
          <?php if (c('contact_page.form_note')): ?><p class="form-note"><?= e(c('contact_page.form_note')) ?></p><?php endif; ?>
        </form>
      </div>
      <?php endif; ?>

    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
