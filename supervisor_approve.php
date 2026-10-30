<?php
include('../scilab/helperFiles/db_connection.php');
// Starts the session so emailed-link visitors get a CSRF token for the approve/reject call
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
$requesterIsTeacher = false; // requester found in `accounts` => teacher/faculty (no CID Chief step)

if ($userRow = $resUser->fetch_assoc()) {
    $name = $userRow['firstname'] . ' ' . (empty($userRow['middlename']) ? '' : $userRow['middlename'] . ' ') . $userRow['lastname'];
    $requesterEmail = $userRow['email'] ?? 'N/A';
    $requesterDept = $userRow['position'] ?? 'N/A';
    $requesterIsTeacher = true;
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
$dateList = scilab_request_dates($conn, $request['id'], $request['inclusiveDate'] ?? '');
$date = scilab_format_dates($dateList); // readable list of every day, e.g. "Nov 3, 5, 9, 10-14, 2026"
$time = $request['inclusiveTime'] ?? '';
$laboratoryName = $request['scilabName'] ?? '';
$purpose = $request['subjectTopic'] ?? '';
$teacherInCharge = $request['teacherInCharge'] ?? '';
$submissionDate = date('F j, Y 	 h:i A', strtotime($request['created_at'] ?? 'now'));

$supervisor_status = $request['supervisor_status'] ?? 'pending';
$subject_teacher_status = $request['subject_teacher_status'] ?? 'pending';
$lab_personnel_status = $request['lab_personnel_status'] ?? 'pending';
$cid_chief_status = $request['cid_chief_status'] ?? 'pending';

// Stage order depends on the request's flow (helperFiles/stage_flow.php):
// subject-teacher flow = Subject Teacher -> Teacher-in-Charge -> Lab Personnel -> CID Chief,
// legacy flow = Supervisor -> AUH -> Lab Personnel -> CID Chief.
$isSubjectTeacherFlow = scilab_is_subject_teacher_flow($request);
$stageOrder = scilab_stage_order($request);
$subjectTeacherName = trim((string)($request['subjectTeacher'] ?? ''));
$currentStage = scilab_current_stage($request);

$tokenValidStage = '';
if ($token) {
    foreach (['supervisor', 'subject_teacher', 'lab_personnel', 'cid_chief'] as $stageName) {
        if (scilab_approval_token_valid($requestId, $stageName, $token)) {
            $tokenValidStage = $stageName;
            break;
        }
    }
}

// Anyone with the link (e.g. the requester) may view the progress tracker. Taking action
// still requires either an approver's login or a valid stage token. Visitors are
// deliberately NOT given a session identity here: ajax_supervisor_action.php treats any
// session with a username as authorized to act.
$currentRole = $_SESSION['role'] ?? 'guest';

// Check if current user is the Teacher in Charge (and, on the new flow, the selected Subject Teacher)
$isTeacherInCharge = false;
$isSelectedSubjectTeacher = false;
$loggedInEmployeeID = $_SESSION['employeeID'] ?? '';
$userPosition = '';

if ($loggedInEmployeeID) {
    $stmtCheck = $conn->prepare("SELECT firstname, middlename, lastname, position FROM accounts WHERE employeeID = ?");
    $stmtCheck->bind_param("s", $loggedInEmployeeID);
    $stmtCheck->execute();
    $resCheck = $stmtCheck->get_result();
    if ($uRow = $resCheck->fetch_assoc()) {
        $userPosition = $uRow['position'] ?? '';

        if (scilab_name_matches_user($uRow, $request['teacherInCharge'] ?? '')) {
            $isTeacherInCharge = true;
        }
        if ($isSubjectTeacherFlow && scilab_name_matches_user($uRow, $subjectTeacherName)) {
            $isSelectedSubjectTeacher = true;
        }
    }
    $stmtCheck->close();
}

// Legacy flow: any teacher position (or AUH designation holder) acts on the subject_teacher stage.
// New flow: only the Subject Teacher the requester selected.
$isSubjectTeacher = $isSubjectTeacherFlow
    ? $isSelectedSubjectTeacher
    : (strpos(strtolower($userPosition), 'teacher') !== false);
$isLabPersonnel = ($userPosition === 'Sci. Res. Assist.' || $userPosition === 'Sci. Research Specialist I');
$isCIDChief = (strpos(strtolower($userPosition), 'chief') !== false);

// Designation holders for the current school year may also act on these stages.
if ($loggedInEmployeeID) {
    if (!$isSubjectTeacherFlow && !$isSubjectTeacher && scilab_user_holds_designation($conn, $loggedInEmployeeID, 'AUH-' . ($request['subjectAcademicUnit'] ?? ''))) {
        $isSubjectTeacher = true;
    }
    if (!$isCIDChief && scilab_user_holds_designation($conn, $loggedInEmployeeID, 'CID Chief')) {
        $isCIDChief = true;
    }
}

// A request that already reached a terminal state has no actionable step, even if
// some stage columns are still 'pending' from an older record.
$requestIsTerminal = in_array(strtolower(trim((string)($request['statusScilabPersonnel'] ?? ''))), ['approved', 'rejected', 'cancelled'], true);
if ($requestIsTerminal) {
    $currentStage = '';
}

$canApproveCurrentStep = false;
$currentApproverStep = '';

// Which stages the logged-in user may act on.
$stageRoleFlags = [
    'supervisor' => $isTeacherInCharge,
    'subject_teacher' => $isSubjectTeacher,
    'lab_personnel' => $isLabPersonnel,
    'cid_chief' => $isCIDChief,
];

// The stage in progress (computed from statuses, so it is set even for a terminal request,
// where the footer only says who the request was waiting on).
$progressStage = scilab_current_stage($request);
if ($progressStage !== '') {
    $canApproveCurrentStep = !empty($stageRoleFlags[$progressStage]);
    $currentApproverStep = scilab_stage_label($request, $progressStage);
}

if ($tokenValidStage !== '' && $tokenValidStage === $currentStage) {
    $canApproveCurrentStep = true;
}

// The user's own step status: the first stage (in this request's order) they hold a role
// for that is no longer pending, else pending if they hold a role for any stage.
$userStepStatus = null;
foreach ($stageOrder as $stageKey) {
    if (empty($stageRoleFlags[$stageKey])) continue;
    $candidateStatus = $request[$stageKey . '_status'] ?? 'pending';
    if ($userStepStatus === null) $userStepStatus = $candidateStatus;
    if ($candidateStatus !== 'pending') {
        $userStepStatus = $candidateStatus;
        break;
    }
}

if ($tokenValidStage !== '' && $userStepStatus === null) {
    $userStepStatus = $request[$tokenValidStage . '_status'] ?? 'pending';
}

$hasAlreadyActed = ($userStepStatus !== null && $userStepStatus !== 'pending');
$lastAction = $userStepStatus;

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
    <!-- Self-hosted Plus Jakarta Sans (same variable woff2 files Google Fonts served, latin + latin-ext) -->
    <style>
        /* latin-ext */
        @font-face {
            font-family: 'Plus Jakarta Sans';
            font-style: normal;
            font-weight: 400 800;
            font-display: swap;
            src: url(assets/fonts/plus-jakarta-sans/plus-jakarta-sans-v12-latin-ext.woff2) format('woff2');
            unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C4, U+2113, U+2C60-2C7F, U+A720-A7FF;
        }

        /* latin */
        @font-face {
            font-family: 'Plus Jakarta Sans';
            font-style: normal;
            font-weight: 400 800;
            font-display: swap;
            src: url(assets/fonts/plus-jakarta-sans/plus-jakarta-sans-v12-latin.woff2) format('woff2');
            unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
        }
    </style>

    <?php $pageAssets = []; ?>
    <?php include('helperFiles/headData.php'); ?>
    <link rel="stylesheet" href="css/spinner.css?v=<?= filemtime(__DIR__ . '/css/spinner.css') ?>">
    <script src="js/ral_spinner.js?v=<?= filemtime(__DIR__ . '/js/ral_spinner.js') ?>"></script>

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
            --tracker-inset: 12.5%; /* half of one step column: 100% / (2 * steps) */
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
            left: var(--tracker-inset);
            right: var(--tracker-inset);
            height: 3px;
            background: var(--border);
            border-radius: 3px;
            z-index: 0;
        }

        /* Progress fill line */
        .tracker-progress {
            position: absolute;
            top: 22px;
            left: var(--tracker-inset);
            height: 3px;
            background: linear-gradient(90deg, var(--success) 0%, var(--secondary) 100%);
            border-radius: 3px;
            z-index: 1;
            transition: width 0.8s cubic-bezier(.4, 0, .2, 1);
        }

        .tracker.steps-3 { --tracker-inset: 16.6667%; }

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
            z-index: 200000;
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
            z-index: 10000;
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
                top: var(--tracker-inset);
                bottom: var(--tracker-inset);
                left: 21px;
                right: auto;
                width: 3px;
                height: auto;
            }

            .tracker-progress {
                top: var(--tracker-inset);
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

                <div class="tracker<?= $requesterIsTeacher ? ' steps-3' : '' ?>" id="stepTracker">

                    <!-- Progress fill bar (width set by JS) -->
                    <div class="tracker-progress" id="trackerProgress"></div>

                    <?php
                    // Steps follow this request's stage order. CID Chief is not shown for
                    // teacher/faculty requesters (auto-approved).
                    $trackerPrevStatus = 'approved';
                    $trackerStepNo = 0;
                    foreach ($stageOrder as $trackerStage):
                        if ($trackerStage === 'cid_chief' && $requesterIsTeacher) continue;
                        $trackerStatus = $request[$trackerStage . '_status'] ?? 'pending';
                        $trackerClass = getStepClass($trackerStatus, $trackerPrevStatus);
                        $trackerStepNo++;
                    ?>
                    <div class="step <?= $trackerClass ?>" data-step="<?= $trackerStepNo ?>" data-stage="<?= htmlspecialchars($trackerStage) ?>">
                        <div class="step-dot"><?= getStepIcon($trackerClass) ?></div>
                        <div class="step-info">
                            <div class="step-role"><?= htmlspecialchars(scilab_stage_label($request, $trackerStage)) ?></div>
                            <span
                                class="step-status"><?= getStepStatusText($trackerStatus, $trackerPrevStatus) ?></span>
                        </div>
                    </div>
                    <?php
                        $trackerPrevStatus = $trackerStatus;
                    endforeach;
                    ?>

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

        <?php if ($canApproveCurrentStep): ?>
        <div class="card" style="margin-top: 24px;">
            <div class="card-inner">

                <!-- Action Required Header + Buttons -->
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                    <div>
                        <p class="section-label" style="margin-bottom: 4px;">Action Required</p>
                        <p style="font-size: 14px; color: var(--text-secondary); margin: 0;">
                            Please review and approve or reject this request.
                        </p>
                    </div>

                    <div style="display: flex; gap: 12px; flex-shrink: 0;">
                        <button class="btn btn-danger" id="pageRejectBtn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="15" y1="9" x2="9" y2="15"/>
                                <line x1="9" y1="9" x2="15" y2="15"/>
                            </svg>
                            Reject
                        </button>

                        <button class="btn btn-success" id="pageApproveBtn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            Approve
                        </button>
                    </div>
                </div>

                <!-- Pending Conflict Warning -->
                <div id="pendingConflictWarning"></div>

            </div>
        </div>
        <?php elseif ($hasAlreadyActed): ?>

        <div class="card" style="margin-top: 24px;">
            <div class="card-inner" style="text-align: center; padding: 24px 32px;">
                <?php if ($lastAction === 'approved'): ?>
                <p style="font-size: 15px; font-weight: 700; color: var(--success); margin: 0;">
                    You have already approved this request.
                </p>
                <?php else: ?>
                <p style="font-size: 15px; font-weight: 700; color: var(--danger); margin: 0;">
                    You have denied this request due to <?= htmlspecialchars($request['feedback'] ?? 'the reason provided.') ?>
                </p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    </main>

    <!-- ============================================================
       MODAL — FULL DETAILS
  ============================================================ -->
    <div class="custom-modal-overlay" id="modalOverlay" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="custom-modal" id="custom-modal">

            <!-- Modal Header -->
            <div class="custom-modal-header">
                <div class="custom-modal-header-left">
                    <h2 id="modalTitle">FULL RESERVATION DETAILS</h2>
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
                    <?php if ($isSubjectTeacherFlow): ?>
                    <div class="detail-field">
                        <div class="detail-label">Subject Teacher</div>
                        <div class="detail-value"><?= htmlspecialchars($subjectTeacherName) ?></div>
                    </div>
                    <?php endif; ?>
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

    <!-- Shared footer: also carries the header's notification sidebar and profile popover scripts -->
    <?php include('helperFiles/footer.php'); ?>

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
            const bar = document.getElementById('trackerProgress');
            if (!bar || totalSteps < 2) return;
            const progress = approvedCount / (totalSteps - 1); // 0 → 1
            const clampedPct = Math.min(Math.max(progress, 0), 1) * 100;
            // The line spans (100% - 2 * inset) of the tracker: one step column minus half on each side
            const spanPct = 100 - (100 / totalSteps);
            bar.style.width = (clampedPct * spanPct / 100) + '%';
        })();

        /* ============================================================
        PENDING CONFLICT CHECK
        ============================================================ */
        (function checkPendingConflict() {

            const conflictWarning = document.getElementById('pendingConflictWarning');

            if (!conflictWarning) {
                return;
            }

            const scilabName = <?= json_encode($laboratoryName) ?>;
            const inclusiveDates = <?= json_encode($dateList) ?>;
            const inclusiveTime = <?= json_encode($time) ?>;

            // Parse inclusiveTime (e.g. "09:30 to 11:30")
            const timeParts = (inclusiveTime || '').split(' to ');

            if (
                timeParts.length !== 2 ||
                !scilabName ||
                !inclusiveDates.length
            ) {
                return;
            }

            $.post('ajax/ajax_forms.php', {
                action: 'check_conflict',
                scilabName: scilabName,
                dates: JSON.stringify(inclusiveDates),
                startTime: timeParts[0].trim(),
                endTime: timeParts[1].trim(),
                exclude_id: <?= json_encode($requestId) ?>
            }, function(res) {

                if (res.status === 'success') {

                    /* ====================================================
                    PENDING CONFLICT
                    Same functionality/design as admin_approve.php
                    ==================================================== */
                    if (res.conflict_type === 'pending') {

                        $('#pendingConflictWarning').html(`
                            <div class="alert alert-warning" style="margin-top:18px; margin-bottom:0; border-radius:8px;">
                                <strong>
                                    <i class="glyphicon glyphicon-warning-sign"></i>
                                    Pending Conflict:
                                </strong>
                                There is another pending request for this timeframe
                                (${ralEsc(res.details.time)} - ${ralEsc(res.details.subject)}).
                            </div>
                        `);

                    }
                }

            }, 'json');

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
                /* ---- Current approver: actions are on the page, modal is view-only ---- */
                const closeBtn = document.createElement('button');
                closeBtn.className = 'btn btn-ghost';
                closeBtn.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Close`;
                closeBtn.addEventListener('click', closeModal);

                modalFooter.appendChild(closeBtn);

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

        /* ============================================================
           PAGE-LEVEL APPROVE / REJECT BUTTONS
        ============================================================ */
        const pageApproveBtn = document.getElementById('pageApproveBtn');
        const pageRejectBtn = document.getElementById('pageRejectBtn');

        if (pageApproveBtn) {
            pageApproveBtn.addEventListener('click', function() {
                this.disabled = true;
                handleApprove();
            });
        }

        if (pageRejectBtn) {
            pageRejectBtn.addEventListener('click', function() {
                openModal();
                setTimeout(() => showRejectArea(), 100);
            });
        }

        /* ==
           APPROVE HANDLER (SIMULATED)
        ============================================================ */
        function reloadPage() {
            const url = new URL(window.location.href);
            url.searchParams.delete('refresh');
            url.searchParams.set('refresh', Date.now());
            window.location.href = url.toString();
        }

        let actionInFlight = false;
        const actionButtons = () => [pageApproveBtn, pageRejectBtn, document.getElementById('approveBtn'),
            document.getElementById('confirmRejectBtn'), document.getElementById('cancelRejectBtn')].filter(Boolean);

        async function executeAction(action, reason = null) {
            if (actionInFlight) return; // prevent double submit
            actionInFlight = true;
            actionButtons().forEach(b => b.disabled = true);
            RalSpinner.show(action === 'reject' ? 'Rejecting request...' : 'Approving request...');
            let succeeded = false;
            try {
                const response = await fetch('ajax/ajax_supervisor_action.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ request_id: requestId, action: action, reason: reason, token: approvalToken })
                });
                const data = await response.json();
                if (data.success) {
                    succeeded = true; // keep UI blocked until reload
                    closeModal();
                    showApprovalToast('✓ ' + (data.message || 'Action completed successfully.'), 'success');
                    setTimeout(reloadPage, 800);
                } else {
                    showApprovalToast('✗ ' + (data.message || 'Action could not be completed.'), 'error');
                }
            } catch (error) {
                showApprovalToast('✗ Network error occurred.', 'error');
            } finally {
                if (!succeeded) {
                    RalSpinner.hide();
                    actionInFlight = false;
                    actionButtons().forEach(b => b.disabled = false);
                }
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

            // Hide page-level action buttons while rejection is active
            if (pageApproveBtn) pageApproveBtn.style.display = 'none';
            if (pageRejectBtn) pageRejectBtn.style.display = 'none';
        }

        function hideRejectArea() {
            rejectArea.classList.remove('visible');
            rejectReason.value = '';
            rejectError.classList.remove('visible');
        }

        document.getElementById('cancelRejectBtn').addEventListener('click', () => {
            hideRejectArea();
            if (pageApproveBtn) pageApproveBtn.style.display = '';
            if (pageRejectBtn) pageRejectBtn.style.display = '';
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