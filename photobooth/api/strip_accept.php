<?php
/**
 * api/strip_accept.php
 * POST { session_id }
 * -> { success: true, printed: {...} }
 *
 * Called the moment the user taps "Accept" on the final preview.
 * If printing.auto_print is enabled in Settings, this immediately
 * sends the strip to the configured printer via PrintManager and
 * records the outcome on the session's metadata.json.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('POST required', 405);
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$sessionId = sanitize_id($body['session_id'] ?? '');

if ($sessionId === '' || !is_dir(SessionManager::dir($sessionId))) {
    json_error('Unknown session', 404);
}

$meta = SessionManager::getMetadata($sessionId);
if (empty($meta['strip'])) {
    json_error('Strip has not been generated for this session yet', 400);
}

$meta['accepted_at'] = date('c');
SessionManager::saveMetadata($sessionId, $meta);

$printResult = ['status' => 'skipped', 'reason' => 'Auto print disabled in Settings'];
if (Settings::get('printing.auto_print', false)) {
    $absoluteStripPath = SessionManager::dir($sessionId) . '/' . $meta['strip'];
    $printResult = PrintManager::printFile($absoluteStripPath);
}

SessionManager::recordPrintStatus($sessionId, $printResult['status'], $printResult);

json_response([
    'success' => true,
    'print_result' => $printResult,
]);
