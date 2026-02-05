<?php
include('helperFiles/db_connection.php');
include('helperFiles/session_handler.php');

$email = $_SESSION['email'] ?? null;
$username = $_SESSION['username'] ?? null;
/*
if(isset($_SESSION['role'])) {
    if($_SESSION['role'] === 'admin') header("Location: admin_home.php");
} else {
    header("Location: index.php");
    exit();
}
*/
$requestId = $_GET['id'] ?? null;

if (!$requestId) {
    die("Please provide a request ID.");
}

$sql = "SELECT * FROM scilab_form_requests WHERE id = '$requestId'";
$result = $conn->query($sql);
$request = $result->fetch_assoc();

if (!$request) {
    die("Request not found.");
}

$name = $request['requesterEmployeeID'];
$grade = $request['gradeLevel'];
$section = $request['section/s'];
$date = $request['inclusiveDate'];
$time = $request['inclusiveTime'];
$laboratoryName = $request['scilabName'];
$purpose = $request['subjectTopic'];
$teacherInCharge = $request['teacherInCharge'];
$status = $request['statusScilabPersonnel'] ?? 'pending';

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
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 20px;
}

.container {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
}

.glass-card {
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

.status-badge {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 20px;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-pending {
    background: rgba(245, 158, 11, 0.25);
    color: #fef3c7;
    box-shadow: 0 0 20px rgba(245, 158, 11, 0.3);
}

.status-approved {
    background: rgba(34, 197, 94, 0.25);
    color: #dcfce7;
    box-shadow: 0 0 20px rgba(34, 197, 94, 0.3);
}

.status-rejected {
    background: rgba(239, 68, 68, 0.25);
    color: #fee2e2;
    box-shadow: 0 0 20px rgba(239, 68, 68, 0.3);
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
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-radius: 24px;
    padding: 40px;
    border: 1px solid rgba(255, 255, 255, 0.3);
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
}

.btn-approve:hover:not(:disabled) {
    background: rgba(34, 197, 94, 0.4);
    box-shadow: 0 0 24px rgba(34, 197, 94, 0.4);
    transform: translateY(-2px);
}

.btn-reject {
    background: rgba(239, 68, 68, 0.3);
    color: #fee2e2;
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

@media (max-width: 768px) {
    .container {
        padding: 10px;
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
    <div class="glass-card">
        <div class="status-badge status-<?= htmlspecialchars($status) ?>" id="statusBadge">
            <?= htmlspecialchars(ucfirst($status)) ?>
        </div>
        
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
        
        <div class="modal-actions">
            <button class="btn-action btn-approve" id="btnApprove" onclick="handleAction('approve')">Approve</button>
            <button class="btn-action btn-reject" id="btnReject" onclick="handleAction('reject')">Reject</button>
            <button class="btn-action btn-close" onclick="closeModal()">Close</button>
        </div>
    </div>
</div>

<script>
const requestId = <?= json_encode($requestId) ?>;
const modalOverlay = document.getElementById('modalOverlay');
const statusBadge = document.getElementById('statusBadge');
const btnApprove = document.getElementById('btnApprove');
const btnReject = document.getElementById('btnReject');
const errorMessage = document.getElementById('errorMessage');

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

async function handleAction(action) {
    const button = action === 'approve' ? btnApprove : btnReject;
    
    button.classList.add('loading');
    btnApprove.disabled = true;
    btnReject.disabled = true;
    errorMessage.classList.remove('show');
    
    try {
        const response = await fetch('ajax/ajax_supervisor_action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                request_id: requestId,
                action: action
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            const newStatus = action === 'approve' ? 'approved' : 'rejected';
            statusBadge.className = 'status-badge status-' + newStatus;
            statusBadge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
            
            setTimeout(() => {
                closeModal();
            }, 800);
        } else {
            throw new Error(data.message || 'An error occurred');
        }
    } catch (error) {
        errorMessage.textContent = error.message;
        errorMessage.classList.add('show');
        button.classList.remove('loading');
        btnApprove.disabled = false;
        btnReject.disabled = false;
    }
}
</script>
<?php include('helperFiles/footer.php'); ?>