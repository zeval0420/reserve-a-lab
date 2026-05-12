<?php
// Catch fatal errors and throw them as JSON responses instead of crashing
ini_set('display_errors', 0);
error_reporting(E_ALL);

set_exception_handler(function($e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error', 
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit();
});

set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

include('../../scilab/helperFiles/db_connection.php');
include('../helperFiles/session_handler.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

function formatTime($time) {
    return date("g:i A", strtotime($time));
}

function sendSubmissionNotificationToSupervisors($conn, $data, $supervisorEmails, $formID) {
    if (empty($supervisorEmails)) return; 

    $subjectLine = "Action Required: New SciLab Request for Approval";
    $templatePath = __DIR__ . "/../templates/supervisor_request_email_template.html";

    if (file_exists($templatePath)) {
        $bodyTemplate = file_get_contents($templatePath);
    } else {
        $bodyTemplate = "A new request requires your approval. To view and approve the request, please click the button below.<br><br><a href='[ApprovalLink]' style='background-color: #4CAF50; color: white; padding: 14px 25px; text-align: center; text-decoration: none; display: inline-block;'>View Request Details</a><br><br><strong>Request Details:</strong><br>Facility: [Facility]<br>Requested By: [Requested By]<br>Date: [Start Date]<br>Time: [End Date]";
    }

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $baseURL = $protocol . $_SERVER['HTTP_HOST'] . "/reserve-a-lab";
    $approvalLink = $baseURL . "/supervisor_approve.php?id=" . $formID;

    $replacements = [
        "[Facility]" => $data['scilabName'],
        "[Grade Level]" => $data['gradeLevel'],
        "[Section]" => $data['section'],
        "[Subject]" => $data['subject'],
        "[Concurrent Topic]" => $data['topic'],
        "[Unit]" => $data['unit'],
        "[Teacher Name]" => $data['teacher'],
        "[Requested By]" => $data['requester'],
        "[Start Date]" => $data['inclusiveDate'],
        "[End Date]" => $data['inclusiveTime'],
        "[Materials]" => $data['materials'],
        "[Group Members]" => $data['students'],
        "[ApprovalLink]" => $approvalLink,
    ];

    foreach ($replacements as $key => $val) {
        $bodyTemplate = str_replace($key, htmlspecialchars($val), $bodyTemplate);
    }

    foreach ($supervisorEmails as $email) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'pshsircscilab@gmail.com';
                $mail->Password = 'wxzmkkrffptfchcc';
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('pshsircscilab@gmail.com', 'SciLab Notification System');
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = $subjectLine;
                $mail->Body = $bodyTemplate;

                $mail->send();
            } catch (Exception $e) {
                error_log("Supervisor email failed to {$email}: {$mail->ErrorInfo}");
            }
        }
    }
}

