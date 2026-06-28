<?php
/**
 * api/settings_save.php
 * POST { settings: {...partial or full settings tree...}, new_passcode?: "1234" }
 * -> { success: true }
 *
 * Persists to config/settings.json. The admin passcode hash is handled
 * separately via the optional `new_passcode` field so the plaintext/hash
 * never needs to round-trip through the browser.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
admin_require_api();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('POST required', 405);
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$incoming = $body['settings'] ?? [];
if (!is_array($incoming)) {
    json_error('settings must be an object', 400);
}

// Merge onto the CURRENT full settings (which still holds the real
// passcode hash, since settings_get.php strips it before sending out).
$current = Settings::all();
$merged = array_merge_recursive_distinct($current, $incoming);

if (!empty($body['new_passcode'])) {
    $merged['admin']['passcode_hash'] = hash('sha256', (string)$body['new_passcode']);
}

$ok = Settings::saveAll($merged);
if (!$ok) {
    json_error('Could not write settings.json — check folder permissions', 500);
}

json_response(['success' => true]);
