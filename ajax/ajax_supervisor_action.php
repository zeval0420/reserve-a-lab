<?php
include('../helperFiles/db_connection.php');
include('../helperFiles/session_handler.php');

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$username = $_SESSION['username'];

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['request_id']) || !isset($input['action'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input parameters']);
    exit;
}

$requestId = $input['request_id'];
$action = $input['action']; // 'approve' or 'reject'
$signatureData = $input['signature'] ?? null;

// Fetch request to determine current stage
$stmt = $conn->prepare("SELECT * FROM scilab_form_requests WHERE id = ?");
$stmt->bind_param("i", $requestId);
$stmt->execute();
$result = $stmt->get_result();
$request = $result->fetch_assoc();

if (!$request) {
    echo json_encode(['success' => false, 'message' => 'Request not found']);
    exit;
}

// Determine which column to update based on current status flow
// Priority: Supervisor -> Subject Teacher -> Lab Personnel -> CID Chief
$fieldPrefix = '';

if (($request['supervisor_status'] ?? 'pending') === 'pending') {
    $fieldPrefix = 'supervisor';
} elseif (($request['subject_teacher_status'] ?? 'pending') === 'pending') {
    $fieldPrefix = 'subject_teacher';
} elseif (($request['lab_personnel_status'] ?? 'pending') === 'pending') {
    $fieldPrefix = 'lab_personnel';
} elseif (($request['cid_chief_status'] ?? 'pending') === 'pending') {
    $fieldPrefix = 'cid_chief';
} else {
    echo json_encode(['success' => false, 'message' => 'No pending approval stage found or request is already completed.']);
    exit;
}

// Handle Signature Upload (Only for Approval)
$signaturePath = null;
if ($action === 'approve' && !empty($signatureData)) {
    // Ensure directory exists
    $targetDir = "../img/signatures/";
    if (!file_exists($targetDir)) {
        if (!mkdir($targetDir, 0777, true)) {
            echo json_encode(['success' => false, 'message' => 'Failed to create signature directory']);
            exit;
        }
    }

    // Decode Base64
    // Data URI scheme: "data:image/png;base64,......"
    if (preg_match('/^data:image\/(\w+);base64,/', $signatureData, $type)) {
        $data = substr($signatureData, strpos($signatureData, ',') + 1);
        $type = strtolower($type[1]); // jpg, png, gif

        if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid image type']);
            exit;
        }

        $decoded = base64_decode($data);

        if ($decoded === false) {
            echo json_encode(['success' => false, 'message' => 'Invalid base64 data']);
            exit;
        }

        // Generate filename
        $safeUsername = preg_replace('/[^a-zA-Z0-9]/', '_', $username);
        $filename = $safeUsername . "." . $type;
        $filepath = $targetDir . $filename;

        if (file_put_contents($filepath, $decoded)) {
            $signaturePath = "img/signatures/" . $filename; // Path relative to web root
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save signature file']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid signature format']);
        exit;
    }
}

// Update Database
$statusColumn = $fieldPrefix . '_status';
$newStatus = ($action === 'approve') ? 'approved' : 'rejected';

// Prepare SQL
// We dynamically build the query to include signature update if present
$sql = "UPDATE scilab_form_requests SET $statusColumn = ?";
$params = [$newStatus];
$types = "s";

$sql .= " WHERE id = ?";
$params[] = $requestId;
$types .= "i";

$updateStmt = $conn->prepare($sql);
if (!$updateStmt) {
    echo json_encode(['success' => false, 'message' => 'Database prepare failed: ' . $conn->error]);
    exit;
}

$updateStmt->bind_param($types, ...$params);

if ($updateStmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database update failed: ' . $updateStmt->error]);
}

$updateStmt->close();
$conn->close();
?>