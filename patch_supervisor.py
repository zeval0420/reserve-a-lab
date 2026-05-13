import re
import sys

with open('/opt/lampp/htdocs/reserve-a-lab/reserve-a-lab/supervisor.php', 'r') as f:
    content = f.read()

# 1. Insert PHP block at the top
php_logic = """<?php
include('../scilab/helperFiles/db_connection.php');
include('helperFiles/session_handler.php');

$email = $_SESSION['email'] ?? null;
$username = $_SESSION['username'] ?? null;

$requestId = $_GET['id'] ?? null;

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
$stmtUser = $conn->prepare("SELECT firstname, middlename, lastname FROM accounts WHERE employeeID = ?");
$stmtUser->bind_param("s", $requesterID);
$stmtUser->execute();
$resUser = $stmtUser->get_result();

if ($userRow = $resUser->fetch_assoc()) {
    $name = $userRow['firstname'] . ' ' . (empty($userRow['middlename']) ? '' : $userRow['middlename'] . ' ') . $userRow['lastname'];
} else {
    // Try fetching from student
    $stmtUser = $conn->prepare("SELECT firstname, middlename, lastname FROM student WHERE LRN = ?");
    $stmtUser->bind_param("s", $requesterID);
    $stmtUser->execute();
    $resUser = $stmtUser->get_result();
    
    if ($userRow = $resUser->fetch_assoc()) {
        $name = $userRow['firstname'] . ' ' . (empty($userRow['middlename']) ? '' : $userRow['middlename'] . ' ') . $userRow['lastname'];
    }
}

$grade = $request['gradeLevel'] ?? '';
$section = $request['sections'] ?? '';
$date = $request['inclusiveDate'] ?? '';
$time = $request['inclusiveTime'] ?? '';
$laboratoryName = $request['scilabName'] ?? '';
$purpose = $request['subjectTopic'] ?? '';
$teacherInCharge = $request['teacherInCharge'] ?? '';
$submissionDate = date('F j, Y \\a\\t h:i A', strtotime($request['created_at'] ?? 'now'));

$supervisor_status = $request['supervisor_status'] ?? 'pending';
$subject_teacher_status = $request['subject_teacher_status'] ?? 'pending';
$lab_personnel_status = $request['lab_personnel_status'] ?? 'pending';
$cid_chief_status = $request['cid_chief_status'] ?? 'pending';

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
    $currentApproverStep = 'Subject Teacher';
} elseif ($subject_teacher_status === 'approved' && $lab_personnel_status === 'pending') {
    $canApproveCurrentStep = $isLabPersonnel;
    $currentApproverStep = 'Lab Personnel';
} elseif ($lab_personnel_status === 'approved' && $cid_chief_status === 'pending') {
    $canApproveCurrentStep = $isCIDChief;
    $currentApproverStep = 'CID Chief';
}

$materials = [];
$stmtMat = $conn->prepare("SELECT quantity, item, description FROM scilab_material_requests WHERE formID = ?");
$stmtMat->bind_param("i", $requestId);
$stmtMat->execute();
$resMat = $stmtMat->get_result();

while ($m = $resMat->fetch_assoc()) {
    $itemText = $m['item'];
    if (!empty($m['description'])) {
        $itemText .= " (".$m['description'].")";
    }
    $materials[] = "<div class='material-line'>• [".$m['quantity']."x] ".htmlspecialchars($itemText)."</div>";  
}
$materialsText = !empty($materials) ? implode("", $materials) : '—';

function getStepClass($status, $prevStatus = 'approved') {
    if ($status === 'approved') return 'approved';
    if ($status === 'rejected') return 'rejected';
    if ($status === 'pending' && $prevStatus === 'approved') return 'pending';
    return 'upcoming';
}

function getStepStatusText($status, $prevStatus = 'approved') {
    if ($status === 'approved') return 'Approved';
    if ($status === 'rejected') return 'Rejected';
    if ($status === 'pending' && $prevStatus === 'approved') return 'Pending';
    return 'Upcoming';
}

function getStepIcon($class) {
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
"""

content = re.sub(r'<\?php.*?Frontend only\. No backend logic or DB calls\..*?\?>\s*', php_logic, content, flags=re.DOTALL)

# 2. Insert headData.php and custom CSS for rejected step
head_replacement = """    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet" />
        
    <?php include('helperFiles/headData.php'); ?>
"""
content = content.replace("""    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet" />""", head_replacement)

reject_css = """
        .step.rejected .step-dot {
            background: var(--danger-light);
            border-color: var(--danger);
            color: var(--danger);
        }

        .step.rejected .step-status {
            background: var(--danger-light);
            color: var(--danger);
        }
"""
content = content.replace("        .step.upcoming .step-dot {", reject_css + "\n        .step.upcoming .step-dot {")


# 3. Replace static nav with header.php
nav_regex = r'<!-- =+[\s\n]*NAV[\s\n]*=+ -->[\s\n]*<nav class="nav">.*?</nav>'
content = re.sub(nav_regex, "<?php include('helperFiles/header.php'); ?>", content, flags=re.DOTALL)

# 4. Replace Tracker steps
tracker_steps = """                    <!-- Step 1: Supervisor -->
                    <?php $s1Class = getStepClass($supervisor_status); ?>
                    <div class="step <?= $s1Class ?>" data-step="1">
                        <div class="step-dot"><?= getStepIcon($s1Class) ?></div>
                        <div class="step-info">
                            <div class="step-role">Supervisor</div>
                            <span class="step-status"><?= getStepStatusText($supervisor_status) ?></span>
                        </div>
                    </div>

                    <!-- Step 2: Subject Teacher -->
                    <?php $s2Class = getStepClass($subject_teacher_status, $supervisor_status); ?>
                    <div class="step <?= $s2Class ?>" data-step="2">
                        <div class="step-dot"><?= getStepIcon($s2Class) ?></div>
                        <div class="step-info">
                            <div class="step-role">Subject Teacher</div>
                            <span class="step-status"><?= getStepStatusText($subject_teacher_status, $supervisor_status) ?></span>
                        </div>
                    </div>

                    <!-- Step 3: Lab Personnel -->
                    <?php $s3Class = getStepClass($lab_personnel_status, $subject_teacher_status); ?>
                    <div class="step <?= $s3Class ?>" data-step="3">
                        <div class="step-dot"><?= getStepIcon($s3Class) ?></div>
                        <div class="step-info">
                            <div class="step-role">Lab Personnel</div>
                            <span class="step-status"><?= getStepStatusText($lab_personnel_status, $subject_teacher_status) ?></span>
                        </div>
                    </div>

                    <!-- Step 4: CID Chief -->
                    <?php $s4Class = getStepClass($cid_chief_status, $lab_personnel_status); ?>
                    <div class="step <?= $s4Class ?>" data-step="4">
                        <div class="step-dot"><?= getStepIcon($s4Class) ?></div>
                        <div class="step-info">
                            <div class="step-role">CID Chief</div>
                            <span class="step-status"><?= getStepStatusText($cid_chief_status, $lab_personnel_status) ?></span>
                        </div>
                    </div>"""
content = re.sub(r'<!-- Step 1: Supervisor -->.*?<!-- Step 4: CID Chief — Upcoming -->.*?</svg>\s*</div>\s*<div class="step-info">\s*<div class="step-role">CID Chief</div>\s*<span class="step-status">Upcoming</span>\s*</div>\s*</div>', tracker_steps, content, flags=re.DOTALL)

# 5. Replace Summary Grid Data
summary_replacement = """            <div class="summary-head">
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
            </div>"""
content = re.sub(r'<div class="summary-head">.*?</div>\s*</div>\s*</div>', summary_replacement, content, flags=re.DOTALL)

# 6. Replace Modal details
modal_header = """            <div class="modal-header">
                <div class="modal-header-left">
                    <h2 id="modalTitle">Full Reservation Details</h2>
                    <p>Request ID: <strong>REQ-<?= htmlspecialchars(str_pad($requestId, 4, '0', STR_PAD_LEFT)) ?></strong></p>
                </div>"""
content = re.sub(r'<div class="modal-header">\s*<div class="modal-header-left">\s*<h2 id="modalTitle">Full Reservation Details</h2>\s*<p>Request ID: <strong>REQ-2025-0047</strong></p>\s*</div>', modal_header, content)

modal_requester = """                <!-- Requester Information -->
                <p class="modal-section-title">Requester Information</p>
                <div class="detail-grid">
                    <div class="detail-field">
                        <div class="detail-label">Full Name</div>
                        <div class="detail-value"><?= htmlspecialchars($name) ?></div>
                    </div>
                    <div class="detail-field">
                        <div class="detail-label">Student / Faculty ID</div>
                        <div class="detail-value"><?= htmlspecialchars($requesterID) ?></div>
                    </div>
                    <div class="detail-field">
                        <div class="detail-label">Grade & Section</div>
                        <div class="detail-value"><?= htmlspecialchars($grade . ' - ' . $section) ?></div>
                    </div>
                </div>"""
content = re.sub(r'<!-- Requester Information -->.*?</p>\s*</div>\s*</div>', modal_requester, content, flags=re.DOTALL)

modal_reservation = """                <!-- Reservation Details -->
                <p class="modal-section-title">Reservation Details</p>
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
                <p class="modal-section-title">Requested Materials</p>
                <div class="detail-grid">
                    <div class="detail-field full-width">
                        <div class="detail-label">Materials</div>
                        <div class="detail-value" style="display: block;">
                            <?= $materialsText ?>
                        </div>
                    </div>
                </div>"""
content = re.sub(r'<!-- Reservation Details -->.*?<!-- Rejection Reason Area \(conditionally shown\) -->', modal_reservation + "\n\n                <!-- Rejection Reason Area (conditionally shown) -->", content, flags=re.DOTALL)

# 7. Replace JS logic
js_logic = """        const canApprove = <?= $canApproveCurrentStep ? 'true' : 'false' ?>;
        const currentApprover = "<?= $currentApproverStep ?>";
        const requestId = <?= json_encode($requestId) ?>;

        /* ============================================================
           TRACKER PROGRESS BAR WIDTH
        ============================================================ */"""
content = re.sub(r'const currentUserRole.*?(/\* =+[\s\n]*TRACKER PROGRESS BAR WIDTH)', js_logic + r'\n        \1', content, flags=re.DOTALL)

js_render_footer = """        function renderFooterButtons() {
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
                rightGroup.className = 'modal-footer-right';
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
                rightGroup.className = 'modal-footer-right';
                rightGroup.appendChild(closeBtn);

                if (currentApprover) {
                    const infoSpan = document.createElement('span');
                    infoSpan.style.cssText = 'font-size:0.78rem;color:var(--muted);display:flex;align-items:center;gap:5px;';
                    infoSpan.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg> Awaiting <strong style="color:var(--secondary)">${currentApprover}</strong>&nbsp;action`;
                    modalFooter.appendChild(infoSpan);
                }

                modalFooter.appendChild(rightGroup);
            }
        }"""
content = re.sub(r'function renderFooterButtons\(\) \{.*?\}\s*/\* =+', js_render_footer + "\n\n        /* ==", content, flags=re.DOTALL)

js_action = """        async function executeAction(action, reason = null) {
            try {
                const response = await fetch('ajax/ajax_supervisor_action.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ request_id: requestId, action: action, reason: reason })
                });
                const data = await response.json();
                if (data.status === 'success') {
                    closeModal();
                    showToast('✓ ' + data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('✗ ' + data.message, 'error');
                }
            } catch (error) {
                showToast('✗ Network error occurred.', 'error');
            }
        }

        function handleApprove() {
            const btn = document.getElementById('approveBtn');
            if (btn) btn.disabled = true;
            executeAction('approve');
        }"""
content = re.sub(r'function handleApprove\(\) \{.*?\}', js_action, content, flags=re.DOTALL)

js_reject = """        document.getElementById('confirmRejectBtn').addEventListener('click', () => {
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
        });"""
content = re.sub(r"document\.getElementById\('confirmRejectBtn'\)\.addEventListener\('click', \(\) => \{.*?console\.info\('Rejection reason submitted:', reason\);\s*\}\);", js_reject, content, flags=re.DOTALL)

with open('/opt/lampp/htdocs/reserve-a-lab/reserve-a-lab/supervisor.php', 'w') as f:
    f.write(content)

