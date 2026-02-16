<?php
include('../helperFiles/db_connection.php');
include('../helperFiles/session_handler.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

function sendRejectionNotificationToRequester($conn, $request, $rejectionReason, $rejectedBy) {
    $requesterID = $request['requesterEmployeeID'];
    $requesterEmail = null;

    // Try fetching email from accounts
    $stmtEmail = $conn->prepare("SELECT email FROM accounts WHERE employeeID = ?");
    $stmtEmail->bind_param("s", $requesterID);
    $stmtEmail->execute();
    $resEmail = $stmtEmail->get_result();
    if ($row = $resEmail->fetch_assoc()) {
        $requesterEmail = $row['email'];
    } else {
        // Try fetching email from student
        $stmtEmail = $conn->prepare("SELECT email FROM student WHERE LRN = ?");
        if ($stmtEmail) {
            $stmtEmail->bind_param("s", $requesterID);
            $stmtEmail->execute();
            $resEmail = $stmtEmail->get_result();
            if ($row = $resEmail->fetch_assoc()) {
                $requesterEmail = $row['email'];
            }
        }
    }
    $stmtEmail->close();

    if ($requesterEmail) {
        $subjectLine = "Update on your SciLab Request: Rejected";
        $rejectionReason = $rejectionReason ?? 'No reason provided.';

        // A template file like /templates/rejection_email_template.html could be created for a richer email.
        $body = 'Your request for ' . htmlspecialchars($request['scilabName']) . ' on ' . htmlspecialchars($request['inclusiveDate']) . ' has been rejected by the ' . htmlspecialchars($rejectedBy) . '.<br><br><strong>Reason:</strong> ' . htmlspecialchars($rejectionReason);

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'pshsircscilab@gmail.com';
            $mail->Password = 'wxzmkkrffptfchcc';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('pshsircscilab@gmail.com', 'SciLab Notification System');
            $mail->addAddress($requesterEmail);
            $mail->isHTML(true);
            $mail->Subject = $subjectLine;
            $mail->Body = $body;
            $mail->send();
        } catch (Exception $e) {
            error_log("Failed to send rejection email to {$requesterEmail}: " . $mail->ErrorInfo);
        }
    }
}

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
$reason = $input['reason'] ?? null;

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

// Update Database
$statusColumn = $fieldPrefix . '_status';
$newStatus = ($action === 'approve') ? 'approved' : 'rejected';

// Prepare SQL
// We dynamically build the query to include signature update if present
$sql = "UPDATE scilab_form_requests SET $statusColumn = ?";
$params = [$newStatus];
$types = "s";

if ($action === 'reject' && $reason !== null) {
    $sql .= ", feedback = ?";
    $params[] = $reason;
    $types .= "s";
}

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
    // Check if this is the final approval (CID Chief)
    if ($fieldPrefix === 'cid_chief' && $action === 'approve') {
        $requesterID = $request['requesterEmployeeID'];
        $requesterEmail = null;

        // Try fetching email from accounts
        $stmtEmail = $conn->prepare("SELECT email FROM accounts WHERE employeeID = ?");
        $stmtEmail->bind_param("s", $requesterID);
        $stmtEmail->execute();
        $resEmail = $stmtEmail->get_result();
        if ($row = $resEmail->fetch_assoc()) {
            $requesterEmail = $row['email'];
        } else {
            // Try fetching email from student
            $stmtEmail = $conn->prepare("SELECT email FROM student WHERE LRN = ?");
            if ($stmtEmail) {
                $stmtEmail->bind_param("s", $requesterID);
                $stmtEmail->execute();
                $resEmail = $stmtEmail->get_result();
                if ($row = $resEmail->fetch_assoc()) {
                    $requesterEmail = $row['email'];
                }
            }
        }

        if ($requesterEmail) {
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'pshsircscilab@gmail.com';
                $mail->Password = 'wxzmkkrffptfchcc';
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('pshsircscilab@gmail.com', 'SciLab Notification System');
                $mail->addAddress($requesterEmail);
                $mail->isHTML(true);
                $mail->Subject = 'SciLab Request Approved';
                $mail->Body = 'Your request for ' . htmlspecialchars($request['scilabName']) . ' on ' . htmlspecialchars($request['inclusiveDate']) . ' has been fully approved by the CID Chief.';

                $mail->send();
            } catch (Exception $e) {
                error_log("Failed to send approval email: " . $mail->ErrorInfo);
            }
        }
    } elseif ($action === 'reject') {
        $rejectedBy = ucwords(str_replace('_', ' ', $fieldPrefix));
        sendRejectionNotificationToRequester($conn, $request, $reason, $rejectedBy);
        }
    }

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database update failed: ' . $updateStmt->error]);
}

$updateStmt->close();
$conn->close();
?>