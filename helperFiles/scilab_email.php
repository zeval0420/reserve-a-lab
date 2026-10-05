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

function scilab_is_faculty_requester($conn, $requesterID) {
    $requesterID = trim((string)$requesterID);
    if ($requesterID === '') return false;
    $stmt = $conn->prepare("SELECT type, position FROM accounts WHERE employeeID = ? OR email = ? LIMIT 1");
    if (!$stmt) return false;
    $stmt->bind_param("ss", $requesterID, $requesterID);
    $stmt->execute();
    $res = $stmt->get_result();
    $isFaculty = false;
    if ($row = $res->fetch_assoc()) {
        $type = strtolower(trim($row['type'] ?? ''));
        $pos = strtolower(trim($row['position'] ?? ''));
        if ($type === 'faculty' || $type === 'sysadmin' || $pos === 'teacher' || (!empty($type) && $type !== 'student')) {
            $isFaculty = true;
        }
    }
    $stmt->close();
    return $isFaculty;
}

function scilab_resolve_requester_name($conn, $requesterID) {
    $requesterID = trim((string)$requesterID);
    if ($requesterID === '') return 'Unknown Requester';

    // 1. accounts table (teachers / staff)
    $stmt = $conn->prepare("SELECT firstname, middlename, lastname FROM accounts WHERE employeeID = ? OR email = ?");
    if ($stmt) {
        $stmt->bind_param("ss", $requesterID, $requesterID);
        $stmt->execute();
        if ($row = $stmt->get_result()->fetch_assoc()) {
            $name = trim(($row['firstname'] ?? '') . ' ' . ($row['middlename'] ?? '') . ' ' . ($row['lastname'] ?? ''));
            $stmt->close();
            if ($name !== '') return $name;
        } else {
            $stmt->close();
        }
    }

    // 2. student table
    $stmt = $conn->prepare("SELECT firstname, middlename, lastname FROM student WHERE LRN = ?");
    if ($stmt) {
        $stmt->bind_param("s", $requesterID);
        $stmt->execute();
        if ($row = $stmt->get_result()->fetch_assoc()) {
            $name = trim(($row['firstname'] ?? '') . ' ' . ($row['middlename'] ?? '') . ' ' . ($row['lastname'] ?? ''));
            $stmt->close();
            if ($name !== '') return $name;
        } else {
            $stmt->close();
        }
    }

    // 3. scilab_new_accounts table (registered student accounts)
    $stmt = $conn->prepare("SELECT firstname, middlename, lastname FROM scilab_new_accounts WHERE userID = ? OR username = ? OR id = ?");
    if ($stmt) {
        $stmt->bind_param("sss", $requesterID, $requesterID, $requesterID);
        $stmt->execute();
        if ($row = $stmt->get_result()->fetch_assoc()) {
            $name = trim(($row['firstname'] ?? '') . ' ' . ($row['middlename'] ?? '') . ' ' . ($row['lastname'] ?? ''));
            $stmt->close();
            if ($name !== '') return $name;
        } else {
            $stmt->close();
        }
    }

    return $requesterID;
}

