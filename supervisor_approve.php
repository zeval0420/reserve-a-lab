<?php
include('../scilab/helperFiles/db_connection.php');
include('helperFiles/session_handler.php');
include('helperFiles/variableDeclarations.php');

$email = $_SESSION['email'] ?? null;
$username = $_SESSION['username'] ?? null;

$requestId = $_GET['id'] ?? null;
$token = $_GET['token'] ?? null;

if (!$requestId) {
    die("Please provide a request ID.");
}

$stmt = $conn->prepare("SELECT * FROM scilab_form_requests WHERE id = ?");
$stmt->bind_param("i", $requestId);
$stmt->execute();
$result = $stmt->get_result();
$request = $result->fetch_assoc();

if (!$request) {
    die("Request not found.");
}

$requesterID = $request['requesterEmployeeID'];
$name = $requesterID;

// Try fetching from accounts
$stmtUser = $conn->prepare("SELECT firstname, middlename, lastname, email, position FROM accounts WHERE employeeID = ?");
$stmtUser->bind_param("s", $requesterID);
$stmtUser->execute();
$resUser = $stmtUser->get_result();

$requesterEmail = 'N/A';
$requesterDept = 'N/A';

if ($userRow = $resUser->fetch_assoc()) {
    $name = $userRow['firstname'] . ' ' . (empty($userRow['middlename']) ? '' : $userRow['middlename'] . ' ') . $userRow['lastname'];
    $requesterEmail = $userRow['email'] ?? 'N/A';
    $requesterDept = $userRow['position'] ?? 'N/A';
} else {
    // Try fetching from student
    $stmtUser = $conn->prepare("SELECT firstname, middlename, lastname FROM student WHERE LRN = ?");
    $stmtUser->bind_param("s", $requesterID);
    $stmtUser->execute();
    $resUser = $stmtUser->get_result();

    if ($userRow = $resUser->fetch_assoc()) {
        $name = $userRow['firstname'] . ' ' . (empty($userRow['middlename']) ? '' : $userRow['middlename'] . ' ') . $userRow['lastname'];
        $requesterDept = 'Student';
    }
}

$grade = $request['gradeLevel'] ?? '';
$section = $request['sections'] ?? '';
$date = $request['inclusiveDate'] ?? '';
$time = $request['inclusiveTime'] ?? '';
$laboratoryName = $request['scilabName'] ?? '';
$purpose = $request['subjectTopic'] ?? '';
$teacherInCharge = $request['teacherInCharge'] ?? '';
$submissionDate = date('F j, Y 	 h:i A', strtotime($request['created_at'] ?? 'now'));

$supervisor_status = $request['supervisor_status'] ?? 'pending';
$subject_teacher_status = $request['subject_teacher_status'] ?? 'pending';
$lab_personnel_status = $request['lab_personnel_status'] ?? 'pending';
$cid_chief_status = $request['cid_chief_status'] ?? 'pending';

if ($supervisor_status === 'pending') {
    $currentStage = 'supervisor';
} elseif ($supervisor_status === 'approved' && $subject_teacher_status === 'pending') {
    $currentStage = 'subject_teacher';
} elseif ($subject_teacher_status === 'approved' && $lab_personnel_status === 'pending') {
    $currentStage = 'lab_personnel';
} elseif ($lab_personnel_status === 'approved' && $cid_chief_status === 'pending') {
    $currentStage = 'cid_chief';
} else {
    $currentStage = '';
}

$tokenValidStage = '';
if ($token) {
    foreach (['supervisor', 'subject_teacher', 'lab_personnel', 'cid_chief'] as $stageName) {
        if (hash_equals(scilab_approval_token($requestId, $stageName), $token)) {
            $tokenValidStage = $stageName;
            break;
        }
    }
}

if (!isset($_SESSION['role']) && $tokenValidStage === '') {
    $target = 'redirect=' . urlencode('supervisor_approve.php?id=' . $requestId);
    header('Location: index.php?' . $target);
    exit();
}

if (!isset($_SESSION['role'])) {
    $_SESSION['role'] = 'guest';
    $_SESSION['username'] = 'Approval Link User';
    $_SESSION['email'] = '';
}

$currentRole = $_SESSION['role'] ?? 'supervisor';

// Check if current user is the Teacher in Charge
$isTeacherInCharge = false;
$loggedInEmployeeID = $_SESSION['employeeID'] ?? '';
$userPosition = '';

if ($loggedInEmployeeID) {
    $stmtCheck = $conn->prepare("SELECT firstname, middlename, lastname, position FROM accounts WHERE employeeID = ?");
    $stmtCheck->bind_param("s", $loggedInEmployeeID);
    $stmtCheck->execute();
    $resCheck = $stmtCheck->get_result();
    if ($uRow = $resCheck->fetch_assoc()) {
        $userPosition = $uRow['position'] ?? '';
        $formattedName = trim($uRow['lastname'] . ', ' . $uRow['firstname'] . ' ' . $uRow['middlename']);

        if (strpos($request['teacherInCharge'], $formattedName) !== false) {
            $isTeacherInCharge = true;
        }
    }
    $stmtCheck->close();
}