function sendNotificationToAdmins($conn, $requestID) {
    // Fetch request details
    $stmt = $conn->prepare("SELECT * FROM scilab_form_requests WHERE id = ?");
    $stmt->bind_param("i", $requestID);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = $res->fetch_assoc();
    $stmt->close();

    if (!$data) return;

    $requesterID = $data['requesterEmployeeID'];
    $requesterName = $requesterID;

    // Fetch requester name
    $stmtName = $conn->prepare("SELECT firstname, lastname FROM accounts WHERE employeeID = ?");
    $stmtName->bind_param("s", $requesterID);
    $stmtName->execute();
    $resName = $stmtName->get_result();
    if ($row = $resName->fetch_assoc()) {
        $requesterName = $row['firstname'] . ' ' . $row['lastname'];
        $stmtName->close();
    } else {
        $stmtName->close();
        $stmtName = $conn->prepare("SELECT firstname, lastname FROM student WHERE LRN = ?");
        if ($stmtName) {
            $stmtName->bind_param("s", $requesterID);
            $stmtName->execute();
            $resName = $stmtName->get_result();
            if ($row = $resName->fetch_assoc()) {
                $requesterName = $row['firstname'] . ' ' . $row['lastname'];
            }
            $stmtName->close();
        }
    }

    // Fetch materials
    $matStmt = $conn->prepare("SELECT quantity, unit, item, description FROM scilab_material_requests WHERE formID = ?");
    $matStmt->bind_param("i", $requestID);
    $matStmt->execute();
    $matRes = $matStmt->get_result();
    $materials = [];
    while ($row = $matRes->fetch_assoc()) {
        $materials[] = "{$row['quantity']} {$row['unit']} of {$row['item']} ({$row['description']})";
    }
    $materialsStr = implode("; ", $materials);
    $matStmt->close();

    // Fetch students
    $studStmt = $conn->prepare("SELECT student_name FROM scilab_students_involved WHERE formID = ?");
    $studStmt->bind_param("i", $requestID);
    $studStmt->execute();
    $studRes = $studStmt->get_result();
    $students = [];
    while ($row = $studRes->fetch_assoc()) {
        $students[] = $row['student_name'];
    }
    $studentsStr = implode(", ", $students);
    $studStmt->close();

    $admins = $conn->query("SELECT email FROM accounts WHERE status = 'active' AND (position = 'Sci. Res. Assist.' OR position = 'Sci. Research Specialist I')");
    if ($admins->num_rows === 0) return;

    $subjectLine = "New SciLab Request Submitted (Approved by Subject Teacher)";
    $templatePath = __DIR__ . "/../templates/request_email_template.html";
    
    if (file_exists($templatePath)) {
        $bodyTemplate = file_get_contents($templatePath);
    } else {
        $bodyTemplate = "A new request has been approved by the subject teacher and is ready for admin review.";
    }

    $replacements = [
        "[Facility]" => $data['scilabName'],
        "[Grade Level]" => $data['gradeLevel'],
        "[Section]" => $data['sections'],
        "[Subject]" => $data['subject'],
        "[Concurrent Topic]" => $data['subjectTopic'],
        "[Unit]" => "N/A",
        "[Teacher Name]" => $data['teacherInCharge'],
        "[Requested By]" => $requesterName,
        "[Start Date]" => $data['inclusiveDate'],
        "[End Date]" => $data['inclusiveTime'],
        "[Materials]" => $materialsStr,
        "[Group Members]" => $studentsStr,
    ];

    foreach ($replacements as $key => $val) {
        $bodyTemplate = str_replace($key, htmlspecialchars($val), $bodyTemplate);
    }

    while ($admin = $admins->fetch_assoc()) {
        if (filter_var($admin['email'], FILTER_VALIDATE_EMAIL)) {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'pshsircscilab@gmail.com';
                $mail->Password = 'wxzmkkrffptfchcc';
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('pshsircscilab@gmail.com', 'SciLab Notification System');
                $mail->addAddress($admin['email']);

                $mail->isHTML(true);
                $mail->Subject = $subjectLine;
                $mail->Body = $bodyTemplate;

                $mail->send();
            } catch (Exception $e) {
                error_log("Admin email failed to {$admin['email']}: {$mail->ErrorInfo}");
            }
        }
    }
}

