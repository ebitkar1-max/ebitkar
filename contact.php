<?php
/**
 * ابتكار للحلول الذكية — مستقبِل نموذج التواصل
 * يستقبل POST من النموذج ويرسل رسالة إلى صندوق الشركة.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

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

$body = "وصلك طلب جديد من نموذج التواصل في ebitkar.com\n"
      . str_repeat('─', 40) . "\n\n"
      . "الاسم:    {$name}\n"
      . "البريد:   {$email}\n"
      . "الهاتف:   " . ($phone !== '' ? $phone : '—') . "\n"
      . "الخدمة:   " . ($service !== '' ? $service : '—') . "\n\n"
      . "التفاصيل:\n{$message}\n\n"
      . str_repeat('─', 40) . "\n"
      . 'التاريخ:  ' . date('Y-m-d H:i') . "\n"
      . 'IP:       ' . ($_SERVER['REMOTE_ADDR'] ?? '—') . "\n";

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

$sent = @mail(MAILBOX, $encodedSubject, $body, $headerLines, '-f' . SENDER);

if (!$sent) {
    fail('send_failed', 500);
}

$_SESSION['last_sent'] = $now;
echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