$isSubjectTeacher = (strpos(strtolower($userPosition), 'teacher') !== false);
$isLabPersonnel = ($userPosition === 'Sci. Res. Assist.' || $userPosition === 'Sci. Research Specialist I');
$isCIDChief = (strpos(strtolower($userPosition), 'chief') !== false);

$canApproveCurrentStep = false;
$currentApproverStep = '';

if ($supervisor_status === 'pending') {
    $canApproveCurrentStep = $isTeacherInCharge;
    $currentApproverStep = 'Supervisor';
} elseif ($supervisor_status === 'approved' && $subject_teacher_status === 'pending') {
    $canApproveCurrentStep = $isSubjectTeacher;
    $currentApproverStep = 'Area Unit Head (AUH)';
} elseif ($subject_teacher_status === 'approved' && $lab_personnel_status === 'pending') {
    $canApproveCurrentStep = $isLabPersonnel;
    $currentApproverStep = 'Lab Personnel';
} elseif ($lab_personnel_status === 'approved' && $cid_chief_status === 'pending') {
    $canApproveCurrentStep = $isCIDChief;
    $currentApproverStep = 'CID Chief';
}

if ($tokenValidStage !== '' && $tokenValidStage === $currentStage) {
    $canApproveCurrentStep = true;
}

$materials = [];
$stmtMat = $conn->prepare("SELECT quantity, item, description FROM scilab_material_requests WHERE formID = ?");
$stmtMat->bind_param("i", $requestId);
$stmtMat->execute();
$resMat = $stmtMat->get_result();

while ($m = $resMat->fetch_assoc()) {
    $itemText = $m['item'];
    if (!empty($m['description'])) {
        $itemText .= " (" . $m['description'] . ")";
    }
    $materials[] = "<div class='material-line'>• [" . $m['quantity'] . "x] " . htmlspecialchars($itemText) . "</div>";
}
$materialsText = !empty($materials) ? implode("", $materials) : '—';

function getStepClass($status, $prevStatus = 'approved')
{
    if ($status === 'approved')
        return 'approved';
    if ($status === 'rejected')
        return 'rejected';
    if ($status === 'pending' && $prevStatus === 'approved')
        return 'pending';
    return 'upcoming';
}

function getStepStatusText($status, $prevStatus = 'approved')
{
    if ($status === 'approved')
        return 'Approved';
    if ($status === 'rejected')
        return 'Rejected';
    if ($status === 'pending' && $prevStatus === 'approved')
        return 'Pending';
    return 'Upcoming';
}

