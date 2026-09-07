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
    $email = null;
    $stmt = $conn->prepare("SELECT email FROM accounts WHERE employeeID = ?");
    if ($stmt) {
        $stmt->bind_param("s", $requesterID);
        $stmt->execute();
        if ($row = $stmt->get_result()->fetch_assoc()) {
            $email = $row['email'];
        }
        $stmt->close();
    }
    if (!$email) {
        $stmt = $conn->prepare("SELECT email FROM student WHERE LRN = ?");
        if ($stmt) {
            $stmt->bind_param("s", $requesterID);
            $stmt->execute();
            if ($row = $stmt->get_result()->fetch_assoc()) {
                $email = $row['email'];
            }
            $stmt->close();
        }
    }
    return $email ?: null;
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

function scilab_resolve_auh_emails($conn, $subject, $gradeLevel = null) {
    $designation = scilab_auh_designation($conn, $subject, $gradeLevel);
    if ($designation === null) return [];

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
            $nameStmt->bind_param("s", $request['requesterEmployeeID']);
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
            return scilab_resolve_auh_emails($conn, $request['subject'] ?? '', $request['gradeLevel'] ?? null);
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
