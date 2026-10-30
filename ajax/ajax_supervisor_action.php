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

// Convert fatal errors (which bypass the handlers above) into a readable JSON response
// so the client never receives an empty HTTP 500 body.
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => $error['message'],
            'file' => $error['file'],
            'line' => $error['line']
        ]);
    }
});

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

try {
    include('../../scilab/helperFiles/db_connection.php');
    include('../helperFiles/session_handler.php');
    include('../helperFiles/variableDeclarations.php');
    require_once __DIR__ . '/../helperFiles/input.php';
    include_once('../helperFiles/scilab_email.php');
} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Server initialization failed: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit();
}

function formatTime($time) {
    return date("g:i A", strtotime($time));
}

/**
 * Resolve the currently available stock for an inventory item, mirroring the
 * client-side display rules: Consumables match the exact description row
 * (falling back to the item total), Reagents use the item's total stock.
 * Returns [availableQty, classification] or [null, null] when not tracked.
 */
function scilab_available_quantity($conn, $item, $desc)
{
    $item = trim((string)$item);
    if ($item === '') return [null, null];

    $stmt = $conn->prepare("SELECT classification, description, quantity FROM scilab_inventory WHERE item = ? AND (status IS NULL OR status != 'Removed') ORDER BY quantity DESC, id ASC");
    if (!$stmt) return [null, null];
    $stmt->bind_param("s", $item);
    $stmt->execute();
    $result = $stmt->get_result();

    $classification = null;
    $total = 0;
    $exactRowQty = null;
    $requestedDesc = trim((string)$desc);

    while ($row = $result->fetch_assoc()) {
        $total += intval($row['quantity']);
        if ($classification === null) {
            $classification = trim((string)$row['classification']);
        }
        if ($exactRowQty === null && $requestedDesc !== '' && strcasecmp(trim((string)$row['description']), $requestedDesc) === 0) {
            $exactRowQty = intval($row['quantity']);
        }
    }
    $stmt->close();

    if ($classification === null) return [null, null];

    $isConsumable = strcasecmp($classification, 'Consumable') === 0;
    $isReagent = strcasecmp($classification, 'Reagent') === 0;
    if (!$isConsumable && !$isReagent) return [null, null];

    if ($isConsumable && $requestedDesc !== '' && $exactRowQty !== null) {
        return [$exactRowQty, $classification];
    }

    return [$total, $classification];
}

function sendNotificationToAdmins($conn, $requestID) {
    global $email_smtp_host, $email_smtp_user, $email_smtp_password, $email_smtp_secure, $email_smtp_port, $email_sender, $active_server;

    // Fetch request details
    $stmt = $conn->prepare("SELECT * FROM scilab_form_requests WHERE id = ?");
    $stmt->bind_param("i", $requestID);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = $res->fetch_assoc();
    $stmt->close();
    $data = scilab_label_row($conn, $data);

    if (!$data) return;

    $requesterID = $data['requesterEmployeeID'];
    $requesterName = scilab_resolve_requester_name($conn, $requesterID);

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
    if ($admins->num_rows === 0) {
        // Nobody can act on this stage - escalate so the request does not stall.
        scilab_notify_unresolved_stage($conn, $data, 'lab_personnel',
            'No active account holds the position "Sci. Res. Assist." or "Sci. Research Specialist I".');
        return;
    }

    $subjectLine = "SciLab Request SLR-" . $requestID;
    $templatePath = __DIR__ . "/../templates/request_email_template.html";
    
    if (file_exists($templatePath)) {
        $bodyTemplate = file_get_contents($templatePath);
    } else {
        $bodyTemplate = "A new request has passed the earlier approval stages and is ready for admin review.";
    }

    $replacements = [
        "[Request ID]" => "SLR-" . $requestID,
        "[Facility]" => $data['scilabName'],
        "[Grade Level]" => $data['gradeLevel'],
        "[Section]" => $data['sections'],
        "[Subject]" => $data['subject'],
        "[Concurrent Topic]" => $data['subjectTopic'],
        "[Unit]" => ($data['subjectAcademicUnit'] ?? '') !== '' ? $data['subjectAcademicUnit'] : "N/A",
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
                $mail->Host = $email_smtp_host;
                $mail->SMTPAuth = true;
                $mail->Username = $email_smtp_user;
                $mail->Password = $email_smtp_password;
                $mail->SMTPSecure = $email_smtp_secure;
                $mail->Port = $email_smtp_port;

                $mail->setFrom($email_sender, 'PSHS-IRC SciLab');
                $mail->addAddress($admin['email']);

                $mail->isHTML(true);
                $mail->Subject = $subjectLine;
                
                // Inject ActionLink (direct passwordless action link for Lab Personnel)
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                $baseURL = $protocol . $_SERVER['HTTP_HOST'] . "/" . $active_server;
                $actionLink = $baseURL . "/supervisor_approve.php?id=" . $requestID . "&token=" . urlencode(scilab_approval_token($requestID, 'lab_personnel'));
                $personalizedBody = str_replace("[ActionLink]", $actionLink, $bodyTemplate);
                
                $mail->Body = $personalizedBody;

                $mail->send();
            } catch (Exception $e) {
                error_log("Admin email failed to {$admin['email']}: {$mail->ErrorInfo}");
            }
        }
    }
}

