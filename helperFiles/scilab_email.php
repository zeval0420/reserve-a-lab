<?php
/**
 * Shared email helper functions for SciLab request notifications.
 *
 * Requires the following to be included before this file:
 *   - db_connection.php   ($conn)
 *   - variableDeclarations.php  ($active_server, scilab_auh_designation(), etc.)
 *   - PHPMailer classes loaded
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

function scilab_resolve_requester_email($conn, $requesterID) {
    $requesterID = trim((string)$requesterID);

    error_log("SciLab DEBUG - requesterEmployeeID received: [" . $requesterID . "]");

    if ($requesterID === '') {
        error_log("SciLab DEBUG - requesterEmployeeID is empty.");
        return null;
    }

    // If the identifier is itself an email address, use it directly.
    if (filter_var($requesterID, FILTER_VALIDATE_EMAIL)) {
        error_log("SciLab DEBUG - ID is already an email: [" . $requesterID . "]");
        return $requesterID;
    }

    // Faculty / personnel accounts.
    $stmt = $conn->prepare("SELECT email FROM accounts WHERE employeeID = ?");
    if ($stmt) {
        $stmt->bind_param("s", $requesterID);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $email = trim($row['email'] ?? '');

            error_log("SciLab DEBUG - accounts lookup found email: [" . $email . "]");

            if ($email !== '') {
                $stmt->close();
                return $email;
            }
        } else {
            error_log("SciLab DEBUG - No matching accounts.employeeID.");
        }

        $stmt->close();
    }

    // Students.
    $stmt = $conn->prepare("
        SELECT d.studentEmail AS email
        FROM student_directory d
        JOIN student s ON d.LRN = s.LRN
        WHERE s.LRN = ?
    ");

    if ($stmt) {
        $stmt->bind_param("s", $requesterID);
        $stmt->execute();

        $result = $stmt->get_result();

        error_log("SciLab DEBUG - Student lookup returned " . $result->num_rows . " row(s).");

        if ($row = $result->fetch_assoc()) {
            $email = trim($row['email'] ?? '');

            error_log("SciLab DEBUG - Student email found: [" . $email . "]");

            if ($email !== '') {
                $stmt->close();
                return $email;
            }
        } else {
            error_log("SciLab DEBUG - No matching student LRN.");
        }

        $stmt->close();
    }

    error_log("SciLab DEBUG - Email resolution FAILED for ID: [" . $requesterID . "]");

    return null;
}

function scilab_resolve_teacher_in_charge_emails($conn, $teacherInCharge) {
    $emails = [];
    if (empty($teacherInCharge)) return $emails;
    $stmt = $conn->prepare("SELECT email, TRIM(CONCAT(lastname, ', ', firstname, ' ', IFNULL(middlename, ''))) AS fullname FROM accounts WHERE status = 'active'");
    if (!$stmt) return $emails;
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $fn = trim($row['fullname'] ?? '');
        if ($fn !== '' && stripos($teacherInCharge, $fn) !== false) {
            $emails[] = $row['email'];
        }
    }
    $stmt->close();
    return array_unique($emails);
}

function scilab_resolve_auh_emails($conn, $unit, $gradeLevel = null) {
    $designation = 'AUH-' . $unit;

    $syResult = $conn->query("SELECT value FROM current WHERE description = 'School Year' ORDER BY id DESC LIMIT 1");
    $sy = ($syResult && $syResult->num_rows > 0) ? $syResult->fetch_assoc()['value'] : null;
    if (!$sy) return [];

    $emails = [];
    $auhStmt = $conn->prepare("SELECT DISTINCT employeeID FROM designation WHERE sy = ? AND designation = ?");
    if (!$auhStmt) return $emails;

    $auhStmt->bind_param("ss", $sy, $designation);
    $auhStmt->execute();
    $auhRes = $auhStmt->get_result();

    while ($auh = $auhRes->fetch_assoc()) {
        $emp = $conn->prepare("SELECT email FROM accounts WHERE employeeID = ? AND status = 'active'");
        if ($emp) {
            $emp->bind_param("s", $auh['employeeID']);
            $emp->execute();

            if ($row = $emp->get_result()->fetch_assoc()) {
                $emails[] = $row['email'];
            }

            $emp->close();
        }
    }

    $auhStmt->close();

    return array_unique($emails);
}

function scilab_resolve_lab_personnel_emails($conn) {
    $emails = [];
    $res = $conn->query("SELECT email FROM accounts WHERE status = 'active' AND (position = 'Sci. Res. Assist.' OR position = 'Sci. Research Specialist I')");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            if (!empty($row['email'])) $emails[] = $row['email'];
        }
    }
    return array_unique($emails);
}

function scilab_resolve_cid_chief_emails($conn) {
    $emails = [];
    $res = $conn->query("SELECT email FROM accounts WHERE status = 'active' AND position LIKE '%Chief%'");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            if (!empty($row['email'])) $emails[] = $row['email'];
        }
    }
    return array_unique($emails);
}

function scilab_send_submission_confirmation($conn, $requestId) {
    $requestId = intval($requestId);
    if ($requestId <= 0) return;

    $stmt = $conn->prepare("SELECT * FROM scilab_form_requests WHERE id = ?");
    if (!$stmt) return;
    $stmt->bind_param("i", $requestId);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$data) return;

    $requesterEmail = scilab_resolve_requester_email($conn, $data['requesterEmployeeID'] ?? '');
    if (!$requesterEmail && !empty($_SESSION['email'])) {
        $requesterEmail = trim($_SESSION['email']);
    }
    if (!$requesterEmail) return;

    // Resolve the requester display name (accounts first, then student table).
    $requesterName = $data['requesterEmployeeID'] ?? '';
    $nameStmt = $conn->prepare("SELECT firstname, middlename, lastname FROM accounts WHERE employeeID = ?");
    if ($nameStmt) {
        $nameStmt->bind_param("s", $requesterName);
        $nameStmt->execute();
        $row = $nameStmt->get_result()->fetch_assoc();
        $nameStmt->close();
        if ($row) {
            $requesterName = trim(($row['firstname'] ?? '') . ' ' . ($row['middlename'] ?? '') . ' ' . ($row['lastname'] ?? ''));
        }
    }
    if ($requesterName === ($data['requesterEmployeeID'] ?? '')) {
        $nameStmt = $conn->prepare("SELECT firstname, middlename, lastname FROM student WHERE LRN = ?");
        if ($nameStmt) {
            $requesterLRN = (string)($data['requesterEmployeeID'] ?? '');
            $nameStmt->bind_param("s", $requesterLRN);
            $nameStmt->execute();
            $row = $nameStmt->get_result()->fetch_assoc();
            $nameStmt->close();
            if ($row) {
                $requesterName = trim(($row['firstname'] ?? '') . ' ' . ($row['middlename'] ?? '') . ' ' . ($row['lastname'] ?? ''));
            }
        }
    }

    // Fetch requested materials.
    $materialsStr = '';
    $matStmt = $conn->prepare("SELECT quantity, unit, item, description FROM scilab_material_requests WHERE formID = ?");
    if ($matStmt) {
        $matStmt->bind_param("i", $requestId);
        $matStmt->execute();
        $materials = [];
        while ($row = $matStmt->get_result()->fetch_assoc()) {
            $materials[] = "{$row['quantity']} {$row['unit']} of {$row['item']} ({$row['description']})";
        }
        $matStmt->close();
        $materialsStr = implode('; ', $materials);
    }

    // Fetch participating students.
    $studentsStr = '';
    $studStmt = $conn->prepare("SELECT student_name FROM scilab_students_involved WHERE formID = ?");
    if ($studStmt) {
        $studStmt->bind_param("i", $requestId);
        $studStmt->execute();
        $students = [];
        while ($row = $studStmt->get_result()->fetch_assoc()) {
            $students[] = $row['student_name'];
        }
        $studStmt->close();
        $studentsStr = implode(', ', $students);
    }

    $templatePath = __DIR__ . '/../templates/request_confirmation_email_template.html';
    if (file_exists($templatePath)) {
        $bodyTemplate = file_get_contents($templatePath);
    } else {
        $bodyTemplate = '<p>Your laboratory request <strong>[Request ID]</strong> has been received and is now pending approval.</p>'
            . '<p><strong>Facility:</strong> [Facility]<br>'
            . '<strong>Requested By:</strong> [Requested By]<br>'
            . '<strong>Date/Time:</strong> [Start Date] [End Date]</p>'
            . '<p>You can track the progress of this request here: <a href="[TrackLink]">View Request Status</a></p>';
    }

    $replacements = [
        '[Request ID]' => 'SLR-' . $requestId,
        '[Facility]' => $data['scilabName'],
        '[Grade Level]' => $data['gradeLevel'],
        '[Section]' => $data['sections'],
        '[Subject]' => $data['subject'],
        '[Concurrent Topic]' => $data['subjectTopic'],
        '[Unit]' => $data['subjectAcademicUnit'] ?? 'N/A',
        '[Teacher Name]' => $data['teacherInCharge'],
        '[Requested By]' => $requesterName,
        '[Start Date]' => $data['inclusiveDate'],
        '[End Date]' => $data['inclusiveTime'],
        '[Materials]' => $materialsStr !== '' ? $materialsStr : 'N/A',
        '[Group Members]' => $studentsStr !== '' ? $studentsStr : 'N/A',
    ];

    foreach ($replacements as $key => $val) {
        $bodyTemplate = str_replace($key, htmlspecialchars((string)$val), $bodyTemplate);
    }

    global $active_server;
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
    $baseURL = $protocol . ($_SERVER['HTTP_HOST'] ?? '') . '/' . ($active_server ?? '');
    $trackLink = $baseURL . '/supervisor_approve.php?id=' . $requestId;
    $bodyTemplate = str_replace('[TrackLink]', htmlspecialchars($trackLink), $bodyTemplate);

    scilab_send_status_email([$requesterEmail], 'SciLab Request SLR-' . $requestId, $bodyTemplate);
}

function scilab_send_status_email($emails, $subject, $bodyHtml) {
    global $email_smtp_host, $email_smtp_user, $email_smtp_password, $email_smtp_secure, $email_smtp_port, $email_sender;

    if (empty($emails)) return;
    $emails = array_unique(array_filter($emails, function ($e) {
        return filter_var($e, FILTER_VALIDATE_EMAIL);
    }));
    if (empty($emails)) return;

    foreach ($emails as $email) {
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
            $mail->Subject = $subject;
            $mail->Body = $bodyHtml;
            $mail->send();
        } catch (Exception $e) {
            error_log("Status email failed to {$email}: " . ($mail->ErrorInfo ?? $e->getMessage()));
        }
    }
}

/**
 * Notify the requester (student) and every prerequisite approver whose stage is
 * already approved about the current stage's action.
 *
 * All emails use the unified subject "SciLab Request SLR-[id]" so that
 * notifications for the same request stack in a single email thread per recipient.
 *
 * $currentStage: 'supervisor' | 'subject_teacher' | 'lab_personnel' | 'cid_chief' | 'force_approve'
 * $event: 'approve' | 'reject'
 */