function getStepIcon($class)
{
    if ($class === 'approved') {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><polyline points="20 6 9 17 4 12" /></svg>';
    } elseif ($class === 'pending') {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><circle cx="12" cy="12" r="10" /><polyline points="12 6 12 12 16 14" /></svg>';
    } elseif ($class === 'rejected') {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><circle cx="12" cy="12" r="10" /><line x1="15" y1="9" x2="9" y2="15" /><line x1="9" y1="9" x2="15" y2="15" /></svg>';
    } else {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><circle cx="12" cy="12" r="10" /></svg>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Supervisor Approval — Reserve-a-Lab</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet" />

    <?php include('helperFiles/headData.php'); ?>

    <style>
        /* ============================================================
       DESIGN TOKENS
    ============================================================ */
        :root {
            --primary: #0B1B62;
            --secondary: #4F73D9;
            --secondary-light: #EEF2FB;
            --success: #16A34A;
            --success-light: #DCFCE7;
            --danger: #DC2626;
            --danger-light: #FEE2E2;
            --muted: #94A3B8;
            --muted-light: #F1F5F9;
            --text-primary: #0B1B62;
            --text-secondary: #707070;
            --border: #E2E8F0;
            --card-bg: #FFFFFF;
            --input-bg: #F8FAFC;
            --bg-gradient: linear-gradient(135deg, #EBF0FA 0%, #dce6f8 100%);
            --shadow-card: 0 4px 24px rgba(11, 27, 98, 0.08), 0 1px 4px rgba(11, 27, 98, 0.04);
            --shadow-modal: 0 20px 60px rgba(11, 27, 98, 0.18), 0 4px 16px rgba(11, 27, 98, 0.1);
            --radius-card: 16px;
            --radius-btn: 10px;
            --radius-input: 10px;
            --font: 'Plus Jakarta Sans', system-ui, sans-serif;
            --transition: 0.22s cubic-bezier(.4, 0, .2, 1);
        }

        /* ============================================================
       RESET & BASE
    ============================================================ */


        body {
            font-family: var(--font);
            background: var(--bg-gradient);
            min-height: 100vh;
            color: var(--text-primary);
        }

        /* ============================================================
       PAGE WRAPPER
    ============================================================ */
        .page {
            max-width: 860px;
            margin: 0 auto;
            padding: 40px 20px 64px;
        }

        .page-header {
            margin-bottom: 32px;
        }

        .page-label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--secondary);
            margin-bottom: 6px;
        }

        .page-title {
            font-size: 26px;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: -0.03em;
            line-height: 1.2;
        }

        .page-subtitle {
            color: var(--text-secondary);
            font-size: 14px;
            margin-top: 5px;
        }

        /* ============================================================
       SECTION: APPROVAL TRACKER
    ============================================================ */
        .card {
            background: var(--card-bg);
            border-radius: var(--radius-card);
            box-shadow: var(--shadow-card);
            border: 1px solid rgba(226, 232, 240, 0.7);
        }

        .card-inner {
            padding: 28px 32px;
        }

        .section-label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.09em;
            color: var(--text-secondary);
            margin-bottom: 24px;
        }

        /* ---- Step Tracker ---- */
        .tracker {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            position: relative;
            gap: 0;
        }

        /* Connecting line behind steps */
        .tracker::before {
            content: '';
            position: absolute;
            top: 22px;
            left: calc(12.5% - 0px);
            right: calc(12.5% - 0px);
            height: 3px;
            background: var(--border);
            border-radius: 3px;
            z-index: 0;
        }

        /* Progress fill line */
        .tracker-progress {
            position: absolute;
            top: 22px;
            left: calc(12.5% - 0px);
            height: 3px;
            background: linear-gradient(90deg, var(--success) 0%, var(--secondary) 100%);
            border-radius: 3px;
            z-index: 1;
            transition: width 0.8s cubic-bezier(.4, 0, .2, 1);
        }

        .step {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            position: relative;
            z-index: 2;
        }

        .step-dot {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: 700;
            border: 3px solid var(--border);
            background: #fff;
            transition: var(--transition);
            position: relative;
        }

        .step.approved .step-dot {
            background: var(--success-light);
            border-color: var(--success);
            color: var(--success);
        }

        .step.pending .step-dot {
            background: var(--secondary-light);
            border-color: var(--secondary);
            color: var(--secondary);
            animation: pulse-ring 2s ease-in-out infinite;
        }


        .step.rejected .step-dot {
            background: var(--danger-light);
            border-color: var(--danger);
            color: var(--danger);
        }

        .step.rejected .step-status {
            background: var(--danger-light);
            color: var(--danger);
        }

        .step.upcoming .step-dot {
            background: var(--muted-light);
            border-color: var(--border);
            color: var(--muted);
        }

        @keyframes pulse-ring {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(79, 115, 217, 0.35);
            }

            50% {
                box-shadow: 0 0 0 8px rgba(79, 115, 217, 0);
            }
        }

        .step-info {
            text-align: center;
        }

        .step-role {
            font-size: 12px;
            font-weight: 700;
            color: var(--text-primary);
            white-space: nowrap;
        }

        .step-status {
            font-size: 11px;
            font-weight: 600;
            padding: 2px 9px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 4px;
        }

        .step.approved .step-status {
            background: var(--success-light);
            color: var(--success);
        }

        .step.pending .step-status {
            background: var(--secondary-light);
            color: var(--secondary);
        }

        .step.upcoming .step-status {
            background: var(--muted-light);
            color: var(--muted);
        }

        /* ============================================================
       SECTION: RESERVATION SUMMARY
    ============================================================ */
        .summary-card {
            margin-top: 24px;
        }

        .summary-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 24px 32px 16px;
            border-bottom: 1px solid var(--border);
        }

        .summary-head-left h3 {
            font-size: 16px;
            font-weight: 800;
            color: var(--primary);
        }

        .summary-head-left p {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 2px;
        }

        .request-id {
            font-size: 12px;
            font-weight: 700;
            color: var(--secondary);
            background: var(--secondary-light);
            padding: 4px 12px;
            border-radius: 20px;
            border: 1px solid rgba(79, 115, 217, 0.2);
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0;
        }

        .summary-field {
            padding: 19px 32px;
            border-right: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }

        .summary-field:nth-child(3n) {
            border-right: none;
        }

        .summary-field:nth-last-child(-n+3) {
            border-bottom: none;
        }

        .field-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--muted);
            margin-bottom: 5px;
        }

        .field-value {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .field-value.truncate {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 180px;
        }

        .summary-footer {
            padding: 20px 32px;
            display: flex;
            justify-content: flex-end;
            border-top: 1px solid var(--border);
        }

        /* ============================================================
       BUTTONS
    ============================================================ */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 10px 22px;
            border-radius: var(--radius-btn);
            font-family: var(--font);
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            transition: var(--transition);
            text-decoration: none;
            white-space: nowrap;
        }

        .btn-primary {
            background: var(--secondary);
            color: #fff;
            box-shadow: 0 2px 8px rgba(79, 115, 217, 0.35);
        }

        .btn-primary:hover {
            background: #3d5ec7;
            box-shadow: 0 4px 14px rgba(79, 115, 217, 0.45);
            transform: translateY(-1px);
        }

        .btn-success {
            background: var(--success);
            color: #fff;
            box-shadow: 0 2px 8px rgba(22, 163, 74, 0.3);
        }

        .btn-success:hover {
            background: #15803d;
            transform: translateY(-1px);
        }

        .btn-danger {
            background: var(--danger-light);
            color: var(--danger);
            border: 1.5px solid rgba(220, 38, 38, 0.25);
        }

        .btn-danger:hover {
            background: #fecaca;
            transform: translateY(-1px);
        }

        .btn-ghost {
            background: var(--muted-light);
            color: var(--text-secondary);
            border: 1.5px solid var(--border);
        }

        .btn-ghost:hover {
            background: #e9edf4;
            color: var(--text-primary);
        }

        .btn svg {
            width: 15px;
            height: 15px;
            flex-shrink: 0;
        }

        /* ============================================================
       MODAL OVERLAY
    ============================================================ */
        .custom-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(11, 27, 98, 0.45);
            backdrop-filter: blur(4px);
            z-index: 200;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease;
        }

        .custom-modal-overlay.open {
            opacity: 1;
            pointer-events: all;
        }

        .custom-modal {
            background: var(--card-bg);
            border-radius: var(--radius-card);
            box-shadow: var(--shadow-modal);
            width: 100%;
            max-width: 620px;
            max-height: 90vh;
            overflow-y: auto;
            transform: scale(0.94) translateY(16px);
            transition: transform 0.28s cubic-bezier(.34, 1.56, .64, 1), opacity 0.25s ease;
            opacity: 0;
        }

        .custom-modal-overlay.open .custom-modal {
            transform: scale(1) translateY(0);
            opacity: 1;
        }

        /* scrollbar */
        .custom-modal::-webkit-scrollbar {
            width: 5px;
        }

        .custom-modal::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-modal::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 10px;
        }

        .custom-modal-header {
            padding: 24px 28px 19px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            position: sticky;
            top: 0;
            background: var(--card-bg);
            z-index: 1;
            border-radius: var(--radius-card) var(--radius-card) 0 0;
        }

        .custom-modal-header-left h2 {
            font-size: 17px;
            font-weight: 800;
            color: var(--primary);
        }

        .custom-modal-header-left p {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 3px;
        }

        .custom-modal-close {
            width: 32px;
            height: 32px;
            border: none;
            background: var(--muted-light);
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            transition: var(--transition);
            flex-shrink: 0;
        }

        .custom-modal-close:hover {
            background: var(--border);
            color: var(--primary);
        }

        .custom-modal-body {
            padding: 24px 28px;
        }

        .custom-modal-section-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.09em;
            color: var(--secondary);
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 1.5px solid var(--secondary-light);
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px 24px;
            margin-bottom: 24px;
        }

        .detail-field {}

        .detail-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--muted);
            margin-bottom: 4px;
        }

        .detail-value {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
            background: var(--input-bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 8px 12px;
            min-height: 38px;
            display: flex;
            align-items: center;
        }

        .detail-value.full-width {
            grid-column: 1 / -1;
        }

        .detail-field.full-width {
            grid-column: 1 / -1;
        }

        .detail-field.full-width .detail-value {
            align-items: flex-start;
            line-height: 1.5;
        }

        /* ---- Rejection area ---- */
        .reject-area {
            margin-top: 20px;
            display: none;
            animation: fade-in 0.22s ease;
        }

        .reject-area.visible {
            display: block;
        }

        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(6px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .reject-area label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: var(--danger);
            margin-bottom: 7px;
        }

        .reject-area textarea {
            width: 100%;
            background: var(--input-bg);
            border: 1.5px solid rgba(220, 38, 38, 0.4);
            border-radius: var(--radius-input);
            padding: 10px 12px;
            font-family: var(--font);
            font-size: 14px;
            color: var(--text-primary);
            resize: vertical;
            min-height: 90px;
            outline: none;
            transition: var(--transition);
        }

        .reject-area textarea:focus {
            border-color: var(--danger);
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12);
        }

        .reject-error {
            font-size: 12px;
            color: var(--danger);
            margin-top: 5px;
            display: none;
        }

        .reject-error.visible {
            display: block;
        }

        .custom-modal-footer {
            padding: 20px 28px;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .custom-modal-footer-right {
            margin-left: auto;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        /* ---- Status toast ---- */
        .toast {
            position: fixed;
            bottom: 32px;
            left: 50%;
            transform: translateX(-50%) translateY(20px);
            background: var(--primary);
            color: #fff;
            padding: 13px 22px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 8px 28px rgba(11, 27, 98, 0.3);
            z-index: 500;
            opacity: 0;
            transition: opacity 0.3s ease, transform 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        .toast.success {
            background: var(--success);
        }

        .toast.error {
            background: var(--danger);
        }

        /* ============================================================
       RESPONSIVE — MOBILE
    ============================================================ */
        @media (max-width: 640px) {
            .nav {
                padding: 0 16px;
            }

            .page {
                padding: 24px 16px 48px;
            }

            .page-title {
                font-size: 21px;
            }

            .card-inner {
                padding: 20px 16px;
            }

            /* Vertical tracker on mobile */
            .tracker {
                flex-direction: column;
                align-items: flex-start;
                gap: 0;
            }

            .tracker::before {
                top: calc(12.5% - 0px);
                bottom: calc(12.5% - 0px);
                left: 21px;
                right: auto;
                width: 3px;
                height: auto;
            }

            .tracker-progress {
                top: calc(12.5% - 0px);
                left: 21px;
                width: 3px !important;
                height: 50% !important;
                /* Approx 1 step done */
            }

            .step {
                flex-direction: row;
                align-items: center;
                gap: 14px;
                width: 100%;
                padding: 10px 0;
            }

            .step-dot {
                flex-shrink: 0;
            }

            .step-info {
                text-align: left;
            }

            .summary-grid {
                grid-template-columns: 1fr 1fr;
            }

            .summary-field {
                padding: 16px 20px;
            }

            .summary-field:nth-child(3n) {
                border-right: 1px solid var(--border);
            }

            .summary-field:nth-child(2n) {
                border-right: none;
            }

            .summary-head {
                flex-direction: column;
                gap: 10px;
                padding: 20px 20px 16px;
            }

            .summary-footer {
                padding: 16px 20px;
            }

            .custom-modal {
                max-height: 95vh;
            }

            .custom-modal-body {
                padding: 20px 20px;
            }

            .custom-modal-header {
                padding: 20px 20px 16px;
            }

            .custom-modal-footer {
                padding: 16px 20px;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }

            .custom-modal-footer-right {
                margin-left: 0;
                width: 100%;
                justify-content: flex-end;
            }

            .nav-badge {
                display: none;
            }
        }
    </style>
</head>

<body>

    <?php include('helperFiles/header.php'); ?>

    <!-- ============================================================
       PAGE
  ============================================================ -->
    <main class="page">

        <div class="page-header">
            <p class="page-label">Reservation Request</p>
            <h1 class="page-title">Approval Status</h1>
            <p class="page-subtitle">Track the progress of your laboratory reservation request.</p>
        </div>

        <!-- ==========================================
         SECTION 1 — APPROVAL STAGE TRACKER
    ========================================== -->
        <div class="card">
            <div class="card-inner">
                <p class="section-label">Approval Process</p>

                <div class="tracker" id="stepTracker">

                    <!-- Progress fill bar (width set by JS) -->
                    <div class="tracker-progress" id="trackerProgress"></div>

                    <!-- Step 1: Supervisor -->
                    <?php $s1Class = getStepClass($supervisor_status); ?>
                    <div class="step <?= $s1Class ?>" data-step="1">
                        <div class="step-dot"><?= getStepIcon($s1Class) ?></div>
                        <div class="step-info">
                            <div class="step-role">Supervisor</div>
                            <span class="step-status"><?= getStepStatusText($supervisor_status) ?></span>
                        </div>
                    </div>

                    <!-- Step 2: Area Unit Head (AUH) -->
                    <?php $s2Class = getStepClass($subject_teacher_status, $supervisor_status); ?>
                    <div class="step <?= $s2Class ?>" data-step="2">
                        <div class="step-dot"><?= getStepIcon($s2Class) ?></div>
                        <div class="step-info">
                            <div class="step-role">Area Unit Head (AUH)</div>
                            <span
                                class="step-status"><?= getStepStatusText($subject_teacher_status, $supervisor_status) ?></span>
                        </div>
                    </div>

                    <!-- Step 3: Lab Personnel -->
                    <?php $s3Class = getStepClass($lab_personnel_status, $subject_teacher_status); ?>
                    <div class="step <?= $s3Class ?>" data-step="3">
                        <div class="step-dot"><?= getStepIcon($s3Class) ?></div>
                        <div class="step-info">
                            <div class="step-role">Lab Personnel</div>
                            <span
                                class="step-status"><?= getStepStatusText($lab_personnel_status, $subject_teacher_status) ?></span>
                        </div>
                    </div>

                    <!-- Step 4: CID Chief -->
                    <?php $s4Class = getStepClass($cid_chief_status, $lab_personnel_status); ?>
                    <div class="step <?= $s4Class ?>" data-step="4">
                        <div class="step-dot"><?= getStepIcon($s4Class) ?></div>
                        <div class="step-info">
                            <div class="step-role">CID Chief</div>
                            <span
                                class="step-status"><?= getStepStatusText($cid_chief_status, $lab_personnel_status) ?></span>
                        </div>
                    </div>

                </div><!-- /tracker -->
            </div>
        </div><!-- /tracker card -->

        <!-- ==========================================
         SECTION 2 — RESERVATION SUMMARY CARD
    ========================================== -->
        <div class="card summary-card">

            <div class="summary-head">
                <div class="summary-head-left">
                    <h3>Reservation Summary</h3>
                    <p>Submitted on <?= htmlspecialchars($submissionDate) ?></p>
                </div>
                <span class="request-id">REQ-<?= htmlspecialchars(str_pad($requestId, 4, '0', STR_PAD_LEFT)) ?></span>
            </div>

            <div class="summary-grid">
                <div class="summary-field">
                    <div class="field-label">Requester</div>
                    <div class="field-value"><?= htmlspecialchars($name) ?></div>
                </div>
                <div class="summary-field">
                    <div class="field-label">Laboratory</div>
                    <div class="field-value"><?= htmlspecialchars($laboratoryName) ?></div>
                </div>
                <div class="summary-field">
                    <div class="field-label">Date</div>
                    <div class="field-value"><?= htmlspecialchars($date) ?></div>
                </div>
                <div class="summary-field">
                    <div class="field-label">Time</div>
                    <div class="field-value"><?= htmlspecialchars($time) ?></div>
                </div>
                <div class="summary-field">
                    <div class="field-label">Grade & Section</div>
                    <div class="field-value"><?= htmlspecialchars($grade . ' - ' . $section) ?></div>
                </div>
                <div class="summary-field">
                    <div class="field-label">Purpose</div>
                    <div class="field-value truncate" title="<?= htmlspecialchars($purpose) ?>">
                        <?= htmlspecialchars($purpose) ?>
                    </div>
                </div>
            </div>

            <div class="summary-footer">
                <button class="btn btn-primary" id="openModalBtn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                    View Full Details
                </button>
            </div>

        </div><!-- /summary card -->

    </main>

    <!-- ============================================================
       MODAL — FULL DETAILS
  ============================================================ -->
    <div class="custom-modal-overlay" id="modalOverlay" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="custom-modal" id="custom-modal">

            <!-- Modal Header -->
            <div class="custom-modal-header">
                <div class="custom-modal-header-left">
                    <h2 id="modalTitle">Full Reservation Details</h2>
                    <p>Request ID:
                        <strong>REQ-<?= htmlspecialchars(str_pad($requestId, 4, '0', STR_PAD_LEFT)) ?></strong>
                    </p>
                </div>
                <button class="custom-modal-close" id="modalCloseTop" aria-label="Close modal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"
                        stroke-linejoin="round" width="16" height="16">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="custom-modal-body">

                <!-- Requester Information -->
                <p class="custom-modal-section-title">Requester Information</p>
                <div class="detail-grid">
                    <div class="detail-field">
                        <div class="detail-label">Full Name</div>
                        <div class="detail-value">
                            <?= htmlspecialchars($name) ?>
                        </div>
                    </div>
                    <div class="detail-field">
                        <div class="detail-label">Student / Faculty ID</div>
                        <div class="detail-value">
                            <?= htmlspecialchars($requesterID) ?>
                        </div>
                    </div>
                    <div class="detail-field">
                        <div class="detail-label">Department</div>
                        <div class="detail-value">
                            <?= htmlspecialchars($requesterDept) ?>
                        </div>
                    </div>
                    <div class="detail-field">
                        <div class="detail-label">Contact Email</div>
                        <div class="detail-value">
                            <?= htmlspecialchars($requesterEmail) ?>
                        </div>
                    </div>
                </div>

                <!-- Reservation Details -->
                <p class="custom-modal-section-title">Reservation Details</p>
                <div class="detail-grid">
                    <div class="detail-field">
                        <div class="detail-label">Laboratory</div>
                        <div class="detail-value"><?= htmlspecialchars($laboratoryName) ?></div>
                    </div>
                    <div class="detail-field">
                        <div class="detail-label">Date of Use</div>
                        <div class="detail-value"><?= htmlspecialchars($date) ?></div>
                    </div>
                    <div class="detail-field">
                        <div class="detail-label">Time</div>
                        <div class="detail-value"><?= htmlspecialchars($time) ?></div>
                    </div>
                    <div class="detail-field">
                        <div class="detail-label">Teacher-in-Charge</div>
                        <div class="detail-value"><?= htmlspecialchars($teacherInCharge) ?></div>
                    </div>
                    <div class="detail-field full-width">
                        <div class="detail-label">Purpose / Activity</div>
                        <div class="detail-value">
                            <?= nl2br(htmlspecialchars($purpose)) ?>
                        </div>
                    </div>
                </div>

                <!-- Additional Notes -->
                <p class="custom-modal-section-title">Requested Materials</p>
                <div class="detail-grid">
                    <div class="detail-field full-width">
                        <div class="detail-label">Materials</div>
                        <div class="detail-value" style="display: block;">
                            <?= $materialsText ?>
                        </div>
                    </div>
                </div>

                <!-- Rejection Reason Area (conditionally shown) -->
                <div class="reject-area" id="rejectArea">
                    <label for="rejectReason">
                        ⚠ Reason for Rejection <span style="color:var(--danger)">*</span>
                    </label>
                    <textarea id="rejectReason"
                        placeholder="Please provide a clear reason why this request is being rejected…"
                        maxlength="500"></textarea>
                    <div class="reject-error" id="rejectError">
                        A rejection reason is required before confirming.
                    </div>
                    <div style="margin-top:0.85rem; display:flex; gap:0.65rem; justify-content:flex-end;">
                        <button class="btn btn-ghost" id="cancelRejectBtn">Cancel</button>
                        <button class="btn btn-danger" id="confirmRejectBtn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10" />
                                <line x1="15" y1="9" x2="9" y2="15" />
                                <line x1="9" y1="9" x2="15" y2="15" />
                            </svg>
                            Confirm Rejection
                        </button>
                    </div>
                </div>

            </div><!-- /modal-body -->

            <!-- Modal Footer — role-based buttons -->
            <div class="custom-modal-footer" id="modalFooter">
                <!-- Buttons injected by JS based on currentUserRole -->
            </div>

        </div><!-- /modal -->
    </div>

    <!-- ============================================================
       TOAST NOTIFICATION
  ============================================================ -->
    <div class="toast" id="toast" role="status" aria-live="polite"></div>

    <!-- ============================================================
       JAVASCRIPT
  ============================================================ -->
    <script>
        /* ============================================================
           ROLE SIMULATION
           Change `currentUserRole` to test different states:
             "Supervisor"        — can approve/reject (current approver)
             "Area Unit Head (AUH)" — not yet the approver
             "Laboratory Personnel"
             "CID Chief"
             "Student"           — requester / viewer only
        ============================================================ */
        const canApprove = <?= $canApproveCurrentStep ? 'true' : 'false' ?>;
        const currentApprover = "<?= $currentApproverStep ?>";
        const requestId = <?= json_encode($requestId) ?>;
        const approvalToken = <?= json_encode($token ?? '') ?>;

        /* ============================================================
           TRACKER PROGRESS BAR WIDTH
        ============================================================ */
        /* ============================================================
           TRACKER PROGRESS BAR WIDTH
           Based on how many steps are "approved"
        ============================================================ */
        (function initTracker() {
            const steps = document.querySelectorAll('.step');
            const approvedCount = [...steps].filter(s => s.classList.contains('approved')).length;
            const totalSteps = steps.length;
            // Width spans from first step center to last step center
            // Each step center is at (index / (total-1)) * 100% of inner width
            const progress = approvedCount / (totalSteps - 1); // 0 → 1
            const clampedPct = Math.min(Math.max(progress, 0), 1) * 100;
            document.getElementById('trackerProgress').style.width = clampedPct + '%';
        })();

        /* ============================================================
           MODAL OPEN / CLOSE
        ============================================================ */
        const overlay = document.getElementById('modalOverlay');
        const modal = document.getElementById('custom-modal');
        const openBtn = document.getElementById('openModalBtn');
        const closeTopBtn = document.getElementById('modalCloseTop');
        const modalFooter = document.getElementById('modalFooter');

        function openModal() {
            renderFooterButtons();
            overlay.classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            overlay.classList.remove('open');
            document.body.style.overflow = '';
            hideRejectArea();
        }

        openBtn.addEventListener('click', openModal);
        closeTopBtn.addEventListener('click', closeModal);

        // Close on overlay click (outside modal)
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closeModal();
        });

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && overlay.classList.contains('open')) closeModal();
        });

        /* ============================================================
           ROLE-BASED FOOTER BUTTONS
        ============================================================ */
        function renderFooterButtons() {
            modalFooter.innerHTML = '';

            if (canApprove) {
                /* ---- Current approver: Approve + Reject + Close ---- */
                const closeBtn = document.createElement('button');
                closeBtn.className = 'btn btn-ghost';
                closeBtn.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Close`;
                closeBtn.addEventListener('click', closeModal);

                const rejectBtn = document.createElement('button');
                rejectBtn.className = 'btn btn-danger';
                rejectBtn.id = 'rejectBtn';
                rejectBtn.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg> Reject`;
                rejectBtn.addEventListener('click', showRejectArea);

                const approveBtn = document.createElement('button');
                approveBtn.className = 'btn btn-success';
                approveBtn.id = 'approveBtn';
                approveBtn.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Approve`;
                approveBtn.addEventListener('click', handleApprove);

                const rightGroup = document.createElement('div');
                rightGroup.className = 'custom-modal-footer-right';
                rightGroup.appendChild(rejectBtn);
                rightGroup.appendChild(approveBtn);

                modalFooter.appendChild(closeBtn);
                modalFooter.appendChild(rightGroup);

            } else {
                /* ---- Not current approver: Close only ---- */
                const closeBtn = document.createElement('button');
                closeBtn.className = 'btn btn-ghost';
                closeBtn.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Close`;
                closeBtn.addEventListener('click', closeModal);

                const rightGroup = document.createElement('div');
                rightGroup.className = 'custom-modal-footer-right';
                rightGroup.appendChild(closeBtn);

                if (currentApprover) {
                    const infoSpan = document.createElement('span');
                    infoSpan.style.cssText = 'font-size:0.78rem;color:var(--muted);display:flex;align-items:center;gap:5px;';
                    infoSpan.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg> Awaiting <strong style="color:var(--secondary)">${currentApprover}</strong>&nbsp;action`;
                    modalFooter.appendChild(infoSpan);
                }

                modalFooter.appendChild(rightGroup);
            }
        }

        /* ==
           APPROVE HANDLER (SIMULATED)
        ============================================================ */
        async function executeAction(action, reason = null) {
            try {
                const response = await fetch('ajax/ajax_supervisor_action.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ request_id: requestId, action: action, reason: reason, token: approvalToken })
                });
                const data = await response.json();
                if (data.status === 'success') {
                    closeModal();
                    showApprovalToast('✓ ' + data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showApprovalToast('✗ ' + data.message, 'error');
                }
            } catch (error) {
                showApprovalToast('✗ Network error occurred.', 'error');
            }
        }

        function handleApprove() {
            const btn = document.getElementById('approveBtn');
            if (btn) btn.disabled = true;
            executeAction('approve');
        }

        /* ============================================================
           REJECT UX
        ============================================================ */
        const rejectArea = document.getElementById('rejectArea');
        const rejectReason = document.getElementById('rejectReason');
        const rejectError = document.getElementById('rejectError');

        function showRejectArea() {
            rejectArea.classList.add('visible');
            modal.scrollTo({ top: modal.scrollHeight, behavior: 'smooth' });
            rejectReason.focus();

            // Hide footer action buttons while rejection is active
            const approveBtn = document.getElementById('approveBtn');
            const rejectBtn = document.getElementById('rejectBtn');
            if (approveBtn) approveBtn.style.display = 'none';
            if (rejectBtn) rejectBtn.style.display = 'none';
        }

        function hideRejectArea() {
            rejectArea.classList.remove('visible');
            rejectReason.value = '';
            rejectError.classList.remove('visible');
        }

        document.getElementById('cancelRejectBtn').addEventListener('click', () => {
            hideRejectArea();
            // Restore buttons
            const approveBtn = document.getElementById('approveBtn');
            const rejectBtn = document.getElementById('rejectBtn');
            if (approveBtn) approveBtn.style.display = '';
            if (rejectBtn) rejectBtn.style.display = '';
        });

        document.getElementById('confirmRejectBtn').addEventListener('click', () => {
            const reason = rejectReason.value.trim();

            if (!reason) {
                rejectError.classList.add('visible');
                rejectReason.focus();
                rejectReason.style.borderColor = 'var(--danger)';
                return;
            }

            rejectError.classList.remove('visible');
            const btn = document.getElementById('confirmRejectBtn');
            if (btn) btn.disabled = true;
            executeAction('reject', reason);
        });

        rejectReason.addEventListener('input', () => {
            if (rejectReason.value.trim()) {
                rejectError.classList.remove('visible');
                rejectReason.style.borderColor = '';
            }
        });

        /* ============================================================
           TOAST HELPER
        ============================================================ */
        const toast = document.getElementById('toast');
        let toastTimer;

        function showApprovalToast(message, type = 'default') {
            clearTimeout(toastTimer);
            toast.textContent = message;
            toast.className = 'toast ' + (type === 'success' ? 'success' : type === 'error' ? 'error' : '');
            toast.classList.add('show');
            toastTimer = setTimeout(() => toast.classList.remove('show'), 3500);
        }
    </script>

</body>

</html>