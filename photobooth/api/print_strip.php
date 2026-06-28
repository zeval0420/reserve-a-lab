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

$absoluteStripPath = SessionManager::dir($sessionId) . '/' . $meta['strip'];
$result = PrintManager::printFile($absoluteStripPath);
SessionManager::recordPrintStatus($sessionId, $result['status'], $result);

json_response(['success' => true, 'print_result' => $result]);
