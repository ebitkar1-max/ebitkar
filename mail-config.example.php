<?php
/**
 * ── قالب إعدادات البريد ──────────────────────────────────────
 *
 * انسخ هذا الملف باسم  mail-config.php  في نفس المجلد
 * ثم ضع كلمة سر صندوق info@ebitkar.com مكان PUT-PASSWORD-HERE
 *
 * ملف mail-config.php مستثنى من Git فلن يُرفع إلى GitHub أبداً.
 */

return [
    'host'   => 'smtp.hostinger.com',
    'port'   => 465,
    'secure' => 'ssl',              // 465 = ssl   |   587 = tls
    'user'   => 'info@ebitkar.com', // نفس عنوان الصندوق
    'pass'   => 'PUT-PASSWORD-HERE',
    'ehlo'   => 'ebitkar.com',
];
