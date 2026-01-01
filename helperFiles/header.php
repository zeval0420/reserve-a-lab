<link rel="stylesheet" href="helperFiles/HeadFoot.css">
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
                class="btn btn-outline-primary btn-sm" 
                id="notif-btn"
                data-toggle="popover" 
                data-placement="bottom"
                title="Notifications" 
                data-html="true" 
                data-content="Loading...">
                <i class="bi bi-bell-fill"></i>
        </button>
        <button type="button" class="btn btn-outline-success btn-sm" data-toggle="popover" title="<?php echo $_SESSION['username']; ?>" data-html="true" data-content='<a href="helperFiles/logout.php">Logout</a>'>
            <i class="bi bi-person-fill"></i>
        </button>
    </div>
</header>
<nav class="main-nav">
    <div class="nav-left">
        <button type="button" class="btn btn-link nav-link" id="openCalendarBtn">
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

<?php if ($role === 'admin'): ?>
    <script>
        $(function () {
            $('[data-toggle="popover"]').popover({ trigger: 'click', placement: 'bottom' });
            $(document).click(e => {
                $('[data-toggle="popover"]').each(function () {
                    if (!$(this).is(e.target) && !$(this).has(e.target).length && !$('.popover').has(e.target).length)
                        $(this).popover('hide');
                });
            });
            $('#notif-btn').on('shown.bs.popover', function () {
                const btn = $(this);
                fetch('ajax/ajax_admin.php?action=get_pending_count')
                    .then(res => res.text())
                    .then(data => {
                        const content = `
                            <div>You have <span class="badge bg-danger">${data}</span> new requests.<br>
                            <a href="admin_approve.php">Go to requests page</a></div>`;
                        $('#' + btn.attr('aria-describedby')).find('.popover-content').html(content);
                    })
                    .catch(err => console.error('Error fetching pending count:', err));
            });
        });  
    </script>
<?php endif; ?>