<?php
// variableDeclarations.php
// Centralized configuration file
// Edit ONLY this file (and db_connection.php) to adapt the system to new setups.

$active_server = "scilab"; // Change this to "scilab", "reserve-a-lab", etc. when switching environments.

// SESSION VARIABLE KEYS
$session_employeeID = "employeeID";
$session_email = "email";
$session_firstname = "firstname";
$session_middlename = "middlename";
$session_lastname = "lastname";
$session_username = "username";
$session_role = "role";

// FOLDER STRUCTURE
$lab_images_folder = "img/labimages";
$base_lab_image_dir = "../img/labimages/";
$relative_lab_image_dir = "img/labimages/";

// USER ROLES
$roles = [
    'admin' => 'Administrator',
    'personnel' => 'SciLab Personnel',
    'teacher' => 'Teacher',
    'student' => 'Student'
];

$accepted_admin_roles = [
    "Sci. Res. Assist.",
    "Sci. Research Specialist I"
];

// DATABASE TABLE NAMES
$db_table_requests = "scilab_form_requests";
$db_table_accounts = "accounts";
$db_table_current = "current";
$db_table_logs = "activity_logs";
$db_table_notifications = "notifications";
$db_table_settings = "system_settings";
$db_table_sections = "section";
$db_table_subjects = "subject";
$db_table_students = "student";
$db_table_materials = "scilab_material_requests";
$db_table_involved_students = "scilab_students_involved";
$db_table_availability = "scilab_availability";
$db_table_sy = "current";
$db_table_new_accounts = "scilab_new_accounts";

// DATABASE COLUMN NAMES
$col_id = "id";
$col_value = "value";
$col_status = "status";

$db_col_employeeID = "employeeID";
$db_col_email = "email";
$db_col_password = "password";
$db_col_firstname = "firstname";
$db_col_middlename = "middlename";
$db_col_lastname = "lastname";
$db_col_position = "position";
$db_col_status = "status";

$db_col_scilabName = "scilabName";
$db_col_location = "location";
$db_col_inclusiveDate = "inclusiveDate";
$db_col_inclusiveTime = "inclusiveTime";
$db_col_statusPersonnel = "statusScilabPersonnel";
$db_col_requester = "requester";
$db_col_sy = "sy";

$col_inclusiveDate = "inclusiveDate";
$col_inclusiveTime = "inclusiveTime";
$col_scilabName = "scilabName";
$col_statusScilabPersonnel = "statusScilabPersonnel";

// REQUESTS TABLE COLUMNS
$col_requests = [
    'id' => 'id',
    'requester_id' => 'requesterEmployeeID',
    'scilabName' => 'scilabName',
    'grade' => 'gradeLevel',
    'section' => 'section/s',
    'subject' => 'subject',
    'topic' => 'subjectTopic',
    'date' => 'inclusiveDate',
    'time' => 'inclusiveTime',
    'status' => 'statusScilabPersonnel',
    'controlNumber' => 'controlNumber',
    'feedback' => 'feedback'
];

// SECTIONS TABLE
$col_sectionName = "section";
$col_gradeLevel = "grade";

// SUBJECTS TABLE
$col_subjectDesc = "subjectDescription";
$col_subjectUnit = "subjectAcademicUnit";
$col_subjectGradeLevel = "subjectGradeLevel";

// STUDENT TABLE
$col_LRN = "LRN";
$col_batch = "batch";

// GENERAL INFORMATION
$school_name = "Philippine Science High School - Ilocos Region Campus";
$school_abbrev = "PSHS-IRC";
$system_name = "SciLab Reservation Management System";
$organization = "Department of Science and Technology (DOST)";

// EMAIL SETTINGS
$email_sender = "pshsircscilab@gmail.com"; //edit to official
$email_sender_name = "PSHS-IRC SciLab";
$email_display_name = "PSHS-IRC SciLab";
$email_smtp_host = "smtp.gmail.com";
$email_smtp_port = 587;
$email_smtp_user = "pshsircscilab@gmail.com"; //edit to official
$email_smtp_password = "wxzmkkrffptfchcc"; //edit to official
$email_smtp_secure = "tls";

// FRONTEND / SYSTEM PATHS
$template_dir = __DIR__ . "/../emailTemplates/";
$upload_dir = __DIR__ . "/../uploads/";
$log_dir = __DIR__ . "/../logs/";

// TIMEZONE & FORMATTING
date_default_timezone_set("Asia/Manila");
$time_format = "g:i A";
$date_format = "F j, Y";

// SYSTEM BEHAVIOR SETTINGS
$enable_email_notifications = true;
$enable_activity_logging = true;
$default_request_status = "Pending";

if (!function_exists('scilab_approval_token')) {
    /**
     * Stateless magic-link token for passwordless approval actions.
     * Bound to the request ID and the approval stage so a link sent for
     * one stage cannot be reused at a later stage.
     */
    function scilab_approval_token($requestId, $stage)
    {
        return hash_hmac('sha256', intval($requestId) . '|' . $stage, 'SciLabApprovalLink2026');
    }
}

if (!function_exists('scilab_auh_designation')) {
    /**
     * Resolve the Area Unit Head (AUH) designation for a given subject.
     * The request's subject column is a 25-char truncated copy of subjectCode,
     * so an exact join is unreliable; use ordered keyword rules first, then
     * a prefix match against the subject table.
     *
     * Returns "AUH-<unit>" or null when the subject cannot be mapped.
     */
    function scilab_auh_designation($conn, $subject, $gradeLevel = null)
    {
        $subject = trim((string)$subject);
        if ($subject === '') {
            return null;
        }

        // Ordered keyword rules (case-insensitive). More specific terms first.
        $rules = [
            'Computer Science' => 'AUH-Computer Science',
            'Integrated Science' => 'AUH-Integrated Science',
            'Social Science' => 'AUH-Social Science/Values Education',
            'Values Education' => 'AUH-Social Science/Values Education',
            'Chemistry' => 'AUH-Chemistry',
            'Chem' => 'AUH-Chemistry',
            'Physics' => 'AUH-Physics',
            'Biology' => 'AUH-Biology',
            'Mathematics' => 'AUH-Mathematics',
            'Math' => 'AUH-Mathematics',
            'Research' => 'AUH-Research',
            'SCALE' => 'AUH-SCALE',
            'Technology' => 'AUH-Technology',
            'English' => 'AUH-English',
            'Filipino' => 'AUH-Filipino',
            'PEHM' => 'AUH-PEHM',
        ];

        foreach ($rules as $keyword => $designation) {
            if (stripos($subject, $keyword) !== false) {
                return $designation;
            }
        }

        // Fallback: match the (possibly truncated) subject code against the subject table.
        if ($conn) {
            $like = $subject . '%';
            $stmt = $conn->prepare("SELECT subjectAcademicUnit FROM subject WHERE subjectCode LIKE ? AND status = 'active' ORDER BY CHAR_LENGTH(subjectCode) ASC LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("s", $like);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($row = $res->fetch_assoc()) {
                    $unit = trim($row['subjectAcademicUnit'] ?? '');
                    if ($unit !== '') {
                        return 'AUH-' . $unit;
                    }
                }
                $stmt->close();
            }
        }

        return null;
    }
}

if (!function_exists('checkInventoryThresholdAndNotify')) {
    /**
     * Check an inventory item against its alert threshold and notify
     * admins by email the first time the quantity reaches or drops
     * below the threshold. Lives here so it can be reused by the
     * approval endpoints after an automatic inventory deduction.
     */
    function checkInventoryThresholdAndNotify($conn, $itemId) {
        global $email_smtp_host, $email_smtp_user, $email_smtp_password, $email_smtp_secure, $email_smtp_port, $email_sender;

        $stmt = $conn->prepare("SELECT item, description, classification, quantity, unit, threshold_qty, threshold_notified FROM scilab_inventory WHERE id = ?");
        $stmt->bind_param("i", $itemId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) {
            $stmt->close();
            return;
        }
        $item = $res->fetch_assoc();
        $stmt->close();

        $qty = intval($item['quantity']);
        $threshold = $item['threshold_qty'];
        $notified = intval($item['threshold_notified']);

        if ($threshold === null || $threshold === '') {
            if ($notified === 1) {
                $updateNotified = $conn->prepare("UPDATE scilab_inventory SET threshold_notified = 0 WHERE id = ?");
                $updateNotified->bind_param("i", $itemId);
                $updateNotified->execute();
                $updateNotified->close();
            }
            return;
        }

        $threshold = intval($threshold);

        if ($qty <= $threshold) {
            if ($notified === 0) {
                $admins = [];
                $adminStmt = $conn->prepare("SELECT email, firstname, lastname FROM accounts WHERE status = 'active' AND position IN ('Sci. Res. Assist.', 'Sci. Research Specialist I')");
                $adminStmt->execute();
                $adminRes = $adminStmt->get_result();
                while ($adminRow = $adminRes->fetch_assoc()) {
                    $admins[] = $adminRow;
                }
                $adminStmt->close();

                if (empty($admins)) {
                    return;
                }

                $templatePath = __DIR__ . '/../templates/threshold_alert_email.php';
                if (file_exists($templatePath)) {
                    $bodyTemplate = file_get_contents($templatePath);
                } else {
                    $bodyTemplate = "Low stock alert: [ITEM_NAME] is at [CURRENT_QTY] [UNIT] (Threshold: [THRESHOLD_QTY] [UNIT])";
                }

                $replacements = [
                    '[ITEM_NAME]' => htmlspecialchars($item['item']),
                    '[ITEM_DESC]' => htmlspecialchars($item['description'] ?? 'N/A'),
                    '[CLASSIFICATION]' => htmlspecialchars($item['classification']),
                    '[CURRENT_QTY]' => $qty,
                    '[THRESHOLD_QTY]' => $threshold,
                    '[UNIT]' => htmlspecialchars($item['unit'])
                ];

                foreach ($admins as $admin) {
                    $adminName = trim($admin['firstname'] . ' ' . $admin['lastname']);
                    $emailBody = str_replace('[ADMIN_NAME]', $adminName, $bodyTemplate);
                    foreach ($replacements as $key => $val) {
                        $emailBody = str_replace($key, $val, $emailBody);
                    }

                    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host = $email_smtp_host;
                        $mail->SMTPAuth = true;
                        $mail->Username = $email_smtp_user;
                        $mail->Password = $email_smtp_password;
                        $mail->SMTPSecure = $email_smtp_secure;
                        $mail->Port = $email_smtp_port;

                        $mail->setFrom($email_sender, 'PSHS-IRC SciLab');
                        $mail->addAddress($admin['email'], $adminName);

                        $mail->isHTML(true);
                        $mail->Subject = "LOW STOCK ALERT: " . $item['item'];
                        $mail->Body    = $emailBody;

                        $mail->send();
                    } catch (Exception $e) {
                        error_log("Email sending failed to admin {$admin['email']}: {$mail->ErrorInfo}");
                    }
                }

                $updateNotified = $conn->prepare("UPDATE scilab_inventory SET threshold_notified = 1 WHERE id = ?");
                $updateNotified->bind_param("i", $itemId);
                $updateNotified->execute();
                $updateNotified->close();
            }
        } else {
            if ($notified === 1) {
                $updateNotified = $conn->prepare("UPDATE scilab_inventory SET threshold_notified = 0 WHERE id = ?");
                $updateNotified->bind_param("i", $itemId);
                $updateNotified->execute();
                $updateNotified->close();
            }
        }
    }
}

if (!function_exists('scilab_deduct_inventory')) {
    /**
     * Deduct requested material quantities from scilab_inventory once a
     * reservation is fully approved. Matches inventory by item name and
     * prefers an exact description match when several rows share a name.
     * Quantities can never be driven below zero.
     *
     * Returns an array map of inventory id => quantity deducted.
     */
    function scilab_deduct_inventory($conn, $formID)
    {
        $updated = [];

        $stmt = $conn->prepare("SELECT item, quantity, description FROM scilab_material_requests WHERE formID = ?");
        if (!$stmt) return $updated;
        $stmt->bind_param("i", $formID);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($mat = $result->fetch_assoc()) {
            $item = trim($mat['item'] ?? '');
            if ($item === '') continue;
            $qty = intval($mat['quantity'] ?? 0);
            if ($qty <= 0) continue;

            $invStmt = $conn->prepare("SELECT id, item, description, quantity FROM scilab_inventory WHERE item = ? AND (status IS NULL OR status != 'Removed') ORDER BY quantity DESC, id ASC");
            if (!$invStmt) continue;
            $invStmt->bind_param("s", $item);
            $invStmt->execute();
            $invRes = $invStmt->get_result();

            $candidates = [];
            $descMatch = null;
            $requestedDesc = trim((string)($mat['description'] ?? ''));
            while ($inv = $invRes->fetch_assoc()) {
                $candidates[] = $inv;
                if ($descMatch === null && $requestedDesc !== '' && strcasecmp(trim((string)$inv['description']), $requestedDesc) === 0) {
                    $descMatch = $inv;
                }
            }
            $invStmt->close();

            $target = $descMatch ?: ($candidates[0] ?? null);
            if (!$target) continue;

            $deduct = min($qty, intval($target['quantity']));
            if ($deduct <= 0) continue;

            $upd = $conn->prepare("UPDATE scilab_inventory SET quantity = quantity - ? WHERE id = ? AND quantity > 0");
            if (!$upd) continue;
            $upd->bind_param("ii", $deduct, $target['id']);
            if ($upd->execute() && $upd->affected_rows > 0) {
                $updated[$target['id']] = ($updated[$target['id']] ?? 0) + $deduct;
                checkInventoryThresholdAndNotify($conn, $target['id']);
            }
            $upd->close();
        }

        $stmt->close();
        return $updated;
    }
}
?>