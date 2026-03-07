<?php
include('helperFiles/db_connection.php');
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

$grade = $request['gradeLevel'];
$section = $request['sections'];
$date = $request['inclusiveDate'];
$time = $request['inclusiveTime'];
$laboratoryName = $request['scilabName'];
$purpose = $request['subjectTopic'];
$teacherInCharge = $request['teacherInCharge'];

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
        // Match format from forms.php: lastname . ', ' . firstname . ' ' . middlename
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

if ($supervisor_status === 'pending') {
    $canApproveCurrentStep = $isTeacherInCharge;
} elseif ($supervisor_status === 'approved' && $subject_teacher_status === 'pending') {
    $canApproveCurrentStep = $isSubjectTeacher;
} elseif ($subject_teacher_status === 'approved' && $lab_personnel_status === 'pending') {
    $canApproveCurrentStep = $isLabPersonnel;
} elseif ($lab_personnel_status === 'approved' && $cid_chief_status === 'pending') {
    $canApproveCurrentStep = $isCIDChief;
}

include('helperFiles/headData.php');
include('helperFiles/header.php');
?>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    background-image: linear-gradient(rgba(0,0,0,0.45),rgba(0,0,0,0.45)),url(img/background.jpg);
    background-position: center;
    background-size: cover;
    background-attachment: fixed;
    color: #ffffff;
    min-height: 100vh;
}

