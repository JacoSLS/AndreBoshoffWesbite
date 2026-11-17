<?php
/**
 * Outgoing mail settings for the contact form.
 * ---------------------------------------------------------------------------
 * Leave BWW_SMTP_ENABLED as false to use PHP's built-in mail() function.
 * That works on most hosts, but messages sent to Gmail addresses from a server
 * whose domain does not match the "From" address are frequently filtered as
 * spam. Setting up SMTP below is the reliable option and takes two minutes.
 *
 * IMPORTANT: the "From" address should be a mailbox on the domain the site is
 * hosted under. The visitor's address goes in Reply-To, never in From — putting
 * it in From is the single most common reason contact forms end up in spam.
 * ---------------------------------------------------------------------------
 */

return [
    // Switch to true once the SMTP details below are filled in.
    'smtp_enabled' => false,

    // SMTP server details — get these from your hosting control panel.
    'host'      => 'mail.yourdomain.com',
    'port'      => 587,           // 587 for STARTTLS, 465 for SSL
    'secure'    => 'tls',         // 'tls' | 'ssl' | ''
    'username'  => 'website@yourdomain.com',
    'password'  => '',            // set this on the server, never commit it

    // Envelope. From must be a mailbox that exists on the sending domain.
    'from_email' => 'website@yourdomain.com',
    'from_name'  => 'Freeland Well and Water Services Website',

    // Optional: copy every enquiry to a second address (e.g. your own).
    'bcc' => '',

    'timeout' => 15,
];
