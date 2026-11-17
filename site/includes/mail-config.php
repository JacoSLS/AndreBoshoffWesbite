<?php
/**
 * Outgoing mail settings for the contact form.
 * ---------------------------------------------------------------------------
 * With smtp_enabled false the site uses PHP's built-in mail(), sending from a
 * real mailbox on the site's own domain. That works well on Namecheap.
 *
 * IMPORTANT: the "From" address should be a mailbox on the domain the site is
 * hosted under. The visitor's address goes in Reply-To, never in From — putting
 * it in From is the single most common reason contact forms end up in spam.
 *
 * NEVER put the mailbox password in this file. To switch SMTP on, create
 * includes/mail-local.php on the server (it is not part of the website zip,
 * so updates never overwrite it) containing:
 *
 *     <?php return ['smtp_enabled' => true, 'password' => 'the mailbox password'];
 *
 * Anything in mail-local.php replaces the matching setting below.
 * ---------------------------------------------------------------------------
 */

$cfg = [
    'smtp_enabled' => false,

    // SMTP server details (cPanel > Email Accounts > Connect Devices).
    'host'      => 'mail.freelandwellandwater.com',
    'port'      => 465,           // 465 for SSL, 587 for STARTTLS
    'secure'    => 'ssl',         // 'ssl' | 'tls' (a login is never sent unencrypted)
    'username'  => 'andre@freelandwellandwater.com',
    'password'  => '',            // goes in mail-local.php, never here

    // Envelope. From must be a mailbox that exists on the sending domain.
    'from_email' => 'andre@freelandwellandwater.com',
    'from_name'  => 'Freeland Well and Water Services Website',

    // Optional: copy every enquiry to a second address (e.g. your own).
    'bcc' => '',

    'timeout' => 15,
];

$local = __DIR__ . '/mail-local.php';
if (is_file($local)) {
    $over = require $local;
    if (is_array($over)) $cfg = array_merge($cfg, $over);
}
return $cfg;
