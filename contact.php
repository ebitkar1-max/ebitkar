<?php
/**
 * ابتكار للحلول الذكية — مستقبِل نموذج التواصل
 * يستقبل POST من النموذج ويرسل رسالة إلى صندوق الشركة.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

mb_internal_encoding('UTF-8');

const MAILBOX   = 'info@ebitkar.com';   // الوجهة
const SENDER    = 'info@ebitkar.com';   // لا بد أن يكون على نفس النطاق وإلا رفضته الخوادم
const MAX_LEN   = 4000;
const THROTTLE  = 20;                   // ثانية بين رسالتين من نفس الزائر

function fail(string $msg, int $code = 400): never {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

function clean(string $v, int $max = 300): string {
    // إزالة أي أسطر جديدة تمنع حقن ترويسات البريد
    $v = str_replace(["\r", "\n", "%0a", "%0d"], ' ', $v);
    return mb_substr(trim($v), 0, $max);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    fail('Method not allowed', 405);
}

/* ── فخّ العناكب: حقل مخفي لا يملؤه إلا الروبوت ───────────── */
if (!empty($_POST['website'])) {
    // نُظهر نجاحاً حتى لا يعرف الروبوت أنه اكتُشف
    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ── حدّ زمني بسيط لكل زائر ────────────────────────────────── */
session_start();
$now = time();
if (isset($_SESSION['last_sent']) && ($now - $_SESSION['last_sent']) < THROTTLE) {
    fail('too_fast', 429);
}

/* ── التحقق من المدخلات ────────────────────────────────────── */
$name    = clean((string)($_POST['name']    ?? ''), 120);
$email   = clean((string)($_POST['email']   ?? ''), 180);
$phone   = clean((string)($_POST['phone']   ?? ''), 60);
$service = clean((string)($_POST['service'] ?? ''), 120);
$message = mb_substr(trim((string)($_POST['message'] ?? '')), 0, MAX_LEN);

if ($name === '' || $message === '') {
    fail('missing_fields');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fail('bad_email');
}

/* ── تركيب الرسالة ─────────────────────────────────────────── */
$subject = 'طلب جديد من الموقع — ' . ($service !== '' ? $service : 'غير محدد');

$when = date('Y-m-d H:i');
$ip   = $_SERVER['REMOTE_ADDR'] ?? '—';
$dash = '--------------------------------------';

/* النسخة النصية (لعملاء البريد التي لا تعرض HTML) */
$body = "وصلك طلب جديد من نموذج التواصل في ebitkar.com\n"
      . $dash . "\n\n"
      . "الاسم:    {$name}\n"
      . "البريد:   {$email}\n"
      . "الهاتف:   " . ($phone !== '' ? $phone : '—') . "\n"
      . "الخدمة:   " . ($service !== '' ? $service : '—') . "\n\n"
      . "التفاصيل:\n{$message}\n\n"
      . $dash . "\n"
      . "التاريخ:  {$when}\n"
      . "IP:       {$ip}\n";

/* النسخة المنسّقة */
$e = fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

$row = function (string $label, string $value, bool $ltr = false) use ($e): string {
    $dir = $ltr ? ' dir="ltr"' : '';
    return '<tr>'
        . '<td style="padding:11px 0;border-bottom:1px solid #eee;color:#6c6c6c;font-size:13px;width:110px;vertical-align:top">'
        . $e($label) . '</td>'
        . '<td style="padding:11px 0;border-bottom:1px solid #eee;color:#222;font-size:15px;font-weight:600"' . $dir . '>'
        . ($value !== '' ? $e($value) : '—') . '</td>'
        . '</tr>';
};

$html = '<!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8">'
      . '<meta name="viewport" content="width=device-width,initial-scale=1"></head>'
      . '<body style="margin:0;padding:24px;background:#f4f4f4;'
      . 'font-family:Tahoma,\'Segoe UI\',Arial,sans-serif;direction:rtl">'
      . '<div style="max-width:580px;margin:0 auto;background:#fff;border-top:5px solid #f6850d">'
      . '<div style="padding:26px 30px 8px">'
      . '<div style="font-size:12px;letter-spacing:2px;color:#f6850d;font-weight:700">طلب جديد</div>'
      . '<h1 style="margin:6px 0 4px;font-size:21px;color:#222">' . $e($service !== '' ? $service : 'استفسار عام') . '</h1>'
      . '<div style="font-size:12px;color:#999">من نموذج التواصل في ebitkar.com</div>'
      . '</div>'
      . '<div style="padding:8px 30px 4px">'
      . '<table style="width:100%;border-collapse:collapse">'
      . $row('الاسم', $name)
      . $row('البريد', $email, true)
      . $row('الهاتف', $phone, true)
      . $row('الخدمة', $service)
      . '</table></div>'
      . '<div style="padding:18px 30px 4px">'
      . '<div style="font-size:13px;color:#6c6c6c;margin-bottom:8px">التفاصيل</div>'
      . '<div style="background:#f9f9f9;border-inline-start:4px solid #f6850d;padding:15px 18px;'
      . 'font-size:15px;line-height:1.85;color:#333;white-space:pre-wrap">'
      . $e($message) . '</div></div>'
      . '<div style="padding:20px 30px 26px">'
      . '<a href="mailto:' . $e($email) . '" style="display:inline-block;background:#f6850d;color:#fff;'
      . 'text-decoration:none;padding:12px 26px;border-radius:30px;font-size:14px;font-weight:700">'
      . 'الرد على ' . $e($name) . '</a></div>'
      . '<div dir="ltr" style="padding:14px 30px;background:#f9f9f9;border-top:1px solid #eee;'
      . 'font-size:11px;color:#999;text-align:right">' . $e($when) . ' &nbsp;·&nbsp; IP ' . $e($ip) . '</div>'
      . '</div></body></html>';

$headers = [
    'From'                      => SENDER,
    'Reply-To'                  => $email,          // الرد يذهب للزائر مباشرة
    'MIME-Version'              => '1.0',
    'Content-Type'              => 'text/plain; charset=UTF-8',
    'Content-Transfer-Encoding' => '8bit',
    'X-Mailer'                  => 'ebitkar-site',
];

$headerLines = '';
foreach ($headers as $k => $v) {
    $headerLines .= "{$k}: {$v}\r\n";
}

$encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

/* ── الإرسال ────────────────────────────────────────────────
   هوستنجر يرفض mail() على هذه الاستضافة، فالمسار الأساسي هو SMTP.
   يبقى mail() كخطة بديلة لو نُقل الموقع لاستضافة أخرى تدعمه.       */
$sent = false;
$why  = '';

$configPath = __DIR__ . '/mail-config.php';

if (is_file($configPath)) {
    require_once __DIR__ . '/smtp-send.php';
    try {
        smtp_send((array)require $configPath, MAILBOX, $subject, $body, $email, $html);
        $sent = true;
    } catch (Throwable $err) {   // ‏$e مستخدم أعلاه لتهريب HTML
        $why = $err->getMessage();
    }
} else {
    $why = 'no_config';
}

if (!$sent) {
    $sent = @mail(MAILBOX, $encodedSubject, $body, $headerLines);
}

if (!$sent) {
    error_log('[ebitkar contact] send failed: ' . $why);
    fail('send_failed', 500);
}

$_SESSION['last_sent'] = $now;
echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