function scilab_resolve_requester_email($conn, $requesterID, $formID = null) {
    $requesterID = trim((string)$requesterID);

    error_log("SciLab DEBUG - requesterEmployeeID received: [" . $requesterID . "], formID: [" . var_export($formID, true) . "]");

    // If the identifier is itself an email address, use it directly.
    if (!empty($requesterID) && filter_var($requesterID, FILTER_VALIDATE_EMAIL)) {
        error_log("SciLab DEBUG - ID is already an email: [" . $requesterID . "]");
        return $requesterID;
    }

    // 1. Faculty / personnel accounts table.
    if (!empty($requesterID)) {
        $stmt = $conn->prepare("SELECT email FROM accounts WHERE employeeID = ? OR email = ?");
        if ($stmt) {
            $stmt->bind_param("ss", $requesterID, $requesterID);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $email = trim($row['email'] ?? '');
                error_log("SciLab DEBUG - accounts lookup found email: [" . $email . "]");
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $stmt->close();
                    return $email;
                }
            }
            $stmt->close();
        }
    }

    // 2. Student directory / student table (by LRN or studentEmail).
    if (!empty($requesterID)) {
        $stmt = $conn->prepare("
            SELECT d.studentEmail AS email
            FROM student_directory d
            JOIN student s ON d.LRN = s.LRN
            WHERE s.LRN = ? OR d.studentEmail = ?
        ");

        if ($stmt) {
            $stmt->bind_param("ss", $requesterID, $requesterID);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $email = trim($row['email'] ?? '');
                error_log("SciLab DEBUG - Student lookup found email: [" . $email . "]");
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $stmt->close();
                    return $email;
                }
            }
            $stmt->close();
        }
    }

    // 3. scilab_new_accounts table (for registered student accounts).
    if (!empty($requesterID)) {
        $stmt = $conn->prepare("
            SELECT username, userID 
            FROM scilab_new_accounts 
            WHERE userID = ? OR username = ? OR id = ?
        ");
        if ($stmt) {
            $stmt->bind_param("sss", $requesterID, $requesterID, $requesterID);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $username = trim($row['username'] ?? '');
                $userID = trim($row['userID'] ?? '');

                if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
                    error_log("SciLab DEBUG - scilab_new_accounts username is email: [" . $username . "]");
                    $stmt->close();
                    return $username;
                }

                if ($userID !== '') {
                    $sdStmt = $conn->prepare("SELECT studentEmail FROM student_directory WHERE LRN = ?");
                    if ($sdStmt) {
                        $sdStmt->bind_param("s", $userID);
                        $sdStmt->execute();
                        $sdRes = $sdStmt->get_result();
                        if ($sdRow = $sdRes->fetch_assoc()) {
                            $sdEmail = trim($sdRow['studentEmail'] ?? '');
                            if (filter_var($sdEmail, FILTER_VALIDATE_EMAIL)) {
                                error_log("SciLab DEBUG - scilab_new_accounts userID mapped to student_directory email: [" . $sdEmail . "]");
                                $sdStmt->close();
                                $stmt->close();
                                return $sdEmail;
                            }
                        }
                        $sdStmt->close();
                    }
                }

                if ($username !== '') {
                    $constructed = (strpos($username, '@') !== false) ? $username : ($username . '@irc.pshs.edu.ph');
                    if (filter_var($constructed, FILTER_VALIDATE_EMAIL)) {
                        error_log("SciLab DEBUG - scilab_new_accounts constructed email: [" . $constructed . "]");
                        $stmt->close();
                        return $constructed;
                    }
                }
            }
            $stmt->close();
        }
    }

    // 4. Match by full name against student_directory or scilab_new_accounts
    if (!empty($requesterID) && strpos($requesterID, '@') === false) {
        $stmt = $conn->prepare("
            SELECT d.studentEmail
            FROM student_directory d
            JOIN student s ON d.LRN = s.LRN
            WHERE CONCAT(s.firstname, ' ', s.lastname) = ?
               OR CONCAT(s.lastname, ', ', s.firstname) = ?
               OR CONCAT(s.firstname, ' ', IFNULL(s.middlename, ''), ' ', s.lastname) = ?
            LIMIT 1
        ");
        if ($stmt) {
            $stmt->bind_param("sss", $requesterID, $requesterID, $requesterID);
            $stmt->execute();
            if ($row = $stmt->get_result()->fetch_assoc()) {
                $email = trim($row['studentEmail'] ?? '');
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    error_log("SciLab DEBUG - Name match in student_directory found email: [" . $email . "]");
                    $stmt->close();
                    return $email;
                }
            }
            $stmt->close();
        }

        $stmt = $conn->prepare("
            SELECT username, userID
            FROM scilab_new_accounts
            WHERE CONCAT(firstname, ' ', lastname) = ?
               OR CONCAT(lastname, ', ', firstname) = ?
               OR CONCAT(firstname, ' ', IFNULL(middlename, ''), ' ', lastname) = ?
            LIMIT 1
        ");
        if ($stmt) {
            $stmt->bind_param("sss", $requesterID, $requesterID, $requesterID);
            $stmt->execute();
            if ($row = $stmt->get_result()->fetch_assoc()) {
                $username = trim($row['username'] ?? '');
                $userID = trim($row['userID'] ?? '');
                $stmt->close();
                if (filter_var($username, FILTER_VALIDATE_EMAIL)) return $username;
                if ($userID !== '') {
                    $sdRes = $conn->query("SELECT studentEmail FROM student_directory WHERE LRN = '" . $conn->real_escape_string($userID) . "'");
                    if ($sdRes && $sdRow = $sdRes->fetch_assoc()) {
                        $sdEmail = trim($sdRow['studentEmail'] ?? '');
                        if (filter_var($sdEmail, FILTER_VALIDATE_EMAIL)) return $sdEmail;
                    }
                }
                $constructed = (strpos($username, '@') !== false) ? $username : ($username . '@irc.pshs.edu.ph');
                if (filter_var($constructed, FILTER_VALIDATE_EMAIL)) return $constructed;
            } else {
                $stmt->close();
            }
        }
    }

    // 5. Fallback: Search scilab_students_involved table by formID
    if ($formID && intval($formID) > 0) {
        $stStmt = $conn->prepare("SELECT student_name FROM scilab_students_involved WHERE formID = ?");
        if ($stStmt) {
            $formID = intval($formID);
            $stStmt->bind_param("i", $formID);
            $stStmt->execute();
            $stRes = $stStmt->get_result();
            while ($stRow = $stRes->fetch_assoc()) {
                $sName = trim($stRow['student_name'] ?? '');
                if ($sName !== '') {
                    $foundEmail = scilab_resolve_requester_email($conn, $sName, null);
                    if ($foundEmail) {
                        error_log("SciLab DEBUG - Resolved email via scilab_students_involved: [" . $foundEmail . "]");
                        $stStmt->close();
                        return $foundEmail;
                    }
                }
            }
            $stStmt->close();
        }
    }

    // 6. Fallback for handle
    if (!empty($requesterID) && strpos($requesterID, '@') === false && preg_match('/^[a-zA-Z0-9._-]+$/', $requesterID)) {
        $constructed = $requesterID . '@irc.pshs.edu.ph';
        if (filter_var($constructed, FILTER_VALIDATE_EMAIL)) {
            error_log("SciLab DEBUG - Fallback constructed email from handle: [" . $constructed . "]");
            return $constructed;
        }
    }

    error_log("SciLab DEBUG - Email resolution FAILED for ID: [" . $requesterID . "]");
    return null;
}

