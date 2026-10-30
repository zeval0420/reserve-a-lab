<?php
// Release Materials / Cancel Request (admin only). Logic lives in helperFiles/material_release.php.
// Every response is JSON: {success: bool, message?: string, ...}.
include_once(__DIR__ . '/../../scilab/helperFiles/db_connection.php');
include_once(__DIR__ . '/../helperFiles/session_handler.php');
include_once(__DIR__ . '/../helperFiles/variableDeclarations.php');
require_once __DIR__ . '/../helperFiles/input.php';   // ral_display_name()
require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

// Admin-only endpoint: logged in with the admin role, and a valid CSRF token on POST
ral_require_role(['admin']);
ral_require_csrf();

header('Content-Type: application/json');

function release_json($data, $http = 200)
{
    http_response_code($http);
    echo json_encode($data);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    release_json(['success' => false, 'message' => 'POST required.'], 405);
}

$adminName = trim(($_SESSION['firstname'] ?? '') . ' ' . ($_SESSION['middlename'] ?? '') . ' ' . ($_SESSION['lastname'] ?? ''));
$adminName = trim(preg_replace('/\s+/', ' ', $adminName));
if ($adminName === '') {
    $adminName = (string)($_SESSION['username'] ?? 'admin');
}

$action = (string)($_POST['action'] ?? '');
$formID = isset($_POST['id']) ? intval($_POST['id']) : 0;

try {
    switch ($action) {
        case 'get_release_data':
            if ($formID <= 0) release_json(['success' => false, 'message' => 'Missing request.'], 400);
            $data = scilab_release_get_data($conn, $formID);
            if (!$data['ok']) release_json(['success' => false, 'message' => $data['message']], 404);
            $data['success'] = true;
            release_json($data);
            break;

        case 'lookup_receiver':
            $res = scilab_release_lookup_receiver($conn, $_POST['receiver_id'] ?? '', $formID);
            if (!$res['ok']) release_json(['success' => false, 'message' => $res['message']]);
            $res['success'] = true;
            release_json($res);
            break;

        case 'confirm_release':
            if ($formID <= 0) release_json(['success' => false, 'message' => 'Missing request.'], 400);
            $items = json_decode((string)($_POST['items'] ?? '[]'), true);
            if (!is_array($items)) release_json(['success' => false, 'message' => 'Invalid item list.'], 400);
            $res = scilab_release_confirm(
                $conn,
                $formID,
                $items,
                $_POST['receiver_id'] ?? '',
                (string)($_POST['scan_method'] ?? 'scanner'),
                $_POST['remarks'] ?? '',
                $adminName
            );
            if (!$res['ok']) release_json(['success' => false, 'message' => $res['message']]);
            // Low-stock alerts after the commit (an e-mail failure must not undo the release)
            foreach ($res['lowStockIDs'] as $invID) {
                try { checkInventoryThresholdAndNotify($conn, $invID); } catch (Throwable $e) { error_log('Threshold alert failed: ' . $e->getMessage()); }
            }
            release_json(['success' => true, 'message' => $res['message'], 'releaseID' => $res['releaseID'], 'receiver' => $res['receiver']]);
            break;

        case 'cancel_request':
            if ($formID <= 0) release_json(['success' => false, 'message' => 'Missing request.'], 400);
            $res = scilab_cancel_request($conn, $formID, $_POST['reason'] ?? '', $adminName);
            if (!$res['ok']) release_json(['success' => false, 'message' => $res['message']]);
            release_json(['success' => true, 'message' => $res['message'], 'restored' => $res['restored']]);
            break;

        default:
            release_json(['success' => false, 'message' => 'Unknown action.'], 400);
    }
} catch (Throwable $e) {
    error_log('ajax_release.php: ' . $e->getMessage());
    release_json(['success' => false, 'message' => 'Something went wrong. Nothing was changed.'], 500);
}