function sendNotificationToSubjectTeacher($conn, $requestID) {
    global $email_smtp_host, $email_smtp_user, $email_smtp_password, $email_smtp_secure, $email_smtp_port, $email_sender, $active_server;
    // Fetch request details
    $stmt = $conn->prepare("SELECT * FROM scilab_form_requests WHERE id = ?");
    $stmt->bind_param("i", $requestID);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = $res->fetch_assoc();
    $stmt->close();
    $data = scilab_label_row($conn, $data);

    if (!$data) return false;

    // Resolve active AUH email(s) for this request's subject (shared resolver).
    $auhEmails = scilab_resolve_auh_emails($conn, $data['subjectAcademicUnit'] ?? '', $data['gradeLevel'] ?? null);
    if (empty($auhEmails)) {
        $designation = 'AUH-' . ($data['subjectAcademicUnit'] ?? '');
        error_log("sendNotificationToSubjectTeacher: no active AUH email for request {$requestID} (subject: " . ($data['subjectAcademicUnit'] ?? '') . ", designation: " . ($designation ?? 'null') . ')');
        return false;
    }

    $requesterID = $data['requesterEmployeeID'];
    $requesterName = scilab_resolve_requester_name($conn, $requesterID);

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

    $subjectLine = "SciLab Request SLR-" . $requestID;
    $templatePath = __DIR__ . "/../templates/supervisor_request_email_template.html";

    if (file_exists($templatePath)) {
        $bodyTemplate = file_get_contents($templatePath);
    } else {
        $bodyTemplate = "A new request has been approved by the Supervisor and requires your approval. To view and approve the request, please click the button below.<br><br><a href='[ApprovalLink]' style='background-color: #4CAF50; color: white; padding: 14px 25px; text-align: center; text-decoration: none; display: inline-block;'>View Request Details</a><br><br><strong>Request Details:</strong><br>Facility: [Facility]<br>Requested By: [Requested By]<br>Date: [Start Date]<br>Time: [End Date]";
    }

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $baseURL = $protocol . $_SERVER['HTTP_HOST'] . "/" . $active_server;
    $approvalLink = $baseURL . "/supervisor_approve.php?id=" . $requestID;

    $replacements = [
        "[Request ID]" => "SLR-" . $requestID,
        "[Facility]" => $data['scilabName'],
        "[Grade Level]" => $data['gradeLevel'],
        "[Section]"          => $data['sections'],
        "[Subject]"          => $data['subject'],
        "[Concurrent Topic]" => $data['subjectTopic'],
        "[Unit]"             => ($data['subjectAcademicUnit'] ?? '') !== '' ? $data['subjectAcademicUnit'] : "N/A",
        "[Teacher Name]"     => $data['teacherInCharge'],
        "[Requested By]"     => $requesterName,
        "[Start Date]"       => $data['inclusiveDate'],
        "[End Date]"         => $data['inclusiveTime'],
        "[Materials]"        => $materialsStr,
        "[Group Members]"    => $studentsStr,
    ];

    foreach ($replacements as $key => $val) {
        $bodyTemplate = str_replace($key, htmlspecialchars($val), $bodyTemplate);
    }

    $sent = false;
    foreach (array_unique($auhEmails) as $email) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = $email_smtp_host;
                $mail->SMTPAuth = true;
                $mail->Username = $email_smtp_user;
                $mail->Password = $email_smtp_password;
                $mail->SMTPSecure = $email_smtp_secure;
                $mail->Port = $email_smtp_port;

                $mail->setFrom($email_sender, 'PSHS-IRC SciLab');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = $subjectLine;

                $approvalLink = $baseURL . "/supervisor_approve.php?id=" . $requestID . "&token=" . urlencode(scilab_approval_token($requestID, 'subject_teacher'));
                $personalizedBody = str_replace("[ApprovalLink]", $approvalLink, $bodyTemplate);
                $mail->Body = $personalizedBody;

                $mail->send();
                $sent = true;
            } catch (Exception $e) {
                error_log("AUH email failed to {$email}: {$mail->ErrorInfo}");
            }
        }
    }

    return $sent;
}

