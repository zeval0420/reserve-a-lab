<?php
    include('../scilab/helperFiles/db_connection.php');
    include('helperFiles/session_handler.php');

    if (!isset($_SESSION['role'])) {
        header("Location: index.php");
        exit();
    }
    if ($_SESSION['role'] == 'admin') {
        header("Location: admin_home.php");
        exit();
    }

    $email = $_SESSION['email'] ?? null;
    $username = $_SESSION['username'] ?? null;

    /* Fetch all active labs. */
    $labsJson = [];
    $labs = $conn->query("SELECT * FROM scilab_availability WHERE status='active' ORDER BY scilabName ASC");
    if ($labs) {
        while ($lab = $labs->fetch_assoc()) {
            $id = $lab['scilabName'];
            $imgVersion = file_exists($lab['mainImagePath']) ? filemtime($lab['mainImagePath']) : time();
            $labsJson[] = [
                'id'               => $id,
                'laboratoryName'   => $id,
                'location'         => $lab['location'] ?? '',
                'image'            => htmlspecialchars($lab['mainImagePath']) . '?v=' . $imgVersion,
                'availability'     => $lab['availability'] ?? 'Not Available',
                'color'            => $lab['color'] ?? '#2B55C4',
                'pendingRequests'  => 0, // refreshed dynamically
            ];
        }
    }

    /* Pending Requests for teachers/supervisors/admins table */
    $pendingRequestsHtml = '';
    if ($_SESSION['role'] === 'teacher' || $_SESSION['role'] === 'admin') {
        if ($_SESSION['role'] === 'admin') {
            $sql = "SELECT * FROM scilab_form_requests WHERE statusScilabPersonnel = 'Pending'";
        } else {
            $teacherName = $_SESSION['username'];
            $sql = "SELECT * FROM scilab_form_requests WHERE teacherInCharge = '$teacherName' AND statusScilabPersonnel = 'Pending'";
        }
        $result = $conn->query($sql);
        $requests = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $requests[] = $row;
            }
        }

        if (count($requests) > 0) {
            $pendingRequestsHtml .= '<div class="main-wrapper" style="margin-top: 20px; background: rgba(255,255,255,0.6); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border-radius: var(--radius-card); border: 1px solid var(--color-border); box-shadow: var(--shadow-card); padding: 20px; margin-bottom: 20px;">';
            $pendingRequestsHtml .= '<h2 style="font-size: 20px; font-weight: 700; color: var(--color-primary-mid); margin-top: 0; margin-bottom: 15px;">Pending Requests for You</h2>';
            $pendingRequestsHtml .= '<div class="table-responsive">';
            $pendingRequestsHtml .= '<table class="table table-striped table-hover" style="margin-bottom: 0; background: transparent;">';
            $pendingRequestsHtml .= '<thead><tr><th>Requester</th><th>Lab Name</th><th>Date of Use</th><th>Time of Use</th><th>Action</th></tr></thead>';
            $pendingRequestsHtml .= '<tbody>';
            foreach ($requests as $request) {
                $pendingRequestsHtml .= '<tr>';
                $pendingRequestsHtml .= '<td>' . htmlspecialchars($request['requesterEmployeeID']) . '</td>';
                $pendingRequestsHtml .= '<td>' . htmlspecialchars($request['scilabName']) . '</td>';
                $pendingRequestsHtml .= '<td>' . htmlspecialchars($request['inclusiveDate']) . '</td>';
                $pendingRequestsHtml .= '<td>' . htmlspecialchars($request['inclusiveTime']) . '</td>';
                if ($_SESSION['role'] === 'teacher') {
                    $pendingRequestsHtml .= '<td><a href="supervisor_approve.php?id=' . $request['id'] . '" class="btn-liquid">View</a></td>';
                } else {
                    $pendingRequestsHtml .= '<td><a href="admin_approve.php?status=Pending&search='. $request['id'] .'" class="btn-liquid">View</a></td>';
                }
                $pendingRequestsHtml .= '</tr>';
            }
            $pendingRequestsHtml .= '</tbody>';
            $pendingRequestsHtml .= '</table>';
            $pendingRequestsHtml .= '</div>';
            $pendingRequestsHtml .= '</div>';
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Home</title>
        <?php include('helperFiles/headData.php'); ?>
        <link rel="stylesheet" href="css/laboratory-card.css">
        <link rel="stylesheet" href="css/home-heading.css">
        <style>
            .main-wrapper {
                width: 80%;
                margin: 0 auto;
            }
            @media (max-width: 768px) {
                .main-wrapper { width: 95%; }
            }

            /* ─── HEADING ROW ───────────────────────────────────── */
            .heading {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 14px 20px;
                background: rgba(255,255,255,0.6);
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
                border-radius: var(--radius-card);
                border: 1px solid var(--color-border);
                box-shadow: var(--shadow-card);
                margin-bottom: 20px;
                flex-wrap: wrap;
                gap: 15px;
            }
            .heading__welcome {
                font-size: 15px;
                font-weight: 600;
                color: var(--color-text-primary);
                margin: 0;
                animation: welcome-in 0.4s ease both;
            }
            .heading__welcome span { color: var(--color-primary-mid); }

            /* ─── VIEW SELECTOR ─────────────────────────────────── */
            .view-selector {
                display: flex;
                align-items: center;
                gap: 4px;
                background: rgba(43,85,196,0.06);
                border: 1px solid rgba(43,85,196,0.15);
                border-radius: 30px;
                padding: 4px;
                animation: selector-in 0.4s ease 0.1s both;
            }
            .view-selector__btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 6px;
                padding: 6px 14px;
                border-radius: 24px;
                border: none;
                background: transparent;
                color: var(--color-text-secondary);
                font-family: var(--font-family);
                font-size: 12px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
                white-space: nowrap;
                position: relative;
                overflow: hidden;
            }
            .view-selector__btn::after {
                content: '';
                position: absolute;
                inset: 0;
                background: rgba(43,85,196,0.08);
                border-radius: inherit;
                opacity: 0;
                transform: scale(0.6);
                transition: opacity 0.2s ease, transform 0.2s ease;
            }
            .view-selector__btn:active::after { opacity: 1; transform: scale(1); }
            .view-selector__btn i { font-size: 15px; transition: transform 0.3s cubic-bezier(0.4,0,0.2,1); }
            .view-selector__btn:hover { color: var(--color-primary-mid); }
            .view-selector__btn:hover i { transform: scale(1.2); }
            .view-selector__btn.active {
                background: linear-gradient(135deg, var(--color-primary-mid), var(--color-accent));
                color: #fff;
                box-shadow: 0 4px 12px rgba(43,85,196,0.3);
                animation: btn-pop 0.25s cubic-bezier(0.34,1.56,0.64,1);
            }
            .view-selector__btn.active i { transform: scale(1.1); }

            /* ─── SCHEDULE BLOCKS ───────────────────────────────── */
            .schedule-block {
                width: 100%;
                text-align: left;
                border-top: 1px solid var(--color-border);
                padding-top: 12px;
                margin-top: 12px;
                margin-bottom: 10px;
            }
            .schedule-date-label {
                font-weight: 600;
                color: var(--color-primary);
                font-size: 13px;
                margin-bottom: 8px;
                display: block;
            }
            .slot-pill-list {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
            }
            .slot-pill {
                border-radius: 20px;
                padding: 4px 12px;
                font-size: 11px;
                font-weight: 600;
                display: inline-flex;
                flex-direction: column;
                gap: 1px;
                border: 1px solid transparent;
                min-width: 110px;
            }
            .slot-pill-label {
                text-transform: uppercase;
                font-size: 9px;
                letter-spacing: 0.3px;
                opacity: 0.82;
            }
            .slot-pill-time {
                font-size: 11px;
                font-weight: 700;
            }
            .slot-pill.approved {
                background: rgba(40, 199, 111, 0.1);
                color: #1a8a4a;
                border-color: rgba(40, 199, 111, 0.2);
            }
            .slot-pill.requested {
                background: rgba(255, 193, 7, 0.1);
                color: #856404;
                border-color: rgba(255, 193, 7, 0.2);
            }
            .empty-slot-hint {
                margin: 8px 0 0;
                font-size: 12px;
                color: var(--color-text-secondary);
            }

            /* ─── GALLERY OVERLAY ───────────────────────────────── */
            #galleryOverlay {
                position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                background: rgba(15,15,15,.65); z-index: 9999;
                display: flex; align-items: center; justify-content: center;
                flex-direction: column;
            }
            .overlay-backdrop { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; }
            .gallery-content  { position: relative; width: 80%; max-width: 900px; text-align: center; color: white; }
            .gallery-close    { position: absolute; top: -20px; right: -20px; font-size: 40px; background: transparent; border: none; color: white; cursor: pointer; }
            .gallery-title    { margin-bottom: 20px; font-size: 28px; font-weight: bold; }
            .carousel-wrapper { display: flex; align-items: center; justify-content: space-between; }
            .carousel-images  { flex: 1; text-align: center; }
            .carousel-images img { max-height: 500px; max-width: 100%; border-radius: 10px; }
            .gallery-nav      { font-size: 40px; background: transparent; border: none; color: white; cursor: pointer; width: 60px; user-select: none; }

            /* ─── ANIMATIONS ────────────────────────────────────── */
            @keyframes btn-pop    { 0%{transform:scale(.88)} 60%{transform:scale(1.06)} 100%{transform:scale(1)} }
            @keyframes welcome-in { from{opacity:0;transform:translateX(-10px)} to{opacity:1;transform:translateX(0)} }
            @keyframes selector-in{ from{opacity:0;transform:translateX(10px)} to{opacity:1;transform:translateX(0)} }

            @media (max-width: 480px) {
                .view-selector__btn span { display: none; }
                .view-selector__btn { padding: 7px 10px; }
            }
        </style>
    </head>

    <body>
        <?php include('helperFiles/header.php'); ?>

        <!-- Pending request list at top for teachers -->
        <?= $pendingRequestsHtml ?>

        <div class="main-wrapper">
            <!-- Header actions (Date selector + View Selector) -->
            <div class="heading">
                <div class="d-inline-flex align-items-center gap-2 flex-wrap">
                    <label for="datepicker" class="form-label fw-bold mb-0" style="color: var(--color-primary); font-size: 14px; font-weight: 700;">Select Date of Use:</label>
                    <input type="date" id="datepicker" class="form-control liquid-input" style="width:220px; display:inline-block; font-size:14px; padding: 6px 14px; margin-left: 8px;">
                </div>

                <div class="view-selector" role="group" aria-label="Card view selector">
                    <button class="view-selector__btn active" data-view="complete" onclick="switchView(this,'complete')" title="Complete view">
                        <i class="bi bi-grid-3x3-gap-fill"></i><span>Complete</span>
                    </button>
                    <button class="view-selector__btn" data-view="compact" onclick="switchView(this,'compact')" title="Compact view">
                        <i class="bi bi-grid-fill"></i><span>Compact</span>
                    </button>
                    <button class="view-selector__btn" data-view="list" onclick="switchView(this,'list')" title="List view">
                        <i class="bi bi-list-ul"></i><span>List</span>
                    </button>
                </div>
            </div>

            <!-- Card gallery — populated by JS -->
            <div id="lab-gallery" class="lab-gallery--complete"></div>
        </div>

        <!-- Gallery Overlay (full-screen carousel) -->
        <div id="galleryOverlay" style="display:none;">
            <div class="overlay-backdrop"></div>
            <div class="gallery-content">
                <button class="gallery-close">&times;</button>
                <h4 id="galleryTitle" class="gallery-title"></h4>
                <div class="carousel-wrapper">
                    <button class="gallery-nav left">&#10094;</button>
                    <div id="galleryCarousel" class="carousel-images"></div>
                    <button class="gallery-nav right">&#10095;</button>
                </div>
            </div>
        </div>

        <?php include('helperFiles/footer.php'); ?>
    </body>

    <!-- Laboratory card component -->
    <script src="helperFiles/laboratory-card.js?v=<?= filemtime('helperFiles/laboratory-card.js') ?>"></script>

    <!-- Pass PHP data to JS -->
    <script>
        const labsData = <?= json_encode($labsJson, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>

    <script>
        /* ══════════════════════════════════════════════════════
           GALLERY RENDERER + VIEW SWITCHER
           ═══════════════════════════════════════════════════════ */

        let currentVariant = 'complete';

        function renderGallery(variant) {
            const container = document.getElementById('lab-gallery');
            if (!container) return;

            container.className = `lab-gallery--${variant}`;

            // Render lab cards via the JS component
            container.innerHTML = labsData.map(lab => createLabCard(lab, variant)).join('');

            // Inject the schedule block inside each card body
            labsData.forEach(lab => {
                const card = document.getElementById(`lab-card-${variant}-${lab.id}`);
                if (!card) return;

                const body = card.querySelector('.lab-card__body');
                if (body) {
                    const sched = document.createElement('div');
                    sched.className = 'schedule-block mt-2';
                    sched.innerHTML = `
                        <span class="schedule-date-label">Schedule for <span class="selected-date-label" data-selected-date-label>--</span></span>
                        <div class="slot-pill-list" data-lab-schedule></div>
                        <p class="empty-slot-hint mb-0">No requests for this date yet.</p>
                    `;
                    body.appendChild(sched);
                }
            });

            // Set the date and load the snapshot
            const datepicker = document.getElementById('datepicker');
            if (datepicker && datepicker.value) {
                updateCardsForDate(datepicker.value);
            }

            if (typeof AOS !== 'undefined') AOS.refresh();
        }

        function switchView(btn, variant) {
            document.querySelectorAll('.view-selector__btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentVariant = variant;
            renderGallery(variant);
        }

        /* ══════════════════════════════════════════════════════
           REQUEST DELEGATION OVERRIDE
           ═══════════════════════════════════════════════════════ */

        function requestLaboratory(labId) {
            window.location.href = `forms.php?scilabname=${encodeURIComponent(labId)}`;
        }

        /* ══════════════════════════════════════════════════════
           DATE / SNAPSHOT LOADER
           ═══════════════════════════════════════════════════════ */

        function escapeHtml(value) {
            return $('<div>').text(value ?? '').html();
        }

        function formatDateLabel(dateStr) {
            if(!dateStr) return '--';
            const parsed = new Date(dateStr);
            if(isNaN(parsed)) return '--';
            return parsed.toLocaleDateString('en-US', { weekday:'short', month:'short', day:'numeric' });
        }

        function updateSelectedDateLabels(dateStr) {
            const label = formatDateLabel(dateStr);
            $('[data-selected-date-label]').text(label);
        }

        function renderSlotList(card, slots) {
            const list = card.find('[data-lab-schedule]');
            const emptyState = card.find('.empty-slot-hint');
            list.empty();

            const relevant = slots.filter(slot => {
                const status = (slot.status || '').toLowerCase();
                return status === 'approved' || status === 'pending';
            });

            if(!relevant.length) {
                emptyState.show();
                return;
            }

            emptyState.hide();

            relevant.forEach(slot => {
                const normalized = (slot.status || '').toLowerCase();
                const statusClass = normalized === 'approved' ? 'approved' : 'requested';
                const statusLabel = normalized === 'approved' ? 'Approved' : 'Requested';
                const safeTime = escapeHtml(slot.label);
                list.append(`
                    <span class="slot-pill ${statusClass}">
                        <span class="slot-pill-label">${statusLabel}</span>
                        <span class="slot-pill-time">${safeTime}</span>
                    </span>
                `);
            });
        }

        function updateCardsForDate(selectedDate) {
            updateSelectedDateLabels(selectedDate);
            $.post('ajax/store_date.php', { date: selectedDate });
            $.ajax({
                url: 'ajax/ajax_requester.php',
                type: 'POST',
                data: { action: 'get_day_snapshot', date: selectedDate },
                dataType: 'json',
                success: function(payload) {
                    const pendingCounts = payload.pendingCounts || {};
                    const schedules = payload.schedules || {};
                    $('.lab-card').each(function() {
                        const card = $(this);
                        const labName = card.data('lab-id');
                        renderSlotList(card, schedules[labName] || []);
                        const pending = pendingCounts[labName] || 0;
                        const pendingBadge = card.find('.lab-badge-pending');
                        if (pendingBadge.length) {
                            pendingBadge.html(`<i class="bi bi-clock"></i> ${pending} pending`);
                            pendingBadge.toggleClass('has-pending', pending > 0);
                        }
                    });
                },
                error: function() {
                    showToast('Unable to refresh laboratory schedules. Please try again.', 'error');
                }
            });
        }

        /* ══════════════════════════════════════════════════════
           IMAGE GALLERY CAROUSEL
           ═══════════════════════════════════════════════════════ */

        let galleryImages = [], currentIndex = 0;

        function showGallery(images, title) {
            galleryImages = images;
            currentIndex = 0;
            updateGalleryImage();
            $('#galleryTitle').text(title + ' — Gallery');
            $('#galleryOverlay').fadeIn();
        }

        function updateGalleryImage() {
            if (!galleryImages.length) return;
            $('#galleryCarousel').html(`<img src="${galleryImages[currentIndex]}" alt="Lab Image">`);
        }

        function nextImage() { if (galleryImages.length) { currentIndex = (currentIndex + 1) % galleryImages.length; updateGalleryImage(); } }
        function prevImage() { if (galleryImages.length) { currentIndex = (currentIndex - 1 + galleryImages.length) % galleryImages.length; updateGalleryImage(); } }

        $(document).ready(function() {
            // Event delegation for opening the gallery
            $(document).on('click', '.lab-card__image, .lab-card__image-placeholder', function() {
                const card = $(this).closest('[data-lab-id]');
                const lab = card.data('lab-id');
                $.ajax({
                    url: 'ajax/ajax_requester.php',
                    method: 'POST',
                    data: { action: 'get_lab_images', lab: lab },
                    dataType: 'json',
                    success: function(images) {
                        const main = `img/labimages/${lab}.jpg`;
                        images.length ? showGallery(images, lab) : showGallery([main], lab);
                    },
                    error: function() {
                        showToast('Failed to load gallery.', 'error');
                    }
                });
            });

            $('.gallery-close').click(() => $('#galleryOverlay').fadeOut());
            $('.gallery-nav.left').click(prevImage);
            $('.gallery-nav.right').click(nextImage);
            $(document).on('keydown', e => { if (e.key === 'Escape') $('#galleryOverlay').fadeOut(); });

            // Initialize Date Picker input defaults
            const today = new Date().toISOString().split('T')[0];
            const datepicker = document.getElementById('datepicker');
            datepicker.value = today;
            datepicker.setAttribute('min', today);

            $('#datepicker').on('change', function() {
                updateCardsForDate($(this).val());
            });

            // Initial render
            renderGallery('complete');

            // Force reload if navigated via history back/forward to sync state
            window.addEventListener("pageshow", e => {
                if (e.persisted || (window.performance && window.performance.navigation.type === 2)) {
                    window.location.reload();
                }
            });
        });
    </script>
</html>