function sendNotificationToSubjectTeacher($conn, $requestID) {
    // Fetch request details
    $stmt = $conn->prepare("SELECT * FROM scilab_form_requests WHERE id = ?");
    $stmt->bind_param("i", $requestID);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = $res->fetch_assoc();
    $stmt->close();

    if (!$data) return;

    $requesterID = $data['requesterEmployeeID'];
    $requesterName = $requesterID;

    // Fetch requester name
    $stmtName = $conn->prepare("SELECT firstname, lastname FROM accounts WHERE employeeID = ?");
    $stmtName->bind_param("s", $requesterID);
    $stmtName->execute();
    $resName = $stmtName->get_result();
    if ($row = $resName->fetch_assoc()) {
        $requesterName = $row['firstname'] . ' ' . $row['lastname'];
        $stmtName->close();
    } else {
        $stmtName->close();
        $stmtName = $conn->prepare("SELECT firstname, lastname FROM student WHERE LRN = ?");
        if ($stmtName) {
            $stmtName->bind_param("s", $requesterID);
            $stmtName->execute();
            $resName = $stmtName->get_result();
            if ($row = $resName->fetch_assoc()) {
                $requesterName = $row['firstname'] . ' ' . $row['lastname'];
            }
            $stmtName->close();
        }
    }

    // Fetch materials
    $matStmt = $conn->prepare("SELECT quantity, unit, item, description FROM scilab_material_requests WHERE formID = ?");
    $matStmt->bind_param("i", $requestID);
    $matStmt->execute();
    $matRes = $matStmt->get_result();
    $materials = [];
    while ($row = $matRes->fetch_assoc()) {
        $materials[] = "{$row['quantity']} {$row['unit']} of {$row['item']} ({$row['description']})";
    }
    $materialsStr = implode("; ", $materials);
    $matStmt->close();

    // Fetch students
    $studStmt = $conn->prepare("SELECT student_name FROM scilab_students_involved WHERE formID = ?");
    $studStmt->bind_param("i", $requestID);
    $studStmt->execute();
    $studRes = $studStmt->get_result();
    $students = [];
    while ($row = $studRes->fetch_assoc()) {
        $students[] = $row['student_name'];
    }
    $studentsStr = implode(", ", $students);
    $studStmt->close();

    $subjectTeachers = $conn->query("SELECT email FROM accounts WHERE status = 'active' AND position LIKE '%Teacher%'");
    if ($subjectTeachers->num_rows === 0) return;

    $subjectLine = "Action Required: Subject Teacher Approval Needed";
    $templatePath = __DIR__ . "/../templates/supervisor_request_email_template.html";

    if (file_exists($templatePath)) {
        $bodyTemplate = file_get_contents($templatePath);
    } else {
        $bodyTemplate = "A new request has been approved by the Supervisor and requires your approval. To view and approve the request, please click the button below.<br><br><a href='[ApprovalLink]' style='background-color: #4CAF50; color: white; padding: 14px 25px; text-align: center; text-decoration: none; display: inline-block;'>View Request Details</a><br><br><strong>Request Details:</strong><br>Facility: [Facility]<br>Requested By: [Requested By]<br>Date: [Start Date]<br>Time: [End Date]";
    }

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $baseURL = $protocol . $_SERVER['HTTP_HOST'] . "/reserve-a-lab";
    $approvalLink = $baseURL . "/supervisor_approve.php?id=" . $requestID;

    $replacements = [
        "[Facility]"         => $data['scilabName'],
        "[Grade Level]"      => $data['gradeLevel'],
        "[Section]"          => $data['sections'],
        "[Subject]"          => $data['subject'],
        "[Concurrent Topic]" => $data['subjectTopic'],
        "[Unit]"             => "N/A",
        "[Teacher Name]"     => $data['teacherInCharge'],
        "[Requested By]"     => $requesterName,
        "[Start Date]"       => $data['inclusiveDate'],
        "[End Date]"         => $data['inclusiveTime'],
        "[Materials]"        => $materialsStr,
        "[Group Members]"    => $studentsStr,
        "[ApprovalLink]"     => $approvalLink,
    ];

    foreach ($replacements as $key => $val) {
        $bodyTemplate = str_replace($key, htmlspecialchars($val), $bodyTemplate);
    }

    while ($teacher = $subjectTeachers->fetch_assoc()) {
        if (filter_var($teacher['email'], FILTER_VALIDATE_EMAIL)) {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'pshsircscilab@gmail.com';
                $mail->Password = 'wxzmkkrffptfchcc';
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('pshsircscilab@gmail.com', 'SciLab Notification System');
                $mail->addAddress($teacher['email']);
                $mail->isHTML(true);
                $mail->Subject = $subjectLine;
                $mail->Body = $bodyTemplate;
                $mail->send();
            } catch (Exception $e) {
                error_log("Subject Teacher email failed to {$teacher['email']}: {$mail->ErrorInfo}");
            }
        }
    }
}