function sendNotificationToCIDChief($conn, $requestID) {
    global $email_smtp_host, $email_smtp_user, $email_smtp_password, $email_smtp_secure, $email_smtp_port, $email_sender, $active_server;

    // Fetch request details
    $stmt = $conn->prepare("SELECT * FROM scilab_form_requests WHERE id = ?");
    $stmt->bind_param("i", $requestID);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = $res->fetch_assoc();
    $stmt->close();
    $data = scilab_label_row($conn, $data);

    if (!$data) return;

    $requesterID = $data['requesterEmployeeID'];
    $requesterName = scilab_resolve_requester_name($conn, $requesterID);

    // Fetch materials
    $matStmt = $conn->prepare("SELECT quantity, unit, item, description FROM scilab_material_requests WHERE formID = ?");
    $matStmt->bind_param("i", $requestID);
    $matStmt->execute();
    $materials = [];
    $matResult = $matStmt->get_result();
    while ($row = $matResult->fetch_assoc()) {
        $materials[] = "{$row['quantity']} {$row['unit']} of {$row['item']} ({$row['description']})";
    }
    $materialsStr = implode("; ", $materials);
    $matStmt->close();

    // Fetch students
    $studStmt = $conn->prepare("SELECT student_name FROM scilab_students_involved WHERE formID = ?");
    $studStmt->bind_param("i", $requestID);
    $studStmt->execute();
    $students = [];
    $studResult = $studStmt->get_result();
    while ($row = $studResult->fetch_assoc()) {
        $students[] = $row['student_name'];
    }
    $studentsStr = implode(", ", $students);
    $studStmt->close();

    $cidChiefEmails = scilab_resolve_cid_chief_emails($conn);
    if (empty($cidChiefEmails)) {
        // Nobody can give final approval - escalate so the request does not stall.
        scilab_notify_unresolved_stage($conn, $data, 'cid_chief',
            'No active account holds the "CID Chief" designation for the current school year.');
        return;
    }

    $subjectLine = "SciLab Request SLR-" . $requestID;
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $baseURL = $protocol . $_SERVER['HTTP_HOST'] . "/" . $active_server;
    $approvalLink = $baseURL . "/supervisor_approve.php?id=" . $requestID . "&token=" . urlencode(scilab_approval_token($requestID, 'cid_chief'));

    $templatePath = __DIR__ . "/../templates/request_email_template.html";
    if (file_exists($templatePath)) {
        $bodyTemplate = file_get_contents($templatePath);
    } else {
        $bodyTemplate = "<p>A new request has passed Lab Personnel review and requires final CID Chief approval.</p>"
            . '<p><strong>Request ID:</strong> SLR-' . htmlspecialchars($requestID) . '</p>'
            . '<p><strong>Facility:</strong> ' . htmlspecialchars($data['scilabName']) . '<br>'
            . '<strong>Requested By:</strong> ' . htmlspecialchars($requesterName) . '<br>'
            . '<strong>Date/Time:</strong> ' . htmlspecialchars($data['inclusiveDate']) . ' ' . htmlspecialchars($data['inclusiveTime']) . '</p>'
            . '<p><a href="' . htmlspecialchars($approvalLink) . '">Review Request</a></p>';
    }

    $replacements = [
        "[Request ID]" => "SLR-" . $requestID,
        "[Facility]" => $data['scilabName'],
        "[Grade Level]" => $data['gradeLevel'],
        "[Section]" => $data['sections'],
        "[Subject]" => $data['subject'],
        "[Concurrent Topic]" => $data['subjectTopic'],
        "[Unit]" => ($data['subjectAcademicUnit'] ?? '') !== '' ? $data['subjectAcademicUnit'] : "N/A",
        "[Teacher Name]" => $data['teacherInCharge'],
        "[Requested By]" => $requesterName,
        "[Start Date]" => $data['inclusiveDate'],
        "[End Date]" => $data['inclusiveTime'],
        "[Materials]" => $materialsStr !== '' ? $materialsStr : 'N/A',
        "[Group Members]" => $studentsStr !== '' ? $studentsStr : 'N/A',
    ];

    foreach ($replacements as $key => $val) {
        $bodyTemplate = str_replace($key, htmlspecialchars((string)$val), $bodyTemplate);
    }
    $bodyTemplate = str_replace("[ActionLink]", htmlspecialchars($approvalLink), $bodyTemplate);

    foreach ($cidChiefEmails as $chiefEmail) {
        $admin = ['email' => $chiefEmail];
        if (filter_var($admin['email'], FILTER_VALIDATE_EMAIL)) {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = $email_smtp_host;
                $mail->SMTPAuth = true;
                $mail->Username = $email_smtp_user;
                $mail->Password = $email_smtp_password;
                $mail->SMTPSecure = $email_smtp_secure;
                $mail->Port = $email_smtp_port;

                $mail->setFrom($email_sender, 'PSHS-IRC SciLab');
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

/**
 * Email whoever must act on $stage of this request, following the request's flow
 * (see helperFiles/stage_flow.php):
 *   - supervisor                      -> the teacher in charge
 *   - subject_teacher (new flow)      -> the Subject Teacher the requester selected
 *   - subject_teacher (legacy flow)   -> the Area Unit Head
 *   - lab_personnel / cid_chief       -> their groups
 * When nobody can be resolved the stage stays pending and Lab Personnel is alerted.
 * Returns true when an approver was emailed.
 */
function scilab_dispatch_stage_notification($conn, $requestID, $stage)
{
    $stmt = $conn->prepare("SELECT * FROM scilab_form_requests WHERE id = ?");
    $stmt->bind_param("i", $requestID);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$data) return false;
    $data = scilab_label_row($conn, $data);

    $newFlow = scilab_is_subject_teacher_flow($data);

    if ($stage === 'supervisor' || ($stage === 'subject_teacher' && $newFlow)) {
        $names = $stage === 'supervisor' ? (string)($data['teacherInCharge'] ?? '') : (string)($data['subjectTeacher'] ?? '');
        $emails = scilab_resolve_teacher_in_charge_emails($conn, $names);
        if (empty($emails)) {
            // No account matched the name. Leave the stage pending and escalate rather
            // than letting the request stall unnoticed.
            scilab_notify_unresolved_stage($conn, $data, $stage,
                'No active account matched the ' . scilab_stage_label($data, $stage) . ' "' . $names . '".');
            return false;
        }

        $materials = [];
        $matStmt = $conn->prepare("SELECT quantity, unit, item, description FROM scilab_material_requests WHERE formID = ?");
        $matStmt->bind_param("i", $requestID);
        $matStmt->execute();
        $matRes = $matStmt->get_result();
        while ($row = $matRes->fetch_assoc()) {
            $materials[] = "{$row['quantity']} {$row['unit']} of {$row['item']} ({$row['description']})";
        }
        $matStmt->close();

        $students = [];
        $studStmt = $conn->prepare("SELECT student_name FROM scilab_students_involved WHERE formID = ?");
        $studStmt->bind_param("i", $requestID);
        $studStmt->execute();
        $studRes = $studStmt->get_result();
        while ($row = $studRes->fetch_assoc()) {
            $students[] = $row['student_name'];
        }
        $studStmt->close();

        sendSubmissionNotificationToSupervisors($conn, [
            'scilabName' => $data['scilabName'],
            'gradeLevel' => $data['gradeLevel'],
            'section' => $data['sections'],
            'subject' => $data['subject'],
            'topic' => $data['subjectTopic'],
            'unit' => ($data['subjectAcademicUnit'] ?? '') !== '' ? $data['subjectAcademicUnit'] : 'N/A',
            'teacher' => $stage === 'supervisor' ? $names : (string)($data['teacherInCharge'] ?? ''),
            'inclusiveDate' => $data['inclusiveDate'],
            'inclusiveTime' => $data['inclusiveTime'],
            'materials' => implode("; ", $materials),
            'students' => implode(", ", $students),
            'requester' => scilab_resolve_requester_name($conn, $data['requesterEmployeeID'] ?? '')
        ], $emails, $requestID, $stage);
        return true;
    }

    if ($stage === 'subject_teacher') {
        if (!sendNotificationToSubjectTeacher($conn, $requestID)) {
            // No AUH resolvable for this academic unit. Keep the stage pending and
            // escalate to Lab Personnel instead of silently auto-approving it.
            scilab_notify_unresolved_stage($conn, $data, 'subject_teacher',
                'No active account is designated as AUH for academic unit "'
                . (($data['subjectAcademicUnit'] ?? '') !== '' ? $data['subjectAcademicUnit'] : 'N/A') . '".');
            return false;
        }
        return true;
    }

    if ($stage === 'lab_personnel') {
        sendNotificationToAdmins($conn, $requestID);
        return true;
    }

    if ($stage === 'cid_chief') {
        sendNotificationToCIDChief($conn, $requestID);
        return true;
    }

    return false;
}

if (isset($_POST["action"]) && $_POST["action"] == "request_submission") {
    ral_require_login();
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!is_string($csrfToken) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Invalid or missing security token. Please refresh the page and try again.']);
        exit();
    }

    $scilabName = $_POST['venue'] ?? '';
    $grade = intval($_POST['grade_level'] ?? 0);
    
    $sectionsInput = $_POST['sections'] ?? '';
    if (is_array($sectionsInput)) {
        $sections = implode(', ', array_filter($sectionsInput, 'is_string'));
    } else {
        $sections = is_string($sectionsInput) ? $sectionsInput : '';
    }

    $subject = $_POST['subject'] ?? '';
    $topic = $_POST['topic'] ?? '';
    $unit = is_string($_POST['unit'] ?? '') ? ($_POST['unit'] ?? '') : '';

    $teacherInput = $_POST['teacher'] ?? [];
    $teachers = is_array($teacherInput)
        ? $teacherInput
        : [$teacherInput];

    $teachers = array_filter($teachers, 'is_string');
    $teachers = array_map('trim', $teachers);
    $teachers = array_filter($teachers, function ($teacherName) {
        return $teacherName !== '';
    });

    $teacher = implode(', ', $teachers);

    // One or more days (forms.php sends inclusive_dates as a JSON list; inclusive_date is the old single field)
    $requestDates = scilab_normalize_dates($_POST['inclusive_dates'] ?? ($_POST['inclusive_date'] ?? ''));
    if (!$requestDates) {
        echo "Please select at least one valid date.";
        exit();
    }
    if (count($requestDates) > SCILAB_MAX_REQUEST_DATES) {
        echo "A request can cover at most " . SCILAB_MAX_REQUEST_DATES . " dates.";
        exit();
    }
    $startDate = $requestDates[0]; // inclusiveDate holds the earliest day
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

    // Refuse a slot an approved request already holds (approval checks again under a lock)
    $slotConflict = scilab_find_slot_conflict($conn, $scilabName, $requestDates, $formattedTime);
    if ($slotConflict) {
        echo $scilabName . " is already booked on " . date('F j, Y', strtotime($slotConflict['conflictDate'])) . " from " . $slotConflict['inclusiveTime'] . ". Please choose another time or laboratory.";
        exit();
    }

    // Decode merged materials early so stock can be validated before a request row is created
    $mergedMaterials = [];
    if (isset($_POST['mergedMaterials'])) {
        $decoded = json_decode($_POST['mergedMaterials'], true);
        if (is_array($decoded)) {
            $mergedMaterials = $decoded;
        }
    }

    foreach ($mergedMaterials as $mat) {
        $item = trim((string)($mat['item'] ?? ''));
        $qty = isset($mat['qty']) ? (int)$mat['qty'] : 1;
        $desc = trim((string)($mat['desc'] ?? ''));
        if ($item === '' || $qty < 1) continue;

        [$available, $class] = scilab_available_quantity($conn, $item, $desc);
        if ($available !== null && $qty > $available) {
            echo "Insufficient stock for " . $item . " (available: " . $available . "). Please adjust the requested quantity.";
            exit();
        }
    }

    $syResult = $conn->query("SELECT value FROM current WHERE description = 'School Year' ORDER BY id DESC LIMIT 1");
    $schoolYear = ($syResult && $syResult->num_rows > 0) ? $syResult->fetch_assoc()['value'] : 'N/A';

    $requesterID = $_POST['employee_id'] ?? $_SESSION['employeeID'] ?? $_SESSION['student_lrn'] ?? $_SESSION['email'] ?? $_SESSION['username'] ?? '';
    $dateRequested = date('Y-m-d H:i:s');

    $isFacultyOrSysadmin = scilab_is_faculty_requester($conn, $requesterID);
    if (!$isFacultyOrSysadmin && !empty($_SESSION['email'])) {
        $typeStmt = $conn->prepare("SELECT type, position FROM accounts WHERE email = ? LIMIT 1");
        if ($typeStmt) {
            $typeStmt->bind_param("s", $_SESSION['email']);
            $typeStmt->execute();
            if ($typeRow = $typeStmt->get_result()->fetch_assoc()) {
                $accountType = strtolower(trim($typeRow['type'] ?? ''));
                $accountPos = strtolower(trim($typeRow['position'] ?? ''));
                if ($accountType === 'faculty' || $accountType === 'sysadmin' || $accountPos === 'teacher' || (!empty($accountType) && $accountType !== 'student')) {
                    $isFacultyOrSysadmin = true;
                }
            }
            $typeStmt->close();
        }
    }

    // Subject teacher is collected for student requests only (they approve first, then the
    // teacher in charge). Faculty requests keep '' and the legacy stage order.
    $subjectTeacher = '';
    if (!$isFacultyOrSysadmin) {
        $subjectTeacherInput = $_POST['subject_teacher'] ?? '';
        if (is_array($subjectTeacherInput)) {
            $subjectTeacherInput = reset($subjectTeacherInput);
        }
        $subjectTeacher = is_string($subjectTeacherInput) ? trim($subjectTeacherInput) : '';
        if ($subjectTeacher === '') {
            echo "Please select a Subject Teacher.";
            exit();
        }
        if (strlen($subjectTeacher) > 255) {
            echo "The Subject Teacher name is too long.";
            exit();
        }
    }

    $statusScilabPersonnel = 'Pending';
    $initialLabPersonnelStatus = 'pending';
    $initialCidChiefStatus = $isFacultyOrSysadmin ? 'approved' : 'pending';
    $initialSubjectTeacherStatus = 'pending';

    $initialSupervisorStatus = $isFacultyOrSysadmin ? 'approved' : 'pending';

    $requesterNameInitial = scilab_resolve_requester_name($conn, $requesterID);

    $stmt = $conn->prepare("INSERT INTO scilab_form_requests (
        scilabName,
        gradeLevel,
        sections,
        subject,
        subjectAcademicUnit,
        subjectTopic,
        inclusiveDate,
        inclusiveTime,
        dateRequested,
        requesterEmployeeID,
        sy,
        teacherInCharge,
        subjectTeacher,
        statusScilabPersonnel,
        supervisor_status,
        subject_teacher_status,
        lab_personnel_status,
        cid_chief_status,
        supervisor_approved_at,
        supervisor_approved_by,
        cid_chief_approved_at,
        cid_chief_approved_by
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $initialSupervisorApprovedAt = $isFacultyOrSysadmin ? date('Y-m-d H:i:s') : null;
    $initialSupervisorApprovedBy = $isFacultyOrSysadmin ? $requesterNameInitial : null;
    $initialCidChiefApprovedAt = $isFacultyOrSysadmin ? date('Y-m-d H:i:s') : null;
    $initialCidChiefApprovedBy = $isFacultyOrSysadmin ? 'Auto-approved (Teacher request)' : null;

    $stmt->bind_param(
        "sissssssssssssssssssss",
        $scilabName,
        $grade,
        $sections,
        $subject,
        $unit,
        $topic,
        $startDate,
        $formattedTime,
        $dateRequested,
        $requesterID,
        $schoolYear,
        $teacher,
        $subjectTeacher,
        $statusScilabPersonnel,
        $initialSupervisorStatus,
        $initialSubjectTeacherStatus,
        $initialLabPersonnelStatus,
        $initialCidChiefStatus,
        $initialSupervisorApprovedAt,
        $initialSupervisorApprovedBy,
        $initialCidChiefApprovedAt,
        $initialCidChiefApprovedBy
    );

    if (!$stmt->execute()) {
        echo "error";
        exit();
    }

    $formID = $stmt->insert_id;

    if (!scilab_save_request_dates($conn, $formID, $requestDates)) {
        // Do not leave a request that the conflict checks cannot see
        $conn->query("DELETE FROM scilab_form_requests WHERE id = " . (int) $formID);
        echo "error";
        exit();
    }

    $materials = [];
    $stmt2 = $conn->prepare("INSERT INTO scilab_material_requests (formID, item, quantity, unit, description) VALUES (?, ?, ?, ?, ?)");

    if (is_array($mergedMaterials)) {
        foreach ($mergedMaterials as $mat) {
            $item = trim((string)($mat['item'] ?? ''));
            $matUnit = trim((string)($mat['unit'] ?? 'N/A'));
            $desc = trim((string)($mat['desc'] ?? 'N/A'));
            $qty = isset($mat['qty']) ? (int)$mat['qty'] : 1;
            if ($item !== '') {
                $stmt2->bind_param("isiss", $formID, $item, $qty, $matUnit, $desc);
                $stmt2->execute();
                $materials[] = "{$qty} {$matUnit} of {$item} ({$desc})";
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

    $requesterName = scilab_resolve_requester_name($conn, $requesterID);
    if (empty(trim($requesterName)) || $requesterName === $requesterID) {
        $requesterName = trim(($_SESSION['firstname'] ?? '') . ' ' . ($_SESSION['middlename'] ?? '') . ' ' . ($_SESSION['lastname'] ?? ''));
    }

    $reqStmt = $conn->prepare("SELECT * FROM scilab_form_requests WHERE id = ?");
    $reqStmt->bind_param("i", $formID);
    $reqStmt->execute();
    $reqRow = $reqStmt->get_result()->fetch_assoc();
    $reqStmt->close();

    // Student requests (subject-teacher flow): email the Subject Teacher first. Legacy student
    // rows would email the Teacher-in-Charge; scilab_current_stage() picks the right first stage.
    // Teacher requests: Supervisor stage is auto-approved at submit time, so the submission
    // immediately emails the next action taker (AUH).
    // Send a confirmation email to the requester once the request is recorded.
    scilab_send_submission_confirmation($conn, $formID);

    if ($reqRow) {
        $firstStage = scilab_current_stage($reqRow);
        if ($firstStage !== '') {
            scilab_dispatch_stage_notification($conn, $formID, $firstStage);
        }
    }

    echo "success";
    $stmt->close();
    $stmt2->close();
    exit();
}

header('Content-Type: application/json');

// Approvals arrive as JSON from supervisor_approve.php, which sends the CSRF token as a header.
// Visitors with only an emailed link have a session too, so the token applies to them as well.
ral_require_csrf();

$username = $_SESSION['username'] ?? null;

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

// A request that already reached a terminal state must not be advanced further.
// Without this guard a rejected request (whose downstream stage columns are still
// 'pending') can be approved from a stale approval link or dashboard.
$overallStatus = strtolower(trim((string)($request['statusScilabPersonnel'] ?? '')));
if ($action !== 'force_approve_override' && in_array($overallStatus, ['approved', 'rejected', 'cancelled'], true)) {
    echo json_encode([
        'success' => false,
        'message' => 'This request is already ' . ucfirst($overallStatus) . ' and cannot be actioned again.'
    ]);
    exit;
}

// Determine which column to update based on current status flow
// The order comes from helperFiles/stage_flow.php:
//   subject-teacher flow (students): Subject Teacher -> Teacher in Charge -> Lab Personnel -> CID Chief
//   legacy flow: Supervisor -> Area Unit Head (AUH) -> Lab Personnel -> CID Chief
$fieldPrefix = '';

if ($action === 'force_approve_override') {
    $fieldPrefix = 'force_approve';
} else {
    $fieldPrefix = scilab_current_stage($request);
    if ($fieldPrefix === '') {
        echo json_encode(['success' => false, 'message' => 'No pending approval stage found or request is already completed.']);
        exit;
    }
}

/**
 * May the logged-in user act on this approval stage? Mirrors supervisor_approve.php:
 * supervisor = the teacher in charge, subject teacher = the selected Subject Teacher
 * (new flow) or an AUH / teacher position (legacy flow), lab personnel = a lab
 * personnel position, CID chief = a chief position. Force approval is admin-only.
 */
function ral_session_can_act_on_stage($conn, $stage, array $request)
{
    global $accepted_admin_roles;

    if (!ral_is_logged_in()) {
        return false;
    }
    if ($stage === 'force_approve') {
        return $_SESSION['role'] === 'admin';
    }

    $employeeID = (string)($_SESSION['employeeID'] ?? '');
    if ($employeeID === '') {
        return false;
    }
    $res = ral_query($conn, "SELECT firstname, middlename, lastname, position FROM accounts WHERE employeeID = ? AND status = 'active'", 's', [$employeeID]);
    $user = $res ? $res->fetch_assoc() : null;
    if (!$user) {
        return false;
    }
    $position = (string)($user['position'] ?? '');

    switch ($stage) {
        case 'supervisor':
            return scilab_name_matches_user($user, $request['teacherInCharge'] ?? '');
        case 'subject_teacher':
            if (scilab_is_subject_teacher_flow($request)) {
                // New flow: only the Subject Teacher the requester selected may act.
                return scilab_name_matches_user($user, $request['subjectTeacher'] ?? '');
            }
            return strpos(strtolower($position), 'teacher') !== false
                || scilab_user_holds_designation($conn, $employeeID, 'AUH-' . ($request['subjectAcademicUnit'] ?? ''));
        case 'lab_personnel':
            return in_array($position, $accepted_admin_roles, true);
        case 'cid_chief':
            return strpos(strtolower($position), 'chief') !== false
                || scilab_user_holds_designation($conn, $employeeID, 'CID Chief');
    }
    return false;
}

// Authorize: a session whose user holds the role for this stage, OR a valid passwordless approval token for it.
$authorized = false;
if ($username !== null && ral_session_can_act_on_stage($conn, $fieldPrefix, $request)) {
    $authorized = true;
} elseif (isset($input['token']) && $fieldPrefix !== 'force_approve' && scilab_approval_token_valid($requestId, $fieldPrefix, $input['token'])) {
    $authorized = true;
}
if (!$authorized) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Resolve the current approver's display name for audit/PDF display.
$approverName = trim(($_SESSION['firstname'] ?? '') . ' ' . ($_SESSION['middlename'] ?? '') . ' ' . ($_SESSION['lastname'] ?? ''));
if ($approverName === '') {
    $approverName = trim((string)($_SESSION['username'] ?? ''));
}
if ($approverName === '' && !empty($_SESSION['employeeID'])) {
    $nameStmt = $conn->prepare("SELECT firstname, middlename, lastname FROM accounts WHERE employeeID = ? LIMIT 1");
    if ($nameStmt) {
        $nameStmt->bind_param("s", $_SESSION['employeeID']);
        $nameStmt->execute();
        if ($nameRow = $nameStmt->get_result()->fetch_assoc()) {
            $approverName = trim(($nameRow['firstname'] ?? '') . ' ' . ($nameRow['middlename'] ?? '') . ' ' . ($nameRow['lastname'] ?? ''));
        }
        $nameStmt->close();
    }
}
if ($approverName === '') {
    $approverName = ucwords(str_replace('_', ' ', $fieldPrefix)) . ' (via approval link)';
}

// Determine which column to update based on current status flow
if ($fieldPrefix === 'force_approve') {
    $sql = "UPDATE scilab_form_requests SET statusScilabPersonnel = 'Approved', supervisor_status = 'approved', subject_teacher_status = 'approved', lab_personnel_status = 'approved', cid_chief_status = 'approved'";
    $params = [];
    $types = "";

    // Record timestamp + approver for all four stages (admin force approval).
    foreach (['supervisor', 'subject_teacher', 'lab_personnel', 'cid_chief'] as $stage) {
        $sql .= ", {$stage}_approved_at = NOW()";
        $sql .= ", {$stage}_approved_by = ?";
        $params[] = $approverName;
        $types .= "s";
    }

    if ($action === 'force_approve_override' && $reason !== null) {
        $sql .= ", feedback = ?";
        $params[] = $reason;
        $types .= "s";
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $requestId;
    $types .= "i";
}
else {
    $statusColumn = $fieldPrefix . '_status';
    $newStatus = ($action === 'approve') ? 'approved' : 'rejected';

    // Prepare SQL
    $sql = "UPDATE scilab_form_requests SET $statusColumn = ?";
    $params = [$newStatus];
    $types = "s";
    
    // If rejected at any stage, mark the whole request as Rejected
    if ($action === 'reject') {
        $sql .= ", statusScilabPersonnel = 'Rejected'";
        $sql .= ", controlNumber = NULL, control_equipment = NULL, control_reagent = NULL, control_permit = NULL, control_reservation = NULL";

        // Close out every stage at or after the one that rejected. Leaving them
        // 'pending' would let the request be advanced again from a stale link and
        // would show a still-actionable step in supervisor_approve.php.
        foreach (scilab_stages_from($request, $fieldPrefix) as $stage) {
            if ($stage === $fieldPrefix) continue;
            $sql .= ", {$stage}_status = IF({$stage}_status = 'pending', 'rejected', {$stage}_status)";
        }

        // Record who rejected, for the audit trail.
        $sql .= ", {$fieldPrefix}_approved_at = NOW()";
        $sql .= ", {$fieldPrefix}_approved_by = ?";
        $params[] = $approverName . ' (rejected)';
        $types .= "s";
    } elseif ($action === 'approve' && $fieldPrefix === 'cid_chief') {
        // If final stage approved, mark the whole request as Approved
        $sql .= ", statusScilabPersonnel = 'Approved'";
    }

    // When approving this stage, record the timestamp + approver name.
    if ($action === 'approve') {
        $sql .= ", {$fieldPrefix}_approved_at = NOW()";
        $sql .= ", {$fieldPrefix}_approved_by = ?";
        $params[] = $approverName;
        $types .= "s";
    }

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

// For teachers, Lab Personnel is the second and last approval needed. CID Chief is bypassed.
$isTeacherReq = ($fieldPrefix === 'lab_personnel' && $action === 'approve')
    && scilab_is_faculty_requester($conn, $request['requesterEmployeeID'] ?? '');
$isFinalApproval = $fieldPrefix === 'force_approve'
    || ($action === 'approve' && ($fieldPrefix === 'cid_chief' || $isTeacherReq));

if ($isFinalApproval) {
    // Slot check, status update and inventory deduction in one transaction
    $approval = scilab_approve_request($conn, $requestId, function ($conn) use ($updateStmt, $isTeacherReq, $requestId) {
        if (!$updateStmt->execute()) {
            return false;
        }
        if ($isTeacherReq) {
            $finalStmt = $conn->prepare("UPDATE scilab_form_requests SET statusScilabPersonnel = 'Approved', cid_chief_status = 'approved', cid_chief_approved_at = NOW(), cid_chief_approved_by = 'Auto-approved (Teacher request)' WHERE id = ?");
            if ($finalStmt) {
                $finalStmt->bind_param("i", $requestId);
                $finalStmt->execute();
                $finalStmt->close();
            }
        }
        return true;
    });
    $updated = $approval['ok'];
} else {
    $updated = $updateStmt->execute();
}

if ($updated) {
    // Check if this is the final approval (CID Chief or Force Approve)
    if (($fieldPrefix === 'cid_chief' && $action === 'approve') || $fieldPrefix === 'force_approve') {
        scilab_notify_stage_status($conn, $request, $fieldPrefix, 'approve');
    } elseif ($action === 'approve') {
        // Intermediate approval: tell the requester / earlier approvers, then email the
        // next stage in this request's order that is still pending (a teacher request's
        // CID Chief stage is auto-approved, so nothing follows Lab Personnel there).
        scilab_notify_stage_status($conn, $request, $fieldPrefix, 'approve');
        if (!$isTeacherReq) {
            $nextStage = '';
            foreach (scilab_stages_from($request, $fieldPrefix) as $candidate) {
                if ($candidate === $fieldPrefix) continue;
                if (strtolower((string)($request[$candidate . '_status'] ?? 'pending')) === 'pending') {
                    $nextStage = $candidate;
                    break;
                }
            }
            if ($nextStage !== '') {
                scilab_dispatch_stage_notification($conn, $requestId, $nextStage);
            }
        }
    } elseif ($action === 'reject') {
        scilab_notify_stage_status($conn, $request, $fieldPrefix, 'reject', $reason);
    }

    echo json_encode(['success' => true]);
} elseif ($isFinalApproval && $approval['reason'] !== 'update_failed') {
    echo json_encode(['success' => false, 'message' => $approval['message']]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database update failed: ' . $updateStmt->error]);
}

$updateStmt->close();
$conn->close();
?>