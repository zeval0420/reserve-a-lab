<?php
/**
 * api/set_printer.php
 * POST { printer_name }
 * -> { success: true }
 */
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('POST required', 405);
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$printerName = trim($body["Brother DCP-T830DW Printer"] ?? '');

$current = Settings::all();
if (!isset($current['printing'])) {
    $current['printing'] = [];
}
$current['printing']['printer_name'] = $printerName;

$ok = Settings::saveAll($current);
if (!$ok) {
    json_error('Could not save printer setting', 500);
}

json_response(['success' => true]);
