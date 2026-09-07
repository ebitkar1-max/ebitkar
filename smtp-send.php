<?php
/**
 * عميل SMTP مبسّط — يكفي لإرسال رسائل نموذج التواصل.
 * لا يحتاج أي مكتبات خارجية.
 */

declare(strict_types=1);

/**
 * @throws RuntimeException عند أي فشل في الاتصال أو المصادقة أو الإرسال
 */
function smtp_send(
    array $cfg,
    string $to,
    string $subject,
    string $body,
    string $replyTo = '',
    string $html = ''
): void {
    $host    = $cfg['host'] ?? 'smtp.hostinger.com';
    $port    = (int)($cfg['port'] ?? 465);
    $user    = $cfg['user'] ?? '';
    $pass    = $cfg['pass'] ?? '';
    $secure  = $cfg['secure'] ?? 'ssl';          // ssl (465) أو tls (587)
    $timeout = (int)($cfg['timeout'] ?? 15);

    if ($user === '' || $pass === '') {
        throw new RuntimeException('smtp_not_configured');
    }

    $dsn = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;

    $errNo = 0;
    $errStr = '';
    $fp = @stream_socket_client($dsn, $errNo, $errStr, $timeout);
    if (!$fp) {
        throw new RuntimeException('connect_failed: ' . $errStr);
    }
    stream_set_timeout($fp, $timeout);

    /* --- قراءة رد الخادم مع دعم الأسطر المتعددة (250-…) --- */
    $read = function () use ($fp): array {
        $lines = [];
        while (($line = fgets($fp, 1024)) !== false) {
            $lines[] = rtrim($line, "\r\n");
            // آخر سطر يكون بالشكل "250 " بمسافة بدل الشرطة
            if (strlen($line) < 4 || $line[3] !== '-') {
                break;
            }
        }
        $last = end($lines) ?: '';
        return [(int)substr($last, 0, 3), implode("\n", $lines)];
    };

    $cmd = function (string $line, array $expect) use ($fp, $read): void {
        fwrite($fp, $line . "\r\n");
        [$code, $text] = $read();
        if (!in_array($code, $expect, true)) {
            throw new RuntimeException('smtp_' . $code . ': ' . substr($text, 0, 120));
        }
    };

    try {
        [$code] = $read();                       // ترحيب 220
        if ($code !== 220) {
            throw new RuntimeException('no_greeting');
        }

        $ehloName = $cfg['ehlo'] ?? 'ebitkar.com';
        $cmd('EHLO ' . $ehloName, [250]);

        if ($secure === 'tls') {
            $cmd('STARTTLS', [220]);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('starttls_failed');
            }
            $cmd('EHLO ' . $ehloName, [250]);
        }

        $cmd('AUTH LOGIN', [334]);
        $cmd(base64_encode($user), [334]);
        $cmd(base64_encode($pass), [235]);

        $cmd('MAIL FROM:<' . $user . '>', [250]);
        $cmd('RCPT TO:<' . $to . '>', [250, 251]);
        $cmd('DATA', [354]);

        $fromName = '=?UTF-8?B?' . base64_encode('موقع ابتكار') . '?=';

        $headers =
            'From: ' . $fromName . ' <' . $user . ">\r\n" .
            'To: ' . $to . "\r\n" .
            ($replyTo !== '' ? 'Reply-To: ' . $replyTo . "\r\n" : '') .
            'Subject: =?UTF-8?B?' . base64_encode($subject) . "?=\r\n" .
            'Date: ' . date('r') . "\r\n" .
            'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . ($cfg['ehlo'] ?? 'ebitkar.com') . ">\r\n" .
            "MIME-Version: 1.0\r\n";

        $b64 = fn(string $s): string => rtrim(chunk_split(base64_encode($s), 76, "\r\n"), "\r\n");

        if ($html !== '') {
            // نسختان: نص عادي لمن لا يعرض HTML، وHTML منسّق لبقية العملاء
            $boundary = 'ebk_' . bin2hex(random_bytes(10));

            $headers .= 'Content-Type: multipart/alternative; boundary="' . $boundary . "\"\r\n";

            $payload =
                "--{$boundary}\r\n" .
                "Content-Type: text/plain; charset=\"UTF-8\"\r\n" .
                "Content-Transfer-Encoding: base64\r\n\r\n" .
                $b64($body) . "\r\n\r\n" .
                "--{$boundary}\r\n" .
                "Content-Type: text/html; charset=\"UTF-8\"\r\n" .
                "Content-Transfer-Encoding: base64\r\n\r\n" .
                $b64($html) . "\r\n\r\n" .
                "--{$boundary}--\r\n";
        } else {
            $headers .=
                "Content-Type: text/plain; charset=\"UTF-8\"\r\n" .
                "Content-Transfer-Encoding: base64\r\n";
            $payload = $b64($body) . "\r\n";
        }

        fwrite($fp, $headers . "\r\n" . $payload . ".\r\n");
        [$code, $text] = $read();
        if ($code !== 250) {
            throw new RuntimeException('data_rejected_' . $code . ': ' . substr($text, 0, 120));
        }

        @fwrite($fp, "QUIT\r\n");
    } finally {
        @fclose($fp);
    }
}
