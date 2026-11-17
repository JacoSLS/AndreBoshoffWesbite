<?php
/**
 * Minimal SMTP sender with a mail() fallback.
 * Deliberately dependency-free — no Composer, no PHPMailer, nothing to install.
 */

function bww_mail_config(): array {
    static $c = null;
    if ($c === null) $c = require __DIR__ . '/mail-config.php';
    return $c;
}

/**
 * Send a plain-text email.
 * @return array{ok: bool, error: string}
 */
function bww_send_mail(string $to, string $subject, string $body, string $replyTo = '', string $replyName = ''): array {
    $cfg = bww_mail_config();

    $fromEmail = $cfg['from_email'] ?: ('no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $fromName  = $cfg['from_name'] ?: 'Website';

    // Header injection guard — newlines in any header value are fatal.
    foreach ([$to, $subject, $replyTo, $replyName, $fromEmail, $fromName] as $v) {
        if (preg_match('/[\r\n]/', (string)$v)) {
            return ['ok' => false, 'error' => 'Invalid characters in message headers.'];
        }
    }

    $headers = [
        'From: ' . bww_header_addr($fromName, $fromEmail),
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'X-Mailer: BWW-Site',
        'Date: ' . date('r'),
    ];
    if ($replyTo) $headers[] = 'Reply-To: ' . bww_header_addr($replyName, $replyTo);
    if (!empty($cfg['bcc'])) $headers[] = 'Bcc: ' . $cfg['bcc'];

    if (empty($cfg['smtp_enabled'])) {
        $ok = @mail($to, bww_encode_subject($subject), $body, implode("\r\n", $headers), '-f' . $fromEmail);
        return ['ok' => (bool)$ok, 'error' => $ok ? '' : 'The mail server refused the message.'];
    }

    return bww_smtp_send($cfg, $to, $subject, $body, $headers, $fromEmail);
}

function bww_header_addr(string $name, string $email): string {
    $name = trim($name);
    if ($name === '') return $email;
    return '=?UTF-8?B?' . base64_encode($name) . '?= <' . $email . '>';
}

function bww_encode_subject(string $subject): string {
    return preg_match('/[\x80-\xFF]/', $subject)
        ? '=?UTF-8?B?' . base64_encode($subject) . '?='
        : $subject;
}

/** Speak just enough SMTP to deliver one message. */
function bww_smtp_send(array $cfg, string $to, string $subject, string $body, array $headers, string $fromEmail): array {
    $host    = $cfg['host'];
    $port    = (int)$cfg['port'];
    $secure  = strtolower((string)$cfg['secure']);
    $timeout = (int)($cfg['timeout'] ?? 15);

    $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
    $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
    $fp = @stream_socket_client($remote, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) return ['ok' => false, 'error' => 'Could not reach the mail server (' . $errstr . ').'];
    stream_set_timeout($fp, $timeout);

    $read = function () use ($fp) {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) {
            $data .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') break;
        }
        return $data;
    };
    $cmd = function (string $c) use ($fp, $read) {
        fwrite($fp, $c . "\r\n");
        return $read();
    };
    $code = fn(string $r) => (int)substr(trim($r), 0, 3);

    $fail = function (string $msg) use ($fp) {
        @fclose($fp);
        return ['ok' => false, 'error' => $msg];
    };

    if ($code($read()) !== 220) return $fail('The mail server did not greet us.');

    $ehloHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if ($code($cmd('EHLO ' . $ehloHost)) !== 250) return $fail('EHLO was rejected.');

    if ($secure === 'tls') {
        if ($code($cmd('STARTTLS')) !== 220) return $fail('The server refused to start encryption.');
        if (!@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            return $fail('Encryption could not be established.');
        }
        if ($code($cmd('EHLO ' . $ehloHost)) !== 250) return $fail('EHLO after STARTTLS was rejected.');
    }

    if (!empty($cfg['username'])) {
        if ($code($cmd('AUTH LOGIN')) !== 334) return $fail('The server did not offer AUTH LOGIN.');
        if ($code($cmd(base64_encode($cfg['username']))) !== 334) return $fail('The username was rejected.');
        if ($code($cmd(base64_encode($cfg['password']))) !== 235) return $fail('The mailbox password was rejected.');
    }

    if ($code($cmd('MAIL FROM:<' . $fromEmail . '>')) !== 250) return $fail('The sender address was rejected.');

    $rcpts = [$to];
    if (!empty($cfg['bcc'])) $rcpts[] = trim($cfg['bcc']);
    foreach ($rcpts as $r) {
        if (!in_array($code($cmd('RCPT TO:<' . $r . '>')), [250, 251], true)) {
            return $fail('The recipient address ' . $r . ' was rejected.');
        }
    }

    if ($code($cmd('DATA')) !== 354) return $fail('The server would not accept message data.');

    // Bcc must not appear in the transmitted headers.
    $headers = array_values(array_filter($headers, fn($h) => stripos($h, 'Bcc:') !== 0));
    $msg = implode("\r\n", $headers) . "\r\n"
         . 'To: ' . $to . "\r\n"
         . 'Subject: ' . bww_encode_subject($subject) . "\r\n\r\n"
         . preg_replace('/^\./m', '..', str_replace(["\r\n", "\r", "\n"], "\r\n", $body));

    fwrite($fp, $msg . "\r\n.\r\n");
    if ($code($read()) !== 250) return $fail('The server did not accept the message.');

    $cmd('QUIT');
    @fclose($fp);
    return ['ok' => true, 'error' => ''];
}