function scilab_notify_stage_status($conn, $request, $currentStage, $event, $reason = null) {
    global $active_server;

    $id = $request['id'] ?? 0;
    $stageOrder = ['supervisor' => 0, 'subject_teacher' => 1, 'lab_personnel' => 2, 'cid_chief' => 3];
    $isForce = ($currentStage === 'force_approve');
    $currIdx = $isForce ? 999 : ($stageOrder[$currentStage] ?? -1);

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $baseURL = $protocol . $_SERVER['HTTP_HOST'] . "/" . $active_server;
    $trackerLink = $baseURL . "/supervisor_approve.php?id=" . $id;

    $stageLabels = [
        'supervisor' => 'Supervisor',
        'subject_teacher' => 'Area Unit Head (AUH)',
        'lab_personnel' => 'Lab Personnel',
        'cid_chief' => 'CID Chief',
        'force_approve' => 'Administrator (Force Approval)',
    ];
    $stageLabel = $stageLabels[$currentStage] ?? ucwords(str_replace('_', ' ', $currentStage));

    // Resolve requester display name
    $requesterName = $request['requesterEmployeeID'] ?? '';
    $nameStmt = $conn->prepare("SELECT firstname, middlename, lastname FROM accounts WHERE employeeID = ?");
    if ($nameStmt) {
        $nameStmt->bind_param("s", $requesterName);
        $nameStmt->execute();
        if ($row = $nameStmt->get_result()->fetch_assoc()) {
            $requesterName = trim(($row['firstname'] ?? '') . ' ' . ($row['middlename'] ?? '') . ' ' . ($row['lastname'] ?? ''));
        }
        $nameStmt->close();
    }
    if ($requesterName === $request['requesterEmployeeID']) {
        $nameStmt = $conn->prepare("SELECT firstname, middlename, lastname FROM student WHERE LRN = ?");
        if ($nameStmt) {
            $requesterLRN = (string)$request['requesterEmployeeID'];
            $nameStmt->bind_param("s", $requesterLRN);
            $nameStmt->execute();
            if ($row = $nameStmt->get_result()->fetch_assoc()) {
                $requesterName = trim(($row['firstname'] ?? '') . ' ' . ($row['middlename'] ?? '') . ' ' . ($row['lastname'] ?? ''));
            }
            $nameStmt->close();
        }
    }

    $verb = ($event === 'approve') ? 'approved' : 'rejected';
    $reasonHtml = ($event === 'reject' && $reason) ? '<br><br><strong>Reason:</strong> ' . htmlspecialchars($reason) : '';

    // Common body used for all recipients
    $body = '<p>This is a status update regarding your laboratory reservation request <strong>SLR-' . intval($id) . '</strong>.</p>'
        . '<p><strong>Facility:</strong> ' . htmlspecialchars($request['scilabName'] ?? '') . '<br>'
        . '<strong>Requested By:</strong> ' . htmlspecialchars($requesterName) . '<br>'
        . '<strong>Date/Time:</strong> ' . htmlspecialchars(($request['inclusiveDate'] ?? '') . ' ' . ($request['inclusiveTime'] ?? '')) . '</p>'
        . '<p><strong>' . htmlspecialchars($stageLabel) . '</strong> has ' . $verb . ' this request.' . $reasonHtml . '</p>'
        . '<p>You can track the progress of this request here: <a href="' . htmlspecialchars($trackerLink) . '">View Request Status</a></p>';

    // Unified subject for all recipients
    $unifiedSubject = 'SciLab Request SLR-' . intval($id);

    
    // --- Student / requester ---
    $requesterEmail = scilab_resolve_requester_email($conn, $request['requesterEmployeeID'] ?? '');
    if ($requesterEmail) {
        scilab_send_status_email([$requesterEmail], $unifiedSubject, $body);
    }

    // --- Prerequisite approvers (only those strictly BEFORE the current stage and already approved) ---
    $prior = [];
    if ($isForce) {
        $prior = ['supervisor', 'subject_teacher', 'lab_personnel', 'cid_chief'];
    } else {
        if ($currIdx > 0 && (($request['supervisor_status'] ?? '') === 'approved')) $prior[] = 'supervisor';
        if ($currIdx > 1 && (($request['subject_teacher_status'] ?? '') === 'approved')) $prior[] = 'subject_teacher';
        if ($currIdx > 2 && (($request['lab_personnel_status'] ?? '') === 'approved')) $prior[] = 'lab_personnel';
        if ($currIdx > 3 && (($request['cid_chief_status'] ?? '') === 'approved')) $prior[] = 'cid_chief';
    }

    $stageEmailResolver = [
        'supervisor' => function () use ($conn, $request) {
            return scilab_resolve_teacher_in_charge_emails($conn, $request['teacherInCharge'] ?? '');
        },
        'subject_teacher' => function () use ($conn, $request) {
            return scilab_resolve_auh_emails($conn, $request['subjectAcademicUnit'] ?? '', $request['gradeLevel'] ?? null);
        },
        'lab_personnel' => function () use ($conn) {
            return scilab_resolve_lab_personnel_emails($conn);
        },
        'cid_chief' => function () use ($conn) {
            return scilab_resolve_cid_chief_emails($conn);
        },
    ];

    foreach ($prior as $stage) {
        if (!isset($stageEmailResolver[$stage])) continue;
        $emails = $stageEmailResolver[$stage]();
        if (!empty($emails)) {
            scilab_send_status_email($emails, $unifiedSubject, $body);
        }
    }
}