function sendNotificationToCIDChief($conn, $requestID) {
    // Fetch request details
    $stmt = $conn->prepare("SELECT * FROM scilab_form_requests WHERE id = ?");
    $stmt->bind_param("i", $requestID);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = $res->fetch_assoc();
    $stmt->close();

    if (!$data) return;

    $requesterID = $data['requesterEmployeeID'];
    $requesterName = $requesterID;

    // Fetch requester name
    $stmtName = $conn->prepare("SELECT firstname, lastname FROM accounts WHERE employeeID = ?");
    $stmtName->bind_param("s", $requesterID);
    $stmtName->execute();
    $resName = $stmtName->get_result();
    if ($row = $resName->fetch_assoc()) {
        $requesterName = $row['firstname'] . ' ' . $row['lastname'];
        $stmtName->close();
    } else {
        $stmtName->close();
        $stmtName = $conn->prepare("SELECT firstname, lastname FROM student WHERE LRN = ?");
        if ($stmtName) {
            $stmtName->bind_param("s", $requesterID);
            $stmtName->execute();
            $resName = $stmtName->get_result();
            if ($row = $resName->fetch_assoc()) {
                $requesterName = $row['firstname'] . ' ' . $row['lastname'];
            }
            $stmtName->close();
        }
    }

    $cidChiefs = $conn->query("SELECT email FROM accounts WHERE status = 'active' AND position LIKE '%Chief%'");
    if ($cidChiefs->num_rows === 0) return;

    $subjectLine = "Action Required: CID Chief Final Approval Needed";
    $bodyTemplate = "A new request has passed Lab Personnel review and requires final CID Chief approval. 
        <br><br><strong>Facility:</strong> " . htmlspecialchars($data['scilabName']) . "
        <br><strong>Requested By:</strong> " . htmlspecialchars($requesterName) . "
        <br><strong>Date/Time:</strong> " . htmlspecialchars($data['inclusiveDate']) . " " . htmlspecialchars($data['inclusiveTime']) . "
        <br><br>Please log in to the system to review and approve.";

    while ($admin = $cidChiefs->fetch_assoc()) {
        if (filter_var($admin['email'], FILTER_VALIDATE_EMAIL)) {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'pshsircscilab@gmail.com';
                $mail->Password = 'wxzmkkrffptfchcc';
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('pshsircscilab@gmail.com', 'SciLab Notification System');
                $mail->addAddress($admin['email']);
                $mail->isHTML(true);
                $mail->Subject = $subjectLine;
                $mail->Body = $bodyTemplate;
                $mail->send();
            } catch (Exception $e) {
                error_log("CID Chief email failed to {$admin['email']}: {$mail->ErrorInfo}");
            }
        }
    }
}

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

