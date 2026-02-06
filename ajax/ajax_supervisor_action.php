<?php
include('../helperFiles/db_connection.php');
include('../helperFiles/session_handler.php');
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$requestId = $data['request_id'] ?? null;
$action = $data['action'] ?? null;

if (!$requestId || !$action) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit();
}

if (!isset($_SESSION['username']) || !isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$loggedInUsername = trim($_SESSION['username']);
$loggedInEmail = trim($_SESSION['email']);

$userStmt = $conn->prepare("SELECT position FROM accounts WHERE email = ? LIMIT 1");
$userStmt->bind_param("s", $loggedInEmail);
$userStmt->execute();
$userResult = $userStmt->get_result();
$userAccount = $userResult->fetch_assoc();
$userStmt->close();

if (!$userAccount) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$userPosition = trim($userAccount['position']);

$stmt = $conn->prepare("SELECT supervisor_status, subject_teacher_status, lab_personnel_status, cid_chief_status, supervisor_name, subject_teacher_name, requesterEmployeeID, scilabName, inclusiveDate, inclusiveTime FROM scilab_form_requests WHERE id = ?");
$stmt->bind_param("i", $requestId);
$stmt->execute();
$result = $stmt->get_result();
$request = $result->fetch_assoc();
$stmt->close();

if (!$request) {
    echo json_encode(['success' => false, 'message' => 'Request not found.']);
    exit();
}

$supervisor_status = $request['supervisor_status'];
$subject_teacher_status = $request['subject_teacher_status'];
$lab_personnel_status = $request['lab_personnel_status'];
$cid_chief_status = $request['cid_chief_status'];

$supervisor_name = trim($request['supervisor_name']);
$subject_teacher_name = trim($request['subject_teacher_name']);

$studentName = $request['requesterEmployeeID'];
$labName = $request['scilabName'];
$requestDate = $request['inclusiveDate'];
$requestTime = $request['inclusiveTime'];

$statusColumn = '';
$currentStageStatus = '';
$nextRole = null;
$updatedStage = '';
$authorized = false;

if (strcasecmp($loggedInUsername, $supervisor_name) === 0) {
    $statusColumn = 'supervisor_status';
    $currentStageStatus = $supervisor_status;
    $nextRole = 'subject_teacher';
    $updatedStage = 'supervisor';
    $authorized = true;
} elseif (strcasecmp($loggedInUsername, $subject_teacher_name) === 0) {
    $statusColumn = 'subject_teacher_status';
    $currentStageStatus = $subject_teacher_status;
    $nextRole = 'lab_personnel';
    $updatedStage = 'subject_teacher';
    
    if ($supervisor_status !== 'approved') {
        echo json_encode(['success' => false, 'message' => 'Approval not allowed at this stage.']);
        exit();
    }
    
    $authorized = true;
} elseif (in_array($userPosition, ['Sci. Res. Assist.', 'Sci. Research Specialist I'])) {
    $statusColumn = 'lab_personnel_status';
    $currentStageStatus = $lab_personnel_status;
    $nextRole = 'cid_chief';
    $updatedStage = 'lab_personnel';
    
    if ($supervisor_status !== 'approved' || $subject_teacher_status !== 'approved') {
        echo json_encode(['success' => false, 'message' => 'Approval not allowed at this stage.']);
        exit();
    }
    
    $authorized = true;
} elseif ($userPosition === 'CID Chief') {
    $statusColumn = 'cid_chief_status';
    $currentStageStatus = $cid_chief_status;
    $nextRole = null;
    $updatedStage = 'cid_chief';
    
    if ($supervisor_status !== 'approved' || $subject_teacher_status !== 'approved' || $lab_personnel_status !== 'approved') {
        echo json_encode(['success' => false, 'message' => 'Approval not allowed at this stage.']);
        exit();
    }
    
    $authorized = true;
}

if (!$authorized) {
    echo json_encode(['success' => false, 'message' => 'Approval not allowed at this stage.']);
    exit();
}

if ($currentStageStatus === 'approved') {
    echo json_encode(['success' => false, 'message' => 'Approval not allowed at this stage.']);
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

$updateStmt = $conn->prepare("UPDATE scilab_form_requests SET $statusColumn = ? WHERE id = ?");
$updateStmt->bind_param("si", $newStatus, $requestId);

if (!$updateStmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Database update failed.']);
    $updateStmt->close();
    exit();
}

$updateStmt->close();

$notificationSent = false;

if ($action === 'approve' && $nextRole !== null) {
    $recipientEmails = [];
    
    if ($nextRole === 'subject_teacher') {
        $emailStmt = $conn->prepare("SELECT email, username FROM accounts WHERE TRIM(username) = ? LIMIT 1");
        $emailStmt->bind_param("s", $subject_teacher_name);
        $emailStmt->execute();
        $emailResult = $emailStmt->get_result();
        
        if ($row = $emailResult->fetch_assoc()) {
            $recipientEmails[] = ['email' => $row['email'], 'username' => $row['username']];
        }
        $emailStmt->close();
    } elseif ($nextRole === 'lab_personnel') {
        $emailStmt = $conn->prepare("SELECT email, username FROM accounts WHERE position IN ('Sci. Res. Assist.', 'Sci. Research Specialist I') AND status = 'active'");
        $emailStmt->execute();
        $emailResult = $emailStmt->get_result();
        
        while ($row = $emailResult->fetch_assoc()) {
            $recipientEmails[] = ['email' => $row['email'], 'username' => $row['username']];
        }
        $emailStmt->close();
    } elseif ($nextRole === 'cid_chief') {
        $emailStmt = $conn->prepare("SELECT email, username FROM accounts WHERE position = 'CID Chief' AND status = 'active'");
        $emailStmt->execute();
        $emailResult = $emailStmt->get_result();
        
        while ($row = $emailResult->fetch_assoc()) {
            $recipientEmails[] = ['email' => $row['email'], 'username' => $row['username']];
        }
        $emailStmt->close();
    }
    
    if (!empty($recipientEmails)) {
        $approvalLink = 'https://' . $_SERVER['HTTP_HOST'] . '/approve_request.php?id=' . urlencode($requestId);
        
        $roleDisplayNames = [
            'subject_teacher' => 'Subject Teacher / Unit Head',
            'lab_personnel' => 'Laboratory Personnel',
            'cid_chief' => 'CID Chief'
        ];
        
        $roleDisplay = $roleDisplayNames[$nextRole] ?? ucfirst(str_replace('_', ' ', $nextRole));
        
        foreach ($recipientEmails as $recipient) {
            try {
                $mail = new PHPMailer(true);
                
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'your-email@gmail.com';
                $mail->Password = 'your-app-password';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                
                $mail->setFrom('noreply@school.edu', 'Science Laboratory System');
                $mail->addAddress($recipient['email'], $recipient['username']);
                
                $mail->isHTML(true);
                $mail->Subject = 'Laboratory Request Awaiting Your Approval - Request #' . $requestId;
                
                $mail->Body = '
                <!DOCTYPE html>
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 8px 8px 0 0; }
                        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                        .detail-row { margin: 12px 0; }
                        .label { font-weight: bold; color: #555; }
                        .value { color: #333; }
                        .button { display: inline-block; background: #667eea; color: white; padding: 14px 28px; text-decoration: none; border-radius: 6px; margin-top: 20px; }
                        .footer { text-align: center; margin-top: 30px; color: #888; font-size: 12px; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h2 style="margin: 0;">Laboratory Request Approval Required</h2>
                        </div>
                        <div class="content">
                            <p>Dear ' . htmlspecialchars($recipient['username']) . ',</p>
                            <p>A laboratory request requires your approval as <strong>' . htmlspecialchars($roleDisplay) . '</strong>.</p>
                            
                            <div style="background: white; padding: 20px; border-radius: 6px; margin: 20px 0;">
                                <h3 style="margin-top: 0; color: #667eea;">Request Details</h3>
                                <div class="detail-row">
                                    <span class="label">Request ID:</span>
                                    <span class="value">#' . htmlspecialchars($requestId) . '</span>
                                </div>
                                <div class="detail-row">
                                    <span class="label">Requester:</span>
                                    <span class="value">' . htmlspecialchars($studentName) . '</span>
                                </div>
                                <div class="detail-row">
                                    <span class="label">Laboratory:</span>
                                    <span class="value">' . htmlspecialchars($labName) . '</span>
                                </div>
                                <div class="detail-row">
                                    <span class="label">Date:</span>
                                    <span class="value">' . htmlspecialchars($requestDate) . '</span>
                                </div>
                                <div class="detail-row">
                                    <span class="label">Time:</span>
                                    <span class="value">' . htmlspecialchars($requestTime) . '</span>
                                </div>
                            </div>
                            
                            <p>Please review and process this request at your earliest convenience.</p>
                            
                            <a href="' . htmlspecialchars($approvalLink) . '" class="button">Review Request</a>
                            
                            <div class="footer">
                                <p>This is an automated notification from the Science Laboratory Request System.</p>
                                <p>Please do not reply to this email.</p>
                            </div>
                        </div>
                    </div>
                </body>
                </html>
                ';
                
                $mail->AltBody = "Dear " . $recipient['username'] . ",\n\n" .
                                 "A laboratory request requires your approval as " . $roleDisplay . ".\n\n" .
                                 "Request ID: #" . $requestId . "\n" .
                                 "Requester: " . $studentName . "\n" .
                                 "Laboratory: " . $labName . "\n" .
                                 "Date: " . $requestDate . "\n" .
                                 "Time: " . $requestTime . "\n\n" .
                                 "Please review this request by visiting:\n" . $approvalLink . "\n\n" .
                                 "This is an automated notification from the Science Laboratory Request System.";
                
                $mail->send();
                $notificationSent = true;
            } catch (Exception $e) {
                error_log("Notification email failed for {$recipient['email']}: {$mail->ErrorInfo}");
            }
        }
    }
}

echo json_encode([
    'success' => true,
    'updated_stage' => $updatedStage,
    'next_stage' => $nextRole,
    'notification_sent' => $notificationSent
]);

$conn->close();
?>