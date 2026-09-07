<?php
/* تشخيص مؤقت — يُحذف فور انتهاء الفحص */
if (($_GET['k'] ?? '') !== 'q7x2vn9') { http_response_code(404); exit; }
header('Content-Type: application/json; charset=utf-8');

$disabled = array_map('trim', explode(',', (string)ini_get('disable_functions')));

echo json_encode([
    'php'              => PHP_VERSION,
    'mail_exists'      => function_exists('mail'),
    'mail_disabled'    => in_array('mail', $disabled, true),
    'disable_functions'=> ini_get('disable_functions'),
    'sendmail_path'    => ini_get('sendmail_path'),
    'SMTP'             => ini_get('SMTP'),
    'smtp_port'        => ini_get('smtp_port'),
    'openssl'          => extension_loaded('openssl'),
    'sockets_ok'       => function_exists('stream_socket_client'),
    'smtp_reachable'   => (function () {
        $e = null; $en = null;
        $fp = @stream_socket_client('ssl://smtp.hostinger.com:465', $en, $e, 6);
        if (!$fp) return 'no: ' . $e;
        $greet = fgets($fp, 512);
        fclose($fp);
        return 'yes: ' . trim((string)$greet);
    })(),
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
