<?php
include('../helperFiles/db_connection.php');
include('../helperFiles/session_handler.php');

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$requestId = $data['request_id'] ?? null;
$action = $data['action'] ?? null;

if (!$requestId || !$action) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit();
}

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'teacher')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$newStatus = '';
if ($action === 'approve') {
    $newStatus = 'approved';
} elseif ($action === 'reject') {
    $newStatus = 'rejected';
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit();
}

$stmt = $conn->prepare("UPDATE scilab_form_requests SET statusScilabPersonnel = ? WHERE id = ?");
$stmt->bind_param("si", $newStatus, $requestId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database update failed.']);
}

$stmt->close();
$conn->close();