function scilab_resolve_teacher_in_charge_emails($conn, $teacherInCharge) {
    $emails = [];
    if (empty($teacherInCharge)) return $emails;
    $stmt = $conn->prepare("SELECT email, TRIM(CONCAT(lastname, ', ', firstname, ' ', IFNULL(middlename, ''))) AS fullname, CONCAT(firstname, ' ', lastname) AS shortname, CONCAT(firstname, ' ', IFNULL(middlename, ''), ' ', lastname) AS longname FROM accounts WHERE status = 'active'");
    if (!$stmt) return $emails;
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $fn = trim($row['fullname'] ?? '');
        $sn = trim($row['shortname'] ?? '');
        $ln = trim($row['longname'] ?? '');
        if (($fn !== '' && stripos($teacherInCharge, $fn) !== false) ||
            ($sn !== '' && stripos($teacherInCharge, $sn) !== false) ||
            ($ln !== '' && stripos($teacherInCharge, $ln) !== false)) {
            if (!empty($row['email'])) {
                $emails[] = $row['email'];
            }
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

    $requesterEmail = scilab_resolve_requester_email($conn, $data['requesterEmployeeID'] ?? '', $requestId);
    if (!$requesterEmail && !empty($_SESSION['email'])) {
        $requesterEmail = trim($_SESSION['email']);
    }
    if (!$requesterEmail) return;

    // Resolve the requester display name using the central helper.
    $requesterName = scilab_resolve_requester_name($conn, $data['requesterEmployeeID'] ?? '');

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
 * Escalate a stage that has no resolvable approver.
 *
 * When a request reaches a stage but no email address can be resolved for the
 * person responsible (teacher-in-charge name mismatch, missing AUH designation
 * row, no active CID Chief), the request would otherwise sit pending forever
 * with nobody notified. This emails the Lab Personnel group so a human can
 * assign the stage manually, and leaves the stage pending.
 *
 * $stage:  'supervisor' | 'subject_teacher' | 'lab_personnel' | 'cid_chief'
 * $detail: human-readable description of what failed to resolve
 */
function scilab_notify_unresolved_stage($conn, $request, $stage, $detail) {
    global $active_server;

    $id = intval($request['id'] ?? 0);
    if ($id <= 0) return;

    $stageLabels = [
        'supervisor' => 'Supervisor (Teacher-in-Charge)',
        'subject_teacher' => 'Area Unit Head (AUH)',
        'lab_personnel' => 'Lab Personnel',
        'cid_chief' => 'CID Chief',
    ];
    $stageLabel = $stageLabels[$stage] ?? ucwords(str_replace('_', ' ', (string)$stage));

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $baseURL = $protocol . ($_SERVER['HTTP_HOST'] ?? '') . '/' . ($active_server ?? '');
    $actionLink = $baseURL . '/supervisor_approve.php?id=' . $id;

    $requesterName = scilab_resolve_requester_name($conn, $request['requesterEmployeeID'] ?? '');

    $body = '<p><strong>Action needed:</strong> laboratory reservation request <strong>SLR-' . $id
        . '</strong> is waiting on the <strong>' . htmlspecialchars($stageLabel) . '</strong> stage, '
        . 'but no approver could be matched for that stage.</p>'
        . '<p>The stage has been left <strong>pending</strong> so it is not skipped. '
        . 'Please assign the correct approver, or act on the request directly.</p>'
        . '<p><strong>Why it could not be resolved:</strong> ' . htmlspecialchars((string)$detail) . '</p>'
        . '<p><strong>Facility:</strong> ' . htmlspecialchars($request['scilabName'] ?? '') . '<br>'
        . '<strong>Requested By:</strong> ' . htmlspecialchars($requesterName) . '<br>'
        . '<strong>Teacher In-Charge:</strong> ' . htmlspecialchars($request['teacherInCharge'] ?? '') . '<br>'
        . '<strong>Academic Unit:</strong> ' . htmlspecialchars($request['subjectAcademicUnit'] ?? '') . '<br>'
        . '<strong>Date/Time:</strong> ' . htmlspecialchars(trim(($request['inclusiveDate'] ?? '') . ' ' . ($request['inclusiveTime'] ?? ''))) . '</p>'
        . '<p>Assign or review the request here: <a href="' . htmlspecialchars($actionLink) . '">Open Request SLR-' . $id . '</a></p>';

    $emails = scilab_resolve_lab_personnel_emails($conn);
    if (empty($emails)) {
        // Last resort: fall back to anyone with a Chief position so the alert is not lost.
        $emails = scilab_resolve_cid_chief_emails($conn);
    }
    if (empty($emails)) {
        error_log("SciLab escalation failed - no Lab Personnel or CID Chief email available for request {$id}, stage {$stage}");
        return;
    }

    error_log("SciLab escalation - request {$id} stuck at stage {$stage}: {$detail}");

    scilab_send_status_email($emails, 'Action needed: SciLab Request SLR-' . $id, $body);
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

    // Re-fetch the fresh row from the database so we see post-UPDATE status values.
    if ($id > 0) {
        $freshStmt = $conn->prepare("SELECT * FROM scilab_form_requests WHERE id = ?");
        if ($freshStmt) {
            $freshStmt->bind_param("i", $id);
            $freshStmt->execute();
            $freshRow = $freshStmt->get_result()->fetch_assoc();
            $freshStmt->close();
            if ($freshRow) {
                $request = $freshRow;
            }
        }
    }

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
    $requesterName = scilab_resolve_requester_name($conn, $request['requesterEmployeeID'] ?? '');

    $verb = ($event === 'approve') ? 'approved' : 'rejected';
    $reasonHtml = ($event === 'reject' && $reason) ? '<br><br><strong>Reason:</strong> ' . htmlspecialchars($reason) : '';

    // Spell out the terminal outcome so the final email a requestor receives is
    // unambiguous about whether the request completed or was denied.
    $overall = trim((string)($request['statusScilabPersonnel'] ?? ''));
    $isTerminal = in_array(strtolower($overall), ['approved', 'rejected'], true);
    $overallHtml = '';
    if ($isTerminal) {
        $overallLabel = (strtolower($overall) === 'approved') ? 'APPROVED' : 'REJECTED';
        if ($event === 'approve' && strtolower($overall) !== 'approved') {
            $overallHtml = '<p><strong>Status so far:</strong> this request has passed the '
                . htmlspecialchars($stageLabel) . ' stage and is still ' . $verb
                . ' by the remaining approver(s).</p>';
        } elseif ($event === 'reject' || strtolower($overall) === 'approved') {
            $overallHtml = '<p><strong>Final status of this request: ' . $overallLabel . '.</strong></p>';
        }
    }

    // Common body used for all recipients
    $body = '<p>This is a status update regarding your laboratory reservation request <strong>SLR-' . intval($id) . '</strong>.</p>'
        . '<p><strong>Facility:</strong> ' . htmlspecialchars($request['scilabName'] ?? '') . '<br>'
        . '<strong>Requested By:</strong> ' . htmlspecialchars($requesterName) . '<br>'
        . '<strong>Date/Time:</strong> ' . htmlspecialchars(($request['inclusiveDate'] ?? '') . ' ' . ($request['inclusiveTime'] ?? '')) . '</p>'
        . '<p><strong>' . htmlspecialchars($stageLabel) . '</strong> has ' . $verb . ' this request.' . $reasonHtml . '</p>'
        . $overallHtml
        . '<p>You can track the progress of this request here: <a href="' . htmlspecialchars($trackerLink) . '">View Request Status</a></p>';

    // Unified subject for all recipients
    $unifiedSubject = 'SciLab Request SLR-' . intval($id);

    
    // --- Student / requester (pass $id so formID-based fallback resolution works) ---
    $requesterEmail = scilab_resolve_requester_email($conn, $request['requesterEmployeeID'] ?? '', $id);
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
