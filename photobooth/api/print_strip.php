<?php
/**
 * api/print_strip.php
 * POST { session_id }
 * -> { success: true, print_result: {...} }
 * Admin-only. Lets staff reprint any past session's strip on demand,
 * regardless of the auto_print setting.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
admin_require_api();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('POST required', 405);
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$sessionId = sanitize_id($body['session_id'] ?? '');

$meta = SessionManager::getMetadata($sessionId);
if (empty($meta['strip'])) {
    json_error('This session has no generated strip to print', 404);
}

if (empty($meta['strip_a5'])) {
    try {
        $meta['strip_a5'] = Compositor::generateA5SideBySide($sessionId, $meta['strip']);
        SessionManager::saveMetadata($sessionId, $meta);
    } catch (Exception $e) {
        json_error('Failed to generate A5 side-by-side strip: ' . $e->getMessage(), 500);
    }
}

$absoluteStripPath = SessionManager::dir($sessionId) . '/' . $meta['strip_a5'];
$result = PrintManager::printFile($absoluteStripPath, 'A5');
SessionManager::recordPrintStatus($sessionId, $result['status'], $result);

json_response(['success' => true, 'print_result' => $result]);