if (isset($_POST["action"]) && $_POST["action"] == "request_submission") {
    $scilabName = $_POST['venue'] ?? '';
    $grade = intval($_POST['grade_level'] ?? 0);
    $sections = isset($_POST['sections']) && is_array($_POST['sections']) ? implode(', ', $_POST['sections']) : ($_POST['sections'] ?? '');
    $subject = $_POST['subject'] ?? '';
    $topic = $_POST['topic'] ?? '';
    $unit = $_POST['unit'] ?? '';
    $teacher_input = $_POST['teacher[]'] ?? $_POST['teacher'] ?? [];
    $teachers = is_array($teacher_input) ? $teacher_input : array_filter([$teacher_input]);
    $teacher = implode(', ', $teachers);
    $startDate = $_POST['inclusive_date'] ?? '';
    $startTime = $_POST['start_time'] ?? '';
    $endTime = $_POST['end_time'] ?? '';
    $formattedTime = formatTime($startTime) . " to " . formatTime($endTime);

    $checkSciLab = $conn->prepare("SELECT 1 FROM scilab_availability WHERE scilabName = ? AND status = 'active'");
    $checkSciLab->bind_param("s", $scilabName);
    $checkSciLab->execute();
    $checkSciLab->store_result();

    if ($checkSciLab->num_rows === 0) {
        echo "invalid_scilab";
        exit();
    }

    $conflictStmt = $conn->prepare("SELECT * FROM scilab_form_requests 
        WHERE scilabName = ? AND inclusiveDate = ? AND (
            (inclusiveTime COLLATE utf8mb4_bin LIKE CONCAT('%', ?, '%')) OR
            (inclusiveTime COLLATE utf8mb4_bin LIKE CONCAT('%', ?, '%'))
        ) AND statusScilabPersonnel != 'Rejected'");

    $conflictStmt->bind_param("ssss", $scilabName, $startDate, $startTime, $endTime);
    $conflictStmt->execute();
    $conflictResult = $conflictStmt->get_result();

    if ($conflictResult->num_rows > 0) {
        echo "conflict";
        exit();
    }

    $syResult = $conn->query("SELECT value FROM current WHERE description = 'School Year' ORDER BY id DESC LIMIT 1");
    $schoolYear = ($syResult && $syResult->num_rows > 0) ? $syResult->fetch_assoc()['value'] : 'N/A';

    $requesterID = $_SESSION['employeeID'] ?? $_SESSION['student_lrn'] ?? '';
    $dateRequested = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("INSERT INTO scilab_form_requests (
        scilabName, gradeLevel, sections, subject, subjectTopic, inclusiveDate, inclusiveTime, dateRequested, requesterEmployeeID, sy, teacherInCharge, statusScilabPersonnel, supervisor_status, subject_teacher_status, lab_personnel_status, cid_chief_status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', 'pending', 'pending', 'pending', 'pending')");

    $stmt->bind_param("sisssssssss", $scilabName, $grade, $sections, $subject, $topic, $startDate, $formattedTime, $dateRequested, $requesterID, $schoolYear, $teacher);
    
    if (!$stmt->execute()) {
        echo "error";
        exit();
    }

    $formID = $stmt->insert_id;

    $materials = [];
    $stmt2 = $conn->prepare("INSERT INTO scilab_material_requests (formID, item, quantity, unit, description) VALUES (?, ?, ?, ?, ?)");

    if (isset($_POST['mergedMaterials'])) {
        $mergedMaterials = json_decode($_POST['mergedMaterials'], true);
        if (is_array($mergedMaterials)) {
            foreach ($mergedMaterials as $mat) {
                $item = trim($mat['item'] ?? '');
                $unit = trim($mat['unit'] ?? 'N/A');
                $desc = trim($mat['desc'] ?? 'N/A');
                $qty = isset($mat['qty']) ? (int)$mat['qty'] : 1;
                if ($item !== '') {
                    $stmt2->bind_param("isiss", $formID, $item, $qty, $unit, $desc);
                    $stmt2->execute();
                    $materials[] = "{$qty} {$unit} of {$item} ({$desc})";
                }
            }
        }
    }

    $students = $_POST['students'] ?? [];
    $studentList = implode(", ", array_filter($students));
    $stmt3 = $conn->prepare("INSERT INTO scilab_students_involved (formID, student_name) VALUES (?, ?)");
    foreach ($students as $student) {
        $student = trim($student);
        if ($student !== '') {
            $stmt3->bind_param("is", $formID, $student);
            $stmt3->execute();
        }
    }
    $stmt3->close();

    $requesterName = ($_SESSION['firstname'] ?? '') . ' ' . ($_SESSION['middlename'] ?? '') . ' ' . ($_SESSION['lastname'] ?? '');

    if (!empty($teachers)) {
        $teacherEmails = [];
        $placeholders = rtrim(str_repeat('?,', count($teachers)), ',');
        $email_stmt = $conn->prepare("SELECT email FROM accounts WHERE CONCAT(lastname, ', ', firstname, ' ', IFNULL(middlename, '')) IN ($placeholders)");
        if ($email_stmt) {
            $types = str_repeat('s', count($teachers));
            $bindParams = [$types];
            for ($i = 0; $i < count($teachers); $i++) {
                $bindParams[] = &$teachers[$i];
            }
            call_user_func_array([$email_stmt, 'bind_param'], $bindParams);
            $email_stmt->execute();
            $result = $email_stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $teacherEmails[] = $row['email'];
            }
            $email_stmt->close();
        }

        if (!empty($teacherEmails)) {
            sendSubmissionNotificationToSupervisors($conn, [
                'scilabName' => $scilabName,
                'gradeLevel' => $grade,
                'section' => $sections,
                'subject' => $subject,
                'topic' => $topic,
                'unit' => $unit,
                'teacher' => $teacher,
                'inclusiveDate' => $startDate,
                'inclusiveTime' => $formattedTime,
                'materials' => implode("; ", $materials),
                'students' => $studentList,
                'requester' => $requesterName
            ], $teacherEmails, $formID);
        }
    }

    echo "success";
    $stmt->close();
    $stmt2->close();
    exit();
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

if ($action === 'force_approve_override') {
    $fieldPrefix = 'force_approve';
} elseif (($request['supervisor_status'] ?? 'pending') === 'pending') {
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

// Determine which column to update based on current status flow
if ($fieldPrefix === 'force_approve') {
    $sql = "UPDATE scilab_form_requests SET statusScilabPersonnel = 'Approved', supervisor_status = 'approved', subject_teacher_status = 'approved', lab_personnel_status = 'approved', cid_chief_status = 'approved'";
    $params = [];
    $types = "";
    
    if ($action === 'force_approve_override' && $reason !== null) {
        $sql .= ", feedback = ?";
        $params[] = $reason;
        $types .= "s";
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $requestId;
    $types .= "i";
} else {
    $statusColumn = $fieldPrefix . '_status';
    $newStatus = ($action === 'approve') ? 'approved' : 'rejected';

    // Prepare SQL
    $sql = "UPDATE scilab_form_requests SET $statusColumn = ?";
    
    // If rejected at any stage, mark the whole request as Rejected
    if ($action === 'reject') {
        $sql .= ", statusScilabPersonnel = 'Rejected'";
    } elseif ($action === 'approve' && $fieldPrefix === 'cid_chief') {
        // If final stage approved, mark the whole request as Approved
        $sql .= ", statusScilabPersonnel = 'Approved'";
    }

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
}

$updateStmt = $conn->prepare($sql);
if (!$updateStmt) {
    echo json_encode(['success' => false, 'message' => 'Database prepare failed: ' . $conn->error]);
    exit;
}

$bindParams = [$types];
for ($i = 0; $i < count($params); $i++) {
    $bindParams[] = &$params[$i];
}
call_user_func_array([$updateStmt, 'bind_param'], $bindParams);

if ($updateStmt->execute()) {
    // Check if this is the final approval (CID Chief or Force Approve)
    if (($fieldPrefix === 'cid_chief' && $action === 'approve') || $fieldPrefix === 'force_approve') {
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
                
                if ($fieldPrefix === 'force_approve') {
                    $mail->Body = 'Your request for ' . htmlspecialchars($request['scilabName']) . ' on ' . htmlspecialchars($request['inclusiveDate']) . ' has been FORCE APPROVED by an Administrator.';
                } else {
                    $mail->Body = 'Your request for ' . htmlspecialchars($request['scilabName']) . ' on ' . htmlspecialchars($request['inclusiveDate']) . ' has been fully approved by the CID Chief.';
                }

                $mail->send();
            } catch (Exception $e) {
                error_log("Failed to send approval email: " . $mail->ErrorInfo);
            }
        }
    } elseif ($fieldPrefix === 'supervisor' && $action === 'approve') {
        sendNotificationToSubjectTeacher($conn, $requestId);
    } elseif ($fieldPrefix === 'subject_teacher' && $action === 'approve') {
        sendNotificationToAdmins($conn, $requestId);
    } elseif ($fieldPrefix === 'lab_personnel' && $action === 'approve') {
        sendNotificationToCIDChief($conn, $requestId);
    } elseif ($action === 'reject') {
        $rejectedBy = ucwords(str_replace('_', ' ', $fieldPrefix));
        sendRejectionNotificationToRequester($conn, $request, $reason, $rejectedBy);
    }

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database update failed: ' . $updateStmt->error]);
}

$updateStmt->close();
$conn->close();
?>