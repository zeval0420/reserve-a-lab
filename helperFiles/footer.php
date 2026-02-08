<footer class="site-footer sticky background-gradient text-white">
    <div class="container-fluid px-4">
        <div class="row text-center text-md-left align-items-center">
            <!-- Column 1: School Name and Campus -->
            <div class="col-12 col-md-5 mb-5 mb-md-0 text-left" style="padding-left: 50px;">
                <div class="col-12 col-md-8">
                    <br/>
                    <h5 class="mb-1" style="font-weight: bold; font-size: 20px;">PHILIPPINE SCIENCE HIGH SCHOOL</h5>
                    <h5 class="mb-2" style="font-weight: bold; font-size: 20px;">ILOCOS REGION CAMPUS</h5>
                    <p class="mb-0" style="margin-top: 5px;"><i class="bi bi-geo-alt-fill"></i>Poblacion East, San Ildefonso 2728, Ilocos Sur</p>
                </div>
                
                <div class="mb-2 col-12 col-md-4 text-center">
                    <br/>
                    <br/>
                    <p class="mt-2 mb-1 font-weight-bold">About the</p>
                    <p class="mt-2 mb-1 font-weight-bold" style="font-size: 16px; font-weight: bold;"><a href="about.php">DEVELOPERS</a></p>
                    
                </div>
            </div>

            <!-- Column 3: Center logo -->
            <div class="col-12 col-md-2 mb-2 mb-md-0 d-flex justify-content-center" style="padding-top: 15px;">
                <img src="img/logo.png" alt="PSHS Logo" class="footer-logo">
            </div>

            <!-- Column 4: Contact and About -->
            <div class="col-12 col-md-5 text-md-right text-center">
                <div class="mb-2 col-12 col-md-6">
                    <p class="mb-1 font-weight-bold">Developed by:</p>
                    <br/>

                    <p class="mt-2 mb-1 font-weight-bold">Gabriel James Valdez</p>
                    <p class="mt-2 mb-1 font-weight-bold">Zyx Leiabe A. Barangan</p>
                    <p class="mt-2 mb-1 font-weight-bold">Rojan Joefel C. Dumlao</p>

                </div>
                <div class="mb-2 col-12 col-md-6 ">
                    <br/>
                    <p class="mt-2 mb-1 font-weight-bold" style="font-size: 16px; font-weight: bold;">RESEARCH PROJECT</p>
                    <p class="mt-2 mb-1 font-weight-bold">Grade 11</p>
                    <br/>
                    <p class="mt-2 mb-1 font-weight-bold" style="font-size: 16px; font-weight: bold;">Adviser: June Leonel Ngayaan</p>
                    <p class="mt-2 mb-1 font-weight-bold">School Year: 2025-2026</p>
                </div>
            </div>
        </div>
        <div class="text-center mt-3" style="margin-top: 0">
            <small>&copy; 2025 PSHS IRC. All rights reserved.</small>
        </div>
    </div>
</footer>

<!-- Toast Container -->
<div id="toast-container"></div>

<script>
    $(function () {
        $('[data-toggle="popover"]').popover({ trigger: 'click', placement: 'bottom' });

        $(document).on('click', function (e) {
            $('[data-toggle="popover"]').each(function () {
                if (!$(this).is(e.target) && $(this).has(e.target).length === 0 && $('.popover').has(e.target).length === 0) {
                    $(this).popover('hide');
                }
            });
        });
    });

    function showToast(message, type = 'info', duration = 3000) {
        const container = document.getElementById('toast-container');
        if (!container) return;
        const toast = document.createElement('div');
        toast.className = `toast-notification ${type}`;
        
        let icon = '';
        switch(type) {
            case 'success': icon = '<i class="bi bi-check-circle-fill" style="color:#28a745; margin-right:10px;"></i>'; break;
            case 'error': icon = '<i class="bi bi-exclamation-circle-fill" style="color:#dc3545; margin-right:10px;"></i>'; break;
            case 'warning': icon = '<i class="bi bi-exclamation-triangle-fill" style="color:#ffc107; margin-right:10px;"></i>'; break;
            default: icon = '<i class="bi bi-info-circle-fill" style="color:#17a2b8; margin-right:10px;"></i>';
        }

        toast.innerHTML = `
            <div style="display:flex; align-items:center;">
                ${icon}
                <span>${message}</span>
            </div>
            <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
        `;

        container.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('hide');
            toast.addEventListener('animationend', () => toast.remove());
        }, duration);

        // Add to history
        addToNotificationHistory(message, type);
    }

    // Notification Center Logic
    let currentFilter = 'all';

    function filterNotifications(type) {
        currentFilter = type;
        document.querySelectorAll('.ns-filter').forEach(el => {
            if (el.textContent.trim().toLowerCase() === type || (type === 'all' && el.textContent.trim().toLowerCase() === 'all')) {
                el.classList.add('active');
            } else {
                el.classList.remove('active');
            }
        });
        renderNotificationHistory();
    }

    function toggleNotificationSidebar() {
        const sidebar = document.getElementById('notification-sidebar');
        const overlay = document.getElementById('ns-overlay');
        if (sidebar && overlay) {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('show');
            if (sidebar.classList.contains('open')) {
                renderNotificationHistory();
            }
        }
    }

    function addToNotificationHistory(message, type) {
        const history = JSON.parse(localStorage.getItem('notificationHistory') || '[]');
        const timestamp = new Date().toLocaleString('en-US', { hour: 'numeric', minute: 'numeric', hour12: true, month: 'short', day: 'numeric' });
        
        history.unshift({ message, type, timestamp });
        
        // Keep last 50
        if (history.length > 50) history.pop();
        
        localStorage.setItem('notificationHistory', JSON.stringify(history));
        
        // If sidebar is open, re-render
        const sidebar = document.getElementById('notification-sidebar');
        if (sidebar && sidebar.classList.contains('open')) {
            renderNotificationHistory();
        }
    }

    async function renderNotificationHistory() {
        const container = document.getElementById('ns-content');
        if (!container) return;
        
        const history = JSON.parse(localStorage.getItem('notificationHistory') || '[]');
        let displayItems = history.map(item => ({...item, source: 'local'}));

        // If admin, fetch pending requests from server
        if (typeof userRole !== 'undefined' && userRole === 'admin') {
            try {
                const response = await $.ajax({
                    url: 'ajax/ajax_admin.php',
                    data: { action: 'notification_feed' },
                    dataType: 'json'
                });
                
                if (response && response.items) {
                    const serverItems = response.items.map(item => ({
                        type: 'warning',
                        timestamp: item.submitted,
                        message: `<strong>${item.requester}</strong> requested <strong>${item.lab}</strong>.<br><small>For ${item.date} ${item.time}</small>`,
                        source: 'server',
                        link: `admin_approve.php?status=Pending&search=${item.id}`
                    }));
                    displayItems = [...serverItems, ...displayItems];
                }
            } catch (e) {
                console.error("Failed to load pending requests", e);
            }
        }
        
        const filtered = currentFilter === 'all' ? displayItems : displayItems.filter(item => item.type === currentFilter);

        if (filtered.length === 0) {
            container.innerHTML = '<div style="text-align:center; padding: 40px 20px; color: #999; font-style: italic;">No notifications found</div>';
            return;
        }
        
        container.innerHTML = filtered.map(item => {
            const viewLink = item.link ? `<div style="margin-top:8px; text-align:right;"><a href="${item.link}" style="font-weight:600; text-decoration:none; color:inherit; font-size:12px; border-bottom:1px dotted currentColor;">Review Request &rarr;</a></div>` : '';
            return `
            <div class="ns-item ${item.type}">
                <div class="ns-item-header">
                    <span>${item.type}</span>
                    <span>${item.timestamp}</span>
                </div>
                <div class="ns-item-body">${item.message}${viewLink}</div>
            </div>
        `}).join('');
    }

    function clearNotifications() {
        if(confirm('Clear all notification history?')) {
            localStorage.removeItem('notificationHistory');
            renderNotificationHistory();
        }
    }

    // Notification Badge Logic (Pending Requests)
    const userRole = "<?php echo $_SESSION['role'] ?? ''; ?>";
    
    function updateNotificationBadge() {
        if (userRole !== 'admin') return;

        $.get('ajax/ajax_admin.php?action=get_pending_count', function(data) {
            const serverCount = parseInt(data) || 0;
            const lastReadCount = parseInt(localStorage.getItem('notif_last_read_count') || '0');
            
            // If server count dropped (e.g. items approved), reset read count to avoid negative badge
            let effectiveReadCount = lastReadCount;
            if (serverCount < lastReadCount) {
                effectiveReadCount = serverCount;
                localStorage.setItem('notif_last_read_count', serverCount);
            }

            const unreadCount = serverCount - effectiveReadCount;
            const badge = document.getElementById('notif-badge');
            
            if (badge) {
                if (unreadCount > 0) {
                    badge.innerText = unreadCount > 99 ? '99+' : unreadCount;
                    badge.style.display = 'block';
                } else {
                    badge.style.display = 'none';
                }
            }
        });
    }

    function markNotificationsAsRead() {
        if (userRole !== 'admin') return;
        // Fetch current count to set as read
        $.get('ajax/ajax_admin.php?action=get_pending_count', function(data) {
            localStorage.setItem('notif_last_read_count', parseInt(data) || 0);
            updateNotificationBadge();
        });
    }

    // Check for notifications on load and every 30 seconds
    $(document).ready(function() {
        updateNotificationBadge();
        setInterval(updateNotificationBadge, 30000);
    });
</script>