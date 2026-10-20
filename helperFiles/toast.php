<!-- Toast Container (shared by footer.php and pages without the footer, e.g. reset_password.php) -->
<div id="toast-container"></div>

<script>
    function showToast(message, type = 'info', duration = 3000) {
        const container = document.getElementById('toast-container');
        if (!container) return;
        const toast = document.createElement('div');
        toast.className = `toast-notification ${type}`;

        let icon = '';
        switch (type) {
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

        // Add to history (the notification center lives in footer.php)
        if (typeof addToNotificationHistory === 'function') {
            addToNotificationHistory(message, type);
        }
    }
</script>
