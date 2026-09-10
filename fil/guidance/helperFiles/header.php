<header class="header background-gradient">
    <!-- UNCHANGED: #banner ID, logo class, all inline styles preserved -->
    <div id="banner" style="color: white; overflow: hidden;">
        <img src="img/logo.png" alt="PSHS Logo" class="logo" style="float: left; margin-right: 14px;">
        <div style="line-height: 1.2; overflow: hidden;">
            <div style="font-size: 14px; opacity: 0.9;">Department of Science and Technology</div>
            <div style="font-size: 15px; font-weight: bold;">PHILIPPINE SCIENCE HIGH SCHOOL</div>
            <div style="font-size: 15px; font-weight: bold;">ILOCOS REGION CAMPUS</div>
        </div>
    </div>

    <!-- UNCHANGED: .system-name + d-none d-md-block classes intact -->
    <div class="system-name d-none d-md-block">
        <span>RESERVE-A-LAB</span>
    </div>

    <!-- UNCHANGED: #notif-btn, notif-badge, data-toggle="popover", all event bindings kept -->
    <div class="nav-icons">
        <button type="button" class="btn-liquid-white" id="notif-btn" onclick="toggleNotificationSidebar()">
            <i class="bi bi-bell-fill"></i>
            <span class="notification-badge" id="notif-badge">0</span>
        </button>
        <?php
        $popoverContent = '<div style="display:flex; flex-direction:column; gap:5px;">';

        if (isset($_SESSION['email']) && $_SESSION['email'] === 'admin.controller@local') {
            $popoverContent .= '<a href="controller_dashboard.php" class="popover-link"><i class="bi bi-speedometer2"></i> Dashboard</a>';
        }

        $popoverContent .= '<a href="#" class="popover-link" onclick="openChangePasswordModal(); return false;"><i class="bi bi-key-fill"></i> Change Password</a>';
        $popoverContent .= '<a href="helperFiles/logout.php" class="popover-link text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>';
        $popoverContent .= '</div>';
        ?>
        <button type="button" class="btn-liquid-white" data-toggle="popover"
            title="<?php echo $_SESSION['username']; ?>" data-html="true" data-content='<?php echo $popoverContent; ?>'>
            <i class="bi bi-person-fill"></i>
        </button>
    </div>
</header>

<!-- UNCHANGED: notification sidebar — all IDs and onclick handlers intact -->
<div class="ns-overlay" id="ns-overlay" onclick="toggleNotificationSidebar()"></div>
<div class="notification-sidebar" id="notification-sidebar">
    <div class="ns-header">
        <h3 class="ns-title">Notifications</h3>
        <button class="ns-close" onclick="toggleNotificationSidebar()">&times;</button>
        <div style="width: 100%; margin-top: 10px; text-align: right;">
            <span class="ns-mark-read-btn" onclick="markNotificationsAsRead()">Mark as Read</span>
        </div>
    </div>
    <div class="ns-filters">
        <span class="ns-filter active" onclick="filterNotifications('all')">All</span>
        <span class="ns-filter" onclick="filterNotifications('success')">Success</span>
        <span class="ns-filter" onclick="filterNotifications('error')">Error</span>
        <span class="ns-filter" onclick="filterNotifications('warning')">Warning</span>
        <span class="ns-filter" onclick="filterNotifications('info')">Info</span>
    </div>
    <div class="ns-content" id="ns-content"></div>
    <div class="ns-clear-btn" onclick="clearNotifications()">Clear History</div>
</div>

<!-- UNCHANGED: nav — all PHP role logic, IDs, href targets, active class logic untouched -->
<nav class="main-nav">
    <div class="nav-left">
        <button type="button" class="btn-liquid" id="openCalendarBtn">
            View Calendar
        </button>
    </div>

    <?php include('calendar.php'); ?>

    <ul class="nav-list">
        <?php
        $currentPage = basename($_SERVER['PHP_SELF']);
        $role = $_SESSION['role'] ?? '';
        ?>
        <li>
            <a href="index.php"
                class="nav-link <?php echo ($currentPage === 'admin_home.php' || $currentPage === 'requester_home.php') ? 'active' : ''; ?>">
                Home
            </a>
        </li>
        <li>
            <a href="<?php echo ($role === 'admin') ? 'admin_approve.php' : 'requests.php'; ?>"
                class="nav-link <?php echo ($currentPage === 'admin_approve.php' || $currentPage === 'requests.php') ? 'active' : ''; ?>">
                Requests
            </a>
        </li>
        <?php if ($role === 'admin'): ?>
            <li>
                <a href="inventory.php" class="nav-link <?php echo ($currentPage === 'inventory.php') ? 'active' : ''; ?>">
                    Inventory
                </a>
            </li>
        <?php endif; ?>
        <?php if ($role === 'requestor' || $role === 'student'): ?>
            <li>
                <a href="forms.php" class="nav-link <?php echo ($currentPage === 'forms.php') ? 'active' : ''; ?>">
                    Forms
                </a>
            </li>
        <?php endif; ?>
    </ul>
</nav>

<!-- UNCHANGED: modal — #changePasswordModal, form ID, name attrs, data-dismiss all intact -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Password</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <form id="changePasswordForm">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" class="form-control liquid-input" required>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" class="form-control liquid-input" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control liquid-input" required>
                    </div>
                    <div class="form-group">
                        <label style="font-weight: normal; cursor: pointer; font-size: 13px;">
                            <input type="checkbox" id="showPassToggle"
                                style="vertical-align: middle; margin-top: -2px;"> Show Password
                        </label>
                    </div>
                    <div class="text-right">
                        <button type="button" class="btn-liquid-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-liquid-success">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- UNCHANGED: all JS — openChangePasswordModal, showPassToggle, form submit handler -->
<script>
    function openChangePasswordModal() {
        $('#changePasswordModal').modal('show');
        $('[data-toggle="popover"]').popover('hide');
    }

    $(document).ready(function () {
        $('#showPassToggle').change(function () {
            const type = $(this).is(':checked') ? 'text' : 'password';
            $('#changePasswordForm input[name*="password"]').attr('type', type);
        });

        $('#changePasswordForm').submit(function (e) {
            e.preventDefault();
            const newPass = $('input[name="new_password"]').val();
            const confirmPass = $('input[name="confirm_password"]').val();

            if (newPass !== confirmPass) {
                showToast("New passwords do not match.", "warning");
                return;
            }

            $.post('ajax/ajax_account.php', $(this).serialize() + '&action=change_password', function (res) {
                if (res.trim() === 'success') {
                    showToast("Password updated successfully.", "success");
                    $('#changePasswordModal').modal('hide');
                    $('#changePasswordForm')[0].reset();
                } else {
                    showToast(res, "error");
                }
            }).fail(function () {
                showToast("Server error.", "error");
            });
        });
    });
</script>