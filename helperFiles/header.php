<header class="header background-gradient">
    <div id="banner" style="color: white; overflow: hidden;">
        <img src="img/logo.png" alt="PSHS Logo" class="logo"style="float: left; margin-right: 20px;">
        <div style="line-height: 1.2; overflow: hidden;">
            <div style="font-size: 16px;">Department of Science and Technology</div>
            <div style="font-size: 16px; font-weight: bold;">PHILIPPINE SCIENCE HIGH SCHOOL</div>
            <div style="font-size: 16px; font-weight: bold;">ILOCOS REGION CAMPUS</div>
        </div>
    </div>
    <div class="system-name d-none d-md-block">
        <span style="display: block; color:white; font-size: 2.5rem; font-weight: bold; margin-top: 0px;">RESERVE-A-LAB</span>
    </div>    
    <div class="nav-icons">
        <button type="button" 
                class="btn-liquid-white" 
                id="notif-btn"
                onclick="toggleNotificationSidebar()">
                <i class="bi bi-bell-fill"></i>
                <span class="notification-badge" id="notif-badge">0</span>
        </button>
        <button type="button" class="btn-liquid-white" data-toggle="popover" title="<?php echo $_SESSION['username']; ?>" data-html="true" data-content='<a href="helperFiles/logout.php">Logout</a>'>
            <i class="bi bi-person-fill"></i>
        </button>
    </div>
</header>

<!-- Notification Sidebar -->
<div class="ns-overlay" id="ns-overlay" onclick="toggleNotificationSidebar()"></div>
<div class="notification-sidebar" id="notification-sidebar">
    <div class="ns-header">
        <h3 class="ns-title">Notifications</h3>
        <button class="ns-close" onclick="toggleNotificationSidebar()">&times;</button>
        <div style="width: 100%; margin-top: 10px; text-align: right;"><span class="ns-mark-read-btn" onclick="markNotificationsAsRead()">Mark as Read</span></div>
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

<nav class="main-nav">
    <div class="nav-left">
        <button type="button" class="btn-liquid" id="openCalendarBtn">
            View Calendar
        </button>
    </div>
    <!-- CALENDAR -->
    <?php include('calendar.php'); ?>
    
    <ul class="nav-list">
        <?php
            $currentPage = basename($_SERVER['PHP_SELF']); // Get the current page name
            $role = $_SESSION['role'] ?? ''; // Safely get the role
        ?>

        <li>
            <a href="index.php" class="nav-link <?php echo ($currentPage === 'admin_home.php' || $currentPage === 'requester_home.php') ? 'active' : ''; ?>">
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