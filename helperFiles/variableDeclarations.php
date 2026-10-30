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

// EMAIL SETTINGS
$email_sender = "info@irc.pshs.edu.ph.com"; //edit to official
$email_sender_name = "PSHS-IRC SciLab";
$email_display_name = "PSHS-IRC SciLab";
$email_smtp_host = "smtp.gmail.com";
$email_smtp_port = 587;
$email_smtp_user = "info@irc.pshs.edu.ph"; //edit to official
$email_smtp_password = 'fbvqqokdwwyuxqtg'; // edit to official
$email_smtp_secure = "tls";

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

// PRIVATE CREDENTIALS (kept out of version control)
$scilab_secrets = is_file(__DIR__ . '/secrets.php') ? (require __DIR__ . '/secrets.php') : [];
if (empty($scilab_secrets['approval_secret']) || empty($scilab_secrets['smtp_password'])) {
    error_log('helperFiles/secrets.php is missing or incomplete; see secrets.example.php');
}

include('email_credentials.php');

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
        global $scilab_secrets;
        $secret = $scilab_secrets['approval_secret'] ?? '';
        if ($secret === '') {
            throw new RuntimeException('approval_secret is not configured in helperFiles/secrets.php');
        }
        return hash_hmac('sha256', intval($requestId) . '|' . $stage, $secret);
    }
}

if (!function_exists('scilab_approval_token_valid')) {
    /**
     * Check a link token against the current secret, then the legacy secret so
     * links emailed before the secret moved to secrets.php keep working.
     */
    function scilab_approval_token_valid($requestId, $stage, $token)
    {
        global $scilab_secrets;
        if (!is_string($token) || $token === '') {
            return false;
        }
        if (hash_equals(scilab_approval_token($requestId, $stage), $token)) {
            return true;
        }
        $legacy = $scilab_secrets['legacy_approval_secret'] ?? '';
        return $legacy !== '' && hash_equals(hash_hmac('sha256', intval($requestId) . '|' . $stage, $legacy), $token);
    }
}

if (!function_exists('scilab_user_holds_designation')) {
    /**
     * Does this active account hold the given designation (case-insensitive,
     * trimmed) for the current school year?
     */
    function scilab_user_holds_designation($conn, $employeeID, $designation)
    {
        $employeeID = trim((string)$employeeID);
        $designation = trim((string)$designation);
        if ($employeeID === '' || $designation === '') {
            return false;
        }
        $syRes = $conn->query("SELECT value FROM current WHERE description = 'School Year' ORDER BY id DESC LIMIT 1");
        $syRow = $syRes ? $syRes->fetch_assoc() : null;
        $sy = $syRow['value'] ?? null;
        if (!$sy) {
            return false;
        }
        $stmt = $conn->prepare("SELECT 1 FROM designation d JOIN accounts a ON a.employeeID = d.employeeID WHERE d.sy = ? AND d.employeeID = ? AND LOWER(TRIM(d.designation)) = LOWER(?) AND a.status = 'active' LIMIT 1");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("sss", $sy, $employeeID, $designation);
        $stmt->execute();
        $holds = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $holds;
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
        $subject = trim(preg_replace('/\s+/', ' ', (string)$subject));
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

        error_log("scilab_auh_designation: no AUH designation resolvable for subject '{$subject}' (grade {$gradeLevel})");
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
     *
     * Runs inside the approval transaction (scilab_approve_request): the item
     * rows are locked with SELECT ... FOR UPDATE, and if any item has less
     * stock than requested nothing is deducted and the approval is refused,
     * so stock is never over-allocated or driven below zero. Same approach as
     * the manual deduction in ajax/ajax_inventory.php.
     *
     * Returns ['ok' => true, 'deducted' => [inventory id => quantity deducted]]
     * or ['ok' => false, 'message' => reason].
     */
    function scilab_deduct_inventory($conn, $formID)
    {
        $stmt = $conn->prepare("SELECT item, quantity, description FROM scilab_material_requests WHERE formID = ?");
        if (!$stmt) return ['ok' => false, 'message' => 'Could not read the requested materials.'];
        $stmt->bind_param("i", $formID);
        $stmt->execute();
        $result = $stmt->get_result();

        $materials = [];
        while ($mat = $result->fetch_assoc()) {
            $item = trim($mat['item'] ?? '');
            $qty = intval($mat['quantity'] ?? 0);
            if ($item === '' || $qty <= 0) continue;
            $materials[] = ['item' => $item, 'qty' => $qty, 'description' => trim((string)($mat['description'] ?? ''))];
        }
        $stmt->close();

        if (!$materials) return ['ok' => true, 'deducted' => []];

        // Find the candidate rows, then lock them by primary key in id order so
        // concurrent approvals that share items always lock in the same order.
        $names = array_values(array_unique(array_column($materials, 'item')));
        $placeholders = implode(',', array_fill(0, count($names), '?'));
        $idStmt = $conn->prepare("SELECT id FROM scilab_inventory WHERE item IN ($placeholders) AND (status IS NULL OR status != 'Removed')");
        if (!$idStmt) return ['ok' => false, 'message' => 'Could not read the inventory.'];
        $idStmt->bind_param(str_repeat('s', count($names)), ...$names);
        $idStmt->execute();
        $ids = array_map('intval', array_column($idStmt->get_result()->fetch_all(MYSQLI_ASSOC), 'id'));
        $idStmt->close();

        if (!$ids) return ['ok' => true, 'deducted' => []];

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $lockStmt = $conn->prepare("SELECT id, item, description, quantity, unit FROM scilab_inventory WHERE id IN ($placeholders) AND (status IS NULL OR status != 'Removed') ORDER BY id FOR UPDATE");
        if (!$lockStmt) return ['ok' => false, 'message' => 'Could not read the inventory.'];
        $lockStmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $lockStmt->execute();
        $rows = $lockStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $lockStmt->close();

        $upd = $conn->prepare("UPDATE scilab_inventory SET quantity = quantity - ? WHERE id = ? AND quantity >= ?");
        if (!$upd) return ['ok' => false, 'message' => 'Could not update the inventory.'];

        // Ledger of what was taken out of stock (scilab_inventory_deductions): one row per
        // form + inventory row, so a later cancel or release can put back / adjust exactly this.
        // A row already restored by a cancel starts over; otherwise quantities add up, in step
        // with the stock changes made here.
        $ledger = $conn->prepare("INSERT INTO scilab_inventory_deductions (formID, inventoryID, quantity) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = IF(restoredAt IS NULL, quantity + VALUES(quantity), VALUES(quantity)), restoredAt = NULL");
        if (!$ledger) {
            $upd->close();
            return ['ok' => false, 'message' => 'Could not record the inventory deduction.'];
        }

        $deducted = [];
        foreach ($materials as $mat) {
            // Same choice as before: the exact description match, else the row with the most stock
            $candidates = array_values(array_filter($rows, function ($inv) use ($mat) {
                return strcasecmp(trim((string)$inv['item']), $mat['item']) === 0;
            }));
            usort($candidates, function ($a, $b) {
                return [intval($b['quantity']), intval($a['id'])] <=> [intval($a['quantity']), intval($b['id'])];
            });

            $target = null;
            foreach ($candidates as $inv) {
                if ($mat['description'] !== '' && strcasecmp(trim((string)$inv['description']), $mat['description']) === 0) {
                    $target = $inv;
                    break;
                }
            }
            $target = $target ?: ($candidates[0] ?? null);
            if (!$target) continue;

            $available = intval($target['quantity']);
            if ($mat['qty'] > $available) {
                $upd->close();
                $ledger->close();
                return ['ok' => false, 'message' => 'Cannot approve: not enough stock for "' . $target['item'] . '" (' . $mat['qty'] . ' requested, only ' . $available . ' ' . $target['unit'] . ' left). Nothing was deducted.'];
            }

            $upd->bind_param("iii", $mat['qty'], $target['id'], $mat['qty']);
            $upd->execute();
            if ($upd->affected_rows !== 1) {
                $upd->close();
                $ledger->close();
                return ['ok' => false, 'message' => 'Stock changed while deducting. Please try again.'];
            }

            $ledgerInv = intval($target['id']);
            $ledger->bind_param("iii", $formID, $ledgerInv, $mat['qty']);
            if (!$ledger->execute()) {
                $upd->close();
                $ledger->close();
                return ['ok' => false, 'message' => 'Could not record the inventory deduction. Nothing was deducted.'];
            }

            // Later materials in this request see the reduced quantity
            foreach ($rows as &$row) {
                if ($row['id'] === $target['id']) $row['quantity'] = $available - $mat['qty'];
            }
            unset($row);
            $deducted[$target['id']] = ($deducted[$target['id']] ?? 0) + $mat['qty'];
        }

        $upd->close();
        $ledger->close();
        return ['ok' => true, 'deducted' => $deducted];
    }
}

if (!function_exists('scilab_find_slot_conflict')) {
    /**
     * Find an approved request for the same lab whose time range ("9:00 AM to 11:00 AM")
     * overlaps the given one on ANY of the given dates. $date is one 'Y-m-d' string or a list of
     * them (a request can use several days, see helperFiles/request_dates.php). Touching
     * boundaries do not overlap, as in the check_conflict action of ajax/ajax_forms.php.
     * Returns the conflicting row (id, inclusiveTime, conflictDate) or null.
     */
    function scilab_find_slot_conflict($conn, $scilabName, $date, $timeRange, $excludeId = 0)
    {
        $range = explode(' to ', (string)$timeRange);
        if (count($range) !== 2) return null;
        $start = strtotime($range[0]);
        $end = strtotime($range[1]);
        if ($start === false || $end === false) return null;

        $dates = array_values(array_filter(is_array($date) ? $date : [$date], 'strlen'));
        if (!$dates) return null;
        scilab_ensure_request_dates_table($conn);

        $excludeId = intval($excludeId);
        $marks = implode(',', array_fill(0, count($dates), '?'));
        $stmt = $conn->prepare("SELECT r.id, r.inclusiveTime, d.useDate AS conflictDate
            FROM scilab_form_requests r
            JOIN scilab_request_dates d ON d.formID = r.id
            WHERE r.scilabName = ? AND r.statusScilabPersonnel = 'Approved' AND r.id != ? AND d.useDate IN ($marks)
            ORDER BY d.useDate ASC");
        if (!$stmt) return null;
        $stmt->bind_param('si' . str_repeat('s', count($dates)), $scilabName, $excludeId, ...$dates);
        $stmt->execute();
        $result = $stmt->get_result();

        $conflict = null;
        while ($row = $result->fetch_assoc()) {
            $other = explode(' to ', $row['inclusiveTime']);
            if (count($other) !== 2) continue;
            if ($start < strtotime($other[1]) && $end > strtotime($other[0])) {
                $conflict = $row;
                break;
            }
        }
        $stmt->close();
        return $conflict;
    }
}

if (!function_exists('scilab_approve_request')) {
    /**
     * Final approval of a request, in one transaction:
     *  1. lock the lab's scilab_availability row (SELECT ... FOR UPDATE), so
     *     approvals for the same lab run one at a time;
     *  2. refuse if an approved request for the same lab and date overlaps;
     *  3. run $markApproved($conn), the caller's status UPDATE (returns bool);
     *  4. deduct the materials (scilab_deduct_inventory), refusing if stock is short, and
     *     record them in the scilab_inventory_deductions ledger;
     *  5. assign blank control numbers (scilab_assign_control_numbers, when available).
     * A refusal rolls everything back. Low-stock alerts are sent after the commit.
     * Refusal messages avoid the word "approved": admin_approve.php treats a
     * response containing it as success.
     *
     * Returns ['ok' => bool, 'reason' => null|'not_found'|'slot_conflict'|'update_failed'|'insufficient_stock',
     *          'message' => string].
     */
    function scilab_approve_request($conn, $formID, callable $markApproved)
    {
        $formID = intval($formID);

        // Read before the transaction starts: under REPEATABLE READ the first plain
        // read fixes the snapshot, and that has to happen after the lab lock is held.
        $stmt = $conn->prepare("SELECT scilabName, inclusiveDate, inclusiveTime FROM scilab_form_requests WHERE id = ?");
        $stmt->bind_param("i", $formID);
        $stmt->execute();
        $request = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$request) {
            return ['ok' => false, 'reason' => 'not_found', 'message' => 'Request not found.'];
        }
        $requestDates = scilab_request_dates($conn, $formID, $request['inclusiveDate']);

        $stmt = $conn->prepare("SELECT id FROM scilab_availability WHERE scilabName = ?");
        $stmt->bind_param("s", $request['scilabName']);
        $stmt->execute();
        $lab = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $conn->begin_transaction();
        try {
            if ($lab) {
                // Lock by primary key so only this lab's row is locked
                $lock = $conn->prepare("SELECT id FROM scilab_availability WHERE id = ? FOR UPDATE");
                $lock->bind_param("i", $lab['id']);
                $lock->execute();
                $lock->get_result();
                $lock->close();
            }

            $conflict = scilab_find_slot_conflict($conn, $request['scilabName'], $requestDates, $request['inclusiveTime'], $formID);
            if ($conflict) {
                $conn->rollback();
                return ['ok' => false, 'reason' => 'slot_conflict', 'message' => 'Cannot approve: ' . $request['scilabName']
                    . ' is already booked on ' . date('F j, Y', strtotime($conflict['conflictDate']))
                    . ' from ' . $conflict['inclusiveTime'] . ' (request #' . $conflict['id'] . ').'];
            }

            if (!$markApproved($conn)) {
                $conn->rollback();
                return ['ok' => false, 'reason' => 'update_failed', 'message' => 'Update failed.'];
            }

            $deduction = scilab_deduct_inventory($conn, $formID);
            if (!$deduction['ok']) {
                $conn->rollback();
                return ['ok' => false, 'reason' => 'insufficient_stock', 'message' => $deduction['message']];
            }

            // Automatic control numbers (helperFiles/control_numbers.php): only fills blanks,
            // and runs inside this transaction so a failure rolls the approval back too.
            if (function_exists('scilab_assign_control_numbers')) {
                scilab_assign_control_numbers($conn, $formID);
            }

            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
            throw $e;
        }

        foreach (array_keys($deduction['deducted']) as $itemId) {
            checkInventoryThresholdAndNotify($conn, $itemId);
        }
        return ['ok' => true, 'reason' => null, 'message' => ''];
    }
}

// Helpers added with the subject-teacher / control-number / material-release work.
// Each file is owned by one feature; see tasks-subject-teacher-release.md.
foreach (['stage_flow.php', 'control_numbers.php', 'material_release.php', 'request_dates.php'] as $ral_helper) {
    if (is_file(__DIR__ . '/' . $ral_helper)) {
        require_once __DIR__ . '/' . $ral_helper;
    }
}
unset($ral_helper);
?>
