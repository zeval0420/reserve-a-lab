<?php
/**
 * api/email_settings.php
 * GET  -> { success, email: {enabled, to[], subject, from_name, attach:{strip,print,raw}},
 *           smtp_user, problem }
 * POST { enabled, to[], subject, from_name, attach:{strip,print,raw} }
 *      -> { success, smtp_user, problem }
 *
 * Admin-only. Backs the "who gets the prints by email" panel shown in the
 * kiosk's settings modal (index.php) and on the admin Settings page. Only the
 * guest-facing mail options are editable here; the SMTP account itself stays
 * in settings.json / helperFiles and its password is never sent to the browser.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
admin_require_api();

const EMAIL_MAX_RECIPIENTS = 20;

/** Current email options in the shape the panel edits. */
function email_settings_view(): array
{
    $to = Settings::get('email.to', []);
    $to = is_array($to) ? $to : [$to];
    return [
        'enabled' => (bool)Settings::get('email.enabled', false),
        'to' => array_values(array_filter(array_map('trim', array_map('strval', $to)), 'strlen')),
        'subject' => (string)Settings::get('email.subject', 'Natibari Photobooth'),
        'from_name' => (string)Settings::get('email.from_name', 'Natibari Photobooth'),
        'attach' => [
            'strip' => (bool)Settings::get('email.attach.strip', true),
            'print' => (bool)Settings::get('email.attach.print', true),
            'raw' => (bool)Settings::get('email.attach.raw', true),
        ],
    ];
}

function email_settings_reply(?array $email = null): void
{
    json_response([
        'success' => true,
        'email' => $email ?? email_settings_view(),
        'smtp_user' => Mailer::account()['user'],
        'problem' => Mailer::problem(),
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    email_settings_reply();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('GET or POST required', 405);
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    json_error('Invalid request body', 400);
}

// Recipients: valid, de-duplicated (case-insensitively), capped.
$to = [];
$invalid = [];
foreach ((array)($body['to'] ?? []) as $addr) {
    $addr = trim((string)$addr);
    if ($addr === '') {
        continue;
    }
    if (!filter_var($addr, FILTER_VALIDATE_EMAIL)) {
        $invalid[] = $addr;
        continue;
    }
    $to[strtolower($addr)] = $addr;
}
if ($invalid) {
    json_error('Not a valid email address: ' . implode(', ', array_slice($invalid, 0, 3)), 400);
}
if (count($to) > EMAIL_MAX_RECIPIENTS) {
    json_error('At most ' . EMAIL_MAX_RECIPIENTS . ' recipients are allowed.', 400);
}

// Header-bound text: single line, bounded length, never empty.
$line = function ($value, int $max, string $fallback): string {
    $v = trim(preg_replace('/[\r\n\t]+/', ' ', (string)$value));
    return $v === '' ? $fallback : mb_substr($v, 0, $max);
};

$attach = (array)($body['attach'] ?? []);
$email = [
    'enabled' => !empty($body['enabled']),
    'to' => array_values($to),
    'subject' => $line($body['subject'] ?? '', 100, 'Natibari Photobooth'),
    'from_name' => $line($body['from_name'] ?? '', 60, 'Natibari Photobooth'),
    'attach' => [
        'strip' => !empty($attach['strip']),
        'print' => !empty($attach['print']),
        'raw' => !empty($attach['raw']),
    ],
];

$merged = array_merge_recursive_distinct(Settings::all(), ['email' => $email]);
if (!Settings::saveAll($merged)) {
    json_error('Could not write settings.json — check folder permissions', 500);
}

email_settings_reply($email);