/* Navbar readability */
.navbar {
    background: rgba(255, 255, 255, 0.95) !important;
    backdrop-filter: blur(10px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.container {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
    min-height: 88vh;
}

.approval-timeline {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-radius: 20px;
    padding: 28px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    margin-bottom: 24px;
}

.timeline-header {
    font-size: 16px;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 24px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.timeline-steps {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
}

.timeline-line {
    position: absolute;
    top: 20px;
    left: 40px;
    right: 40px;
    height: 2px;
    background: rgba(255, 255, 255, 0.2);
    z-index: 0;
}

.timeline-step {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    z-index: 1;
}

.step-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: 700;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border: 2px solid rgba(255, 255, 255, 0.3);
    color: rgba(255, 255, 255, 0.4);
    margin-bottom: 12px;
    transition: all 0.3s ease;
}

.step-pending .step-icon {
    background: rgba(245, 158, 11, 0.3);
    color: #fef3c7;
    border-color: rgba(245, 158, 11, 0.5);
    box-shadow: 0 0 16px rgba(245, 158, 11, 0.3);
}

.step-approved .step-icon {
    background: rgba(34, 197, 94, 0.35);
    color: #dcfce7;
    border-color: rgba(34, 197, 94, 0.5);
    box-shadow: 0 0 20px rgba(34, 197, 94, 0.4);
}

.step-locked .step-icon {
    background: rgba(148, 163, 184, 0.25);
    color: rgba(255, 255, 255, 0.4);
    border-color: rgba(255, 255, 255, 0.15);
    box-shadow: none;
}

.step-label {
    font-size: 13px;
    font-weight: 600;
    color: #ffffff;
    text-align: center;
    line-height: 1.3;
    max-width: 100px;
}

.step-locked .step-label {
    color: rgba(255, 255, 255, 0.5);
}

.step-status {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 6px;
    padding: 4px 10px;
    border-radius: 8px;
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
}

.step-pending .step-status {
    background: rgba(245, 158, 11, 0.2);
    color: #fef3c7;
    border: 1px solid rgba(245, 158, 11, 0.3);
}

.step-approved .step-status {
    background: rgba(34, 197, 94, 0.25);
    color: #dcfce7;
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.step-locked .step-status {
    background: rgba(148, 163, 184, 0.15);
    color: rgba(255, 255, 255, 0.4);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.glass-card {
    min-height: 200px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-radius: 24px;
    padding: 32px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    position: relative;
    overflow: hidden;
}

.glass-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
}

.request-text {
    font-size: 18px;
    line-height: 1.8;
    color: #ffffff;
    margin-bottom: 24px;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.btn-view {
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: #ffffff;
    padding: 14px 32px;
    border-radius: 16px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
}

.btn-view:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 20px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.modal-overlay.active {
    display: flex;
    opacity: 1;
}

.modal-content {
    background: rgba(30, 41, 59, 0.85);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-radius: 24px;
    padding: 40px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 16px 48px rgba(0, 0, 0, 0.2);
    max-width: 600px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    transform: scale(0.9);
    transition: transform 0.3s ease;
}

.modal-overlay.active .modal-content {
    transform: scale(1);
}

.modal-header {
    margin-bottom: 32px;
    padding-bottom: 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.modal-title {
    font-size: 28px;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 8px;
}

.detail-group {
    margin-bottom: 24px;
}

.detail-label {
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 8px;
    font-weight: 600;
}

.detail-value {
    font-size: 18px;
    color: #ffffff;
    font-weight: 500;
    line-height: 1.6;
}

.modal-actions {
    display: flex;
    gap: 16px;
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid rgba(255, 255, 255, 0.2);
}

.btn-action {
    flex: 1;
    padding: 16px 24px;
    border-radius: 16px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    border: 1px solid rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.btn-action:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-approve {
    background: rgba(34, 197, 94, 0.3);
    color: #dcfce7;
    border-color: rgba(34, 197, 94, 0.4);
}

.btn-approve:hover:not(:disabled) {
    background: rgba(34, 197, 94, 0.4);
    box-shadow: 0 0 24px rgba(34, 197, 94, 0.4);
    transform: translateY(-2px);
}

.btn-reject {
    background: rgba(239, 68, 68, 0.3);
    color: #fee2e2;
    border-color: rgba(239, 68, 68, 0.4);
}

.btn-reject:hover:not(:disabled) {
    background: rgba(239, 68, 68, 0.4);
    box-shadow: 0 0 24px rgba(239, 68, 68, 0.4);
    transform: translateY(-2px);
}

.btn-close {
    background: rgba(255, 255, 255, 0.2);
    color: #ffffff;
}

.btn-close:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
}

.loading {
    pointer-events: none;
    opacity: 0.7;
}

.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    margin: -10px 0 0 -10px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: #ffffff;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.error-message {
    background: rgba(239, 68, 68, 0.25);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border: 1px solid rgba(239, 68, 68, 0.4);
    color: #fee2e2;
    padding: 16px;
    border-radius: 12px;
    margin-top: 16px;
    font-size: 14px;
    display: none;
}

.error-message.show {
    display: block;
}

.success-message {
    background: rgba(34, 197, 94, 0.25);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border: 1px solid rgba(34, 197, 94, 0.4);
    color: #dcfce7;
    padding: 16px;
    border-radius: 12px;
    margin-top: 16px;
    font-size: 14px;
    display: none;
}

.success-message.show {
    display: block;
}

@media (max-width: 768px) {
    .container {
        padding: 10px;
    }

    .approval-timeline {
        padding: 20px;
    }

    .timeline-steps {
        flex-direction: column;
        gap: 24px;
    }

    .timeline-line {
        display: none;
    }

    .timeline-step {
        flex-direction: row;
        width: 100%;
        justify-content: flex-start;
        gap: 16px;
    }

    .step-icon {
        margin-bottom: 0;
    }

    .step-label {
        text-align: left;
        max-width: none;
    }

    .glass-card {
        padding: 24px;
        border-radius: 20px;
    }

    .request-text {
        font-size: 16px;
    }

    .modal-content {
        padding: 28px;
        border-radius: 20px;
    }

    .modal-title {
        font-size: 24px;
    }

    .detail-value {
        font-size: 16px;
    }

    .modal-actions {
        flex-direction: column;
    }

    .btn-action {
        width: 100%;
    }
}

@media (max-width: 480px) {
    body {
        padding: 10px;
    }

    .glass-card {
        padding: 20px;
    }

    .request-text {
        font-size: 15px;
    }

    .modal-content {
        padding: 24px;
    }
}

</style>

<div class="container">
    <div class="approval-timeline">
        <div class="timeline-header">Approval Progress</div>
        <div class="timeline-steps">
            <div class="timeline-line"></div>
            
            <div class="timeline-step step-<?= $supervisor_status ?>">
                <div class="step-icon"><?= $supervisor_status === 'approved' ? '✓' : '1' ?></div>
                <div class="step-label">Supervisor</div>
                <div class="step-status"><?= ucfirst($supervisor_status) ?></div>
            </div>
            
            <div class="timeline-step step-<?= $supervisor_status === 'approved' ? $subject_teacher_status : 'locked' ?>">
                <div class="step-icon"><?= $subject_teacher_status === 'approved' ? '✓' : '2' ?></div>
                <div class="step-label">Subject Teacher</div>
                <div class="step-status"><?= $supervisor_status === 'approved' ? ucfirst($subject_teacher_status) : 'Locked' ?></div>
            </div>
            
            <div class="timeline-step step-<?= $supervisor_status === 'approved' && $subject_teacher_status === 'approved' ? $lab_personnel_status : 'locked' ?>">
                <div class="step-icon"><?= $lab_personnel_status === 'approved' ? '✓' : '3' ?></div>
                <div class="step-label">Lab Personnel</div>
                <div class="step-status"><?= $supervisor_status === 'approved' && $subject_teacher_status === 'approved' ? ucfirst($lab_personnel_status) : 'Locked' ?></div>
            </div>
            
            <div class="timeline-step step-<?= $supervisor_status === 'approved' && $subject_teacher_status === 'approved' && $lab_personnel_status === 'approved' ? $cid_chief_status : 'locked' ?>">
                <div class="step-icon"><?= $cid_chief_status === 'approved' ? '✓' : '4' ?></div>
                <div class="step-label">CID Chief</div>
                <div class="step-status"><?= $supervisor_status === 'approved' && $subject_teacher_status === 'approved' && $lab_personnel_status === 'approved' ? ucfirst($cid_chief_status) : 'Locked' ?></div>
            </div>
        </div>
    </div>

    <div class="glass-card">
        <p class="request-text">
            <?= htmlspecialchars($name) ?> from <?= htmlspecialchars($grade) ?> - <?= htmlspecialchars($section) ?> requests to be supervised on <?= htmlspecialchars($date) ?> <?= htmlspecialchars($time) ?> at <?= htmlspecialchars($laboratoryName) ?>.
        </p>
        
        <button class="btn-view" onclick="openModal()">View Details</button>
    </div>
</div>

<div class="modal-overlay" id="modalOverlay" onclick="closeModalOnBackdrop(event)">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 class="modal-title">Request Details</h2>
        </div>
        
        <div id="supervisor-conflict-warning"></div>
        
        <div class="detail-group">
            <div class="detail-label">Student Name</div>
            <div class="detail-value"><?= htmlspecialchars($name) ?></div>
        </div>
        
        <div class="detail-group">
            <div class="detail-label">Grade & Section</div>
            <div class="detail-value"><?= htmlspecialchars($grade) ?> - <?= htmlspecialchars($section) ?></div>
        </div>
        
        <div class="detail-group">
            <div class="detail-label">Date and Time</div>
            <div class="detail-value"><?= htmlspecialchars($date) ?> at <?= htmlspecialchars($time) ?></div>
        </div>
        
        <div class="detail-group">
            <div class="detail-label">Laboratory Name</div>
            <div class="detail-value"><?= htmlspecialchars($laboratoryName) ?></div>
        </div>
        
        <div class="detail-group">
            <div class="detail-label">Purpose of Use</div>
            <div class="detail-value"><?= htmlspecialchars($purpose) ?></div>
        </div>
        
        <div class="detail-group">
            <div class="detail-label">Teacher-in-Charge</div>
            <div class="detail-value"><?= htmlspecialchars($teacherInCharge) ?></div>
        </div>
        
        <div class="error-message" id="errorMessage"></div>
        <div class="success-message" id="successMessage"></div>
        
        <div class="modal-actions" id="defaultActions">
            <?php if ($canApproveCurrentStep): ?>
                <button class="btn-action btn-approve" id="btnApprove" onclick="handleAction('approve')">Approve</button>
                <button class="btn-action btn-reject" id="btnReject" onclick="initiateRejection()">Reject</button>
            <?php endif; ?>
            <button class="btn-action btn-close" onclick="closeModal()">Close</button>
        </div>
    </div>
</div>

<script>
const requestId = <?= json_encode($requestId) ?>;
const modalOverlay = document.getElementById('modalOverlay');
const errorMessage = document.getElementById('errorMessage');
const successMessage = document.getElementById('successMessage');

function openModal() {
    modalOverlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    modalOverlay.classList.remove('active');
    document.body.style.overflow = '';
}

function closeModalOnBackdrop(event) {
    if (event.target === modalOverlay) {
        closeModal();
    }
}

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' && modalOverlay.classList.contains('active')) {
        closeModal();
    }
});

function initiateRejection() {
    const reason = prompt("Please provide a reason for rejection:");
    if (reason === null) { // User clicked cancel
        return;
    }
    if (reason.trim() === "") {
        alert("Rejection reason cannot be empty.");
        return;
    }
    handleAction('reject', reason);
}

async function handleAction(action, reason = null) {
    const btnApprove = document.getElementById('btnApprove');
    const btnReject = document.getElementById('btnReject');
    const button = action === 'approve' ? btnApprove : btnReject;
    
    if (!button) return;
    
    button.classList.add('loading');
    if (btnApprove) btnApprove.disabled = true;
    if (btnReject) btnReject.disabled = true;
    errorMessage.classList.remove('show');
    successMessage.classList.remove('show');
    
    try {
        const response = await fetch('ajax/ajax_supervisor_action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                request_id: requestId,
                action: action,
                reason: reason
            })
        });
        
        if (!response.ok) {
            throw new Error(`Server Error: ${response.status} ${response.statusText}`);
        }

        const text = await response.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Server response:', text);
            throw new Error('Invalid JSON response from server. Check console for details.');
        }
        
        if (data.success) {
            successMessage.textContent = 'Request ' + (action === 'approve' ? 'approved' : 'rejected') + ' successfully.';
            successMessage.classList.add('show');
            
            setTimeout(() => {
                window.location.reload();
            }, 1200);
        } else {
            throw new Error(data.message || 'An error occurred');
        }
    } catch (error) {
        errorMessage.textContent = error.message;
        errorMessage.classList.add('show');
        button.classList.remove('loading');
        if (btnApprove) btnApprove.disabled = false;
        if (btnReject) btnReject.disabled = false;
    }
}

