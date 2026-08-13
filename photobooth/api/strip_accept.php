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

// Generate the A5 side-by-side strip
try {
    $relativeA5StripPath = Compositor::generateA5SideBySide($sessionId, $meta['strip']);
    $meta['strip_a5'] = $relativeA5StripPath;
} catch (Exception $e) {
    json_error('Failed to generate A5 side-by-side strip: ' . $e->getMessage(), 500);
}

SessionManager::saveMetadata($sessionId, $meta);

$absoluteStripPath = SessionManager::dir($sessionId) . '/' . $meta['strip_a5'];
$printResult = PrintManager::printFile($absoluteStripPath, 'A5');

SessionManager::recordPrintStatus($sessionId, $printResult['status'], $printResult);

json_response([
    'success' => true,
    'print_result' => $printResult,
]);