// Check for conflicting schedules on load
const inclusiveTimeString = <?= json_encode($time) ?>;
const timeParts = inclusiveTimeString.split(' to ');
if (timeParts.length === 2) {
    const formData = new URLSearchParams();
    formData.append('action', 'check_conflict');
    formData.append('scilabName', <?= json_encode($laboratoryName) ?>);
    formData.append('date', <?= json_encode($date) ?>);
    formData.append('startTime', timeParts[0].trim());
    formData.append('endTime', timeParts[1].trim());
    formData.append('exclude_id', requestId);

    fetch('ajax/ajax_forms.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: formData.toString()
    })
    .then(response => response.json())
    .then(res => {
        if (res.status === 'success') {
            const warningContainer = document.getElementById('supervisor-conflict-warning');
            if (res.conflict_type === 'approved') {
                warningContainer.innerHTML = `
                    <div class="error-message show" style="margin-bottom: 24px; text-shadow: none;">
                        <strong><i class="glyphicon glyphicon-ban-circle"></i> Severe Conflict:</strong> An <b>approved</b> request already exists for this timeframe (${res.details.time}). 
                        Approving this request will result in a double-booked laboratory.
                    </div>
                `;
                const btnApprove = document.getElementById('btnApprove');
                if (btnApprove) btnApprove.disabled = true;
            } else if (res.conflict_type === 'pending') {
                warningContainer.innerHTML = `
                    <div style="background: rgba(245, 158, 11, 0.25); border: 1px solid rgba(245, 158, 11, 0.4); color: #fef3c7; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-size: 14px; text-shadow: none;">
                        <strong><i class="glyphicon glyphicon-warning-sign"></i> Pending Conflict:</strong> There is another pending request for this timeframe (${res.details.time} - ${res.details.subject}).
                    </div>
                `;
            }
        }
    })
    .catch(err => console.error('Conflict check error:', err));
}
</script>
<?php include('helperFiles/footer.php'); ?>