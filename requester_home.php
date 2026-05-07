<?php
    include('../scilab/helperFiles/db_connection.php');
    include('helperFiles/session_handler.php');

    $email = $_SESSION['email'] ?? null;
    $username = $_SESSION['username'] ?? null;

    if(!isset($_SESSION['role'])) {
        header("Location: index.php");
        exit();
    } 


    function getLabImages($labName) {
        $folder = 'img/labimages/' . $labName;
        $images = [];
        if(is_dir($folder)) {
            foreach(scandir($folder) as $file) {
                if(in_array(pathinfo($file, PATHINFO_EXTENSION), ['jpg','jpeg','png','gif'])) {
                    $images[] = $folder . '/' . $file;
                }
            }
        }
        return $images;
    }

    if ($_SESSION['role'] === 'teacher' || $_SESSION['role'] === 'admin') {
        if ($_SESSION['role'] === 'admin'){
            $sql = "SELECT * FROM scilab_form_requests WHERE statusScilabPersonnel = 'Pending'";
        } else {
            $teacherName = $_SESSION['username'];
            $sql = "SELECT * FROM scilab_form_requests WHERE teacherInCharge = '$teacherName' AND statusScilabPersonnel = 'Pending'";
        }
        $result = $conn->query($sql);
        $requests = [];
        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }

        if (count($requests) > 0) {
            echo '<div class="main-wrapper" style="margin-top: 20px;">';
            echo '<h2>Pending Requests for You</h2>';
            echo '<table class="table table-striped table-hover">';
            echo '<thead><tr><th>Requester</th><th>Lab Name</th><th>Date of Use</th><th>Time of Use</th><th>Action</th></tr></thead>';
            echo '<tbody>';
            foreach ($requests as $request) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($request['requesterEmployeeID']) . '</td>';
                echo '<td>' . htmlspecialchars($request['scilabName']) . '</td>';
                echo '<td>' . htmlspecialchars($request['inclusiveDate']) . '</td>';
                echo '<td>' . htmlspecialchars($request['inclusiveTime']) . '</td>';
                if ($_SESSION['role'] === 'teacher'){
                    echo '<td><a href="supervisor_approve.php?id=' . $request['id'] . '" class="btn-liquid">View</a></td>';
                } else {
                    echo '<td><a href="admin_approve.php?status=Pending&search='. $request['id'] .'" class="btn-liquid">View</a></td>';
                }
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
            echo '</div>';
        }
    }
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Home</title>
        <?php include('helperFiles/headData.php'); ?>
        <style>
            .main-wrapper { width: 80vw; min-height: 80vh; margin: 0 auto 10vh; padding: 20px; background:#e6e6e6; border-radius:30px; box-shadow:0 2px 12px rgba(0,0,0,0.1); }
            .card { text-align:center; padding:15px; border-radius:15px; margin-bottom:25px; box-shadow:0 2px 6px rgba(0,0,0,0.1); transition:0.2s; background:#fff; display:flex; flex-direction:column; justify-content:space-between; }
            .card:hover { transform:translateY(-5px); }
            .equal-height-row { display: flex; flex-wrap: wrap; }
            .equal-height-row:before, .equal-height-row:after { display: none; }
            .equal-height-row > [class*='col-'] { display:flex; flex-direction:column; }
            .equal-height-row > [class*='col-'] .card { width:100%; flex:1; }
            .fixed-img { width:100%; height:210px; object-fit:cover; border-radius:10px; cursor:pointer; }
            .skeleton { background: linear-gradient(90deg, #e0e0e0 25%, #f0f0f0 50%, #e0e0e0 75%); background-size: 200% 100%; animation: skeleton-shimmer 1.5s infinite; color: transparent; }
            @keyframes skeleton-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
            .image-divider { border-top:1px solid #dcdcdc; margin:15px auto; width:85%; }
            .text-danger { font-weight:bold; font-size:14px; }
            .card.disabled { opacity:0.6; filter:grayscale(80%); pointer-events:none; }
            #galleryOverlay { position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,15,15,0.65); z-index:9999; display:flex; align-items:center; justify-content:center; flex-direction:column; }
            .overlay-backdrop { position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.45); z-index:-1; }
            .gallery-content { position:relative; width:80%; max-width:900px; text-align:center; color:white; }
            .gallery-close { position:absolute; top:-20px; right:-20px; font-size:40px; background:transparent; border:none; color:white; cursor:pointer; }
            .gallery-title { margin-bottom:20px; font-size:28px; font-weight:bold; }
            .carousel-wrapper { display:flex; align-items:center; justify-content:space-between; position:relative; }
            .carousel-images { flex:1; text-align:center; }
            .carousel-images img { max-width:100%; max-height:500px; border-radius:10px; }
            .gallery-nav { font-size:40px; background:transparent; border:none; color:white; cursor:pointer; width:60px; text-align:center; user-select:none; }
            .schedule-block { width:100%; text-align:left; border-top:1px solid #e1e7ff; padding-top:12px; margin-top:12px; margin-bottom:15px; }
            .schedule-date-label { font-weight:600; color:#0e0054; font-size:14px; margin-bottom:6px; display:block; }
            .slot-pill-list { display:flex; flex-wrap:wrap; gap:8px; }
            .slot-pill { border-radius:999px; padding:6px 14px; font-size:12px; font-weight:600; display:inline-flex; flex-direction:column; gap:2px; border:1px solid transparent; min-width:140px; }
            .slot-pill-label { text-transform:uppercase; font-size:11px; letter-spacing:0.4px; }
            .slot-pill-time { font-size:13px; font-weight:700; }
            .slot-pill.approved { background:#e0f7e9; color:#0f6b3b; border-color:#b8e5c8; }
            .slot-pill.requested { background:#fff4e0; color:#b06a00; border-color:#ffd199; }
            .empty-slot-hint { margin:10px 0 0; font-size:13px; color:#6c757d; }
            .scilab-name { width: 80%; padding: 10px 12px; border-radius: 8px; font-weight: bold; color: #ffffff; margin: auto; margin-top: 15px; }

            .liquid-input {
                background: linear-gradient(135deg, rgba(43, 85, 196, 0.05), rgba(43, 85, 196, 0.15)) !important;
                backdrop-filter: blur(8px);
                -webkit-backdrop-filter: blur(8px);
                border: 1px solid rgba(43, 85, 196, 0.2) !important;
                border-radius: 20px !important;
                color: #2B55C4 !important;
                box-shadow: 0 4px 12px rgba(43, 85, 196, 0.1);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                font-weight: 600;
            }
            .liquid-input:focus { box-shadow: 0 8px 20px rgba(43, 85, 196, 0.2); border-color: rgba(43, 85, 196, 0.4) !important; outline: none; }

            @media (max-width: 768px) {
                .main-wrapper { width: 95vw; margin-bottom: 5vh; border-radius: 15px; padding: 10px; }
                .card { margin-bottom: 15px; }
            }
        </style>
    </head>

    <body>
        <?php include('helperFiles/header.php'); ?>

        <div style="text-align:center; margin-bottom:20px; font-size:4vh; font-weight:bold; color:#0e0054;">Welcome, <?= htmlspecialchars($username) ?>!</div>

        <div class="main-wrapper">
            <div class="container text-center my-4">
                <div class="d-inline-flex align-items-center gap-2" style="font-size:21px; padding:15px 0;">
                    <div style="font-size:3vh; font-weight:bold; color:#0e0054; margin-bottom:20px;">Request for Science Laboratory Utilization</div>
                    <label for="datepicker" class="form-label fw-bold mb-0" style="color: #0e0054;">Select Date of Use:</label>
                    <input type="date" id="datepicker" class="form-control liquid-input" style="width:250px; display:inline-block; margin-left:10px; font-size:16px;">
                </div>
            </div>

            <hr class="image-divider">

            <div class="container-fluid text-center" style="margin-top:30px;">
                <div class="row equal-height-row">
                    <?php
                        $labs = [];
                        $result = $conn->query("
                            SELECT sa.scilabName, sa.mainImagePath, sa.location, sa.color
                            FROM scilab_availability sa
                            WHERE sa.availability='Available' AND sa.status='active'
                        ");
                        while($row = $result->fetch_assoc()) $labs[] = $row;

                        if(count($labs) > 0):
                            foreach($labs as $lab):
                    ?>
                        <div class="col-md-4 col-sm-12">
                            <div class="card" data-lab="<?= htmlspecialchars($lab['scilabName']) ?>">
                                <img src="<?= htmlspecialchars($lab['mainImagePath']) ?>" alt="<?= htmlspecialchars($lab['scilabName']) ?>" class="fixed-img gallery-launch skeleton" data-lab="<?= htmlspecialchars($lab['scilabName']) ?>" onload="this.classList.remove('skeleton');" onerror="this.onerror=null; this.src='img/placeholder.svg'; this.classList.remove('skeleton');">
                                <h4 class="scilab-name mt-2" style="background-color: <?= htmlspecialchars($lab['color']) ?>;">
                                    <?= htmlspecialchars($lab['scilabName']) ?>
                                </h4>
                                <hr class="image-divider">
                                <p class="text-muted pending-count">0 pending request(s)</p>
                                <p><small><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($lab['location']) ?></small></p>
                                <div class="schedule-block mt-2">
                                    <span class="schedule-date-label">Schedule for <span class="selected-date-label" data-selected-date-label>--</span></span>
                                    <div class="slot-pill-list" data-lab-schedule></div>
                                    <p class="empty-slot-hint mb-0">No requests for this date yet.</p>
                                </div>
                                <a href="forms.php?scilabname=<?= htmlspecialchars($lab['scilabName']) ?>" class="btn-liquid mt-auto" style="margin: 15px auto 5px; width: 60%; justify-content: center;">REQUEST</a>
                            </div>
                        </div>
                    <?php endforeach; else: ?>
                        <div class="col-12"><p>No available science laboratories at the moment.</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

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

    <script>
        let galleryImages = [], currentIndex = 0;

        function showGallery(images, title) {
            galleryImages = images; currentIndex = 0;
            updateGalleryImage();
            $('#galleryTitle').text(title + ' - Gallery');
            $('#galleryOverlay').fadeIn();
        }

        function updateGalleryImage() {
            if(!galleryImages.length) return;
            $('#galleryCarousel').html(`<img src="${galleryImages[currentIndex]}" alt="Lab Image">`);
        }

        function nextImage() { if(galleryImages.length) { currentIndex=(currentIndex+1)%galleryImages.length; updateGalleryImage(); } }
        function prevImage() { if(galleryImages.length) { currentIndex=(currentIndex-1+galleryImages.length)%galleryImages.length; updateGalleryImage(); } }

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

        $(document).ready(function() {
            $('.gallery-launch').click(function() {
                const lab = $(this).data('lab');
                $.ajax({
                    url:'ajax/ajax_requester.php',
                    method:'POST',
                    data:{ action:'get_lab_images', lab:lab },
                    dataType:'json',
                    success:function(images){ images.length ? showGallery(images, lab) : showToast('No images available.', 'info'); },
                    error:function(){ showToast('Failed to load gallery.', 'error'); }
                });
            });

            $('.gallery-close').click(()=>$('#galleryOverlay').fadeOut());
            $('.gallery-nav.left').click(prevImage);
            $('.gallery-nav.right').click(nextImage);
            $(document).on('keydown', e=>{ if(e.key==='Escape') $('#galleryOverlay').fadeOut(); });

            const today = new Date().toISOString().split('T')[0];
            const datepicker = document.getElementById('datepicker');
            datepicker.value = today;
            datepicker.setAttribute('min', today);
            datepicker.dispatchEvent(new Event('change'));

            function updateCardsForDate(selectedDate) {
                updateSelectedDateLabels(selectedDate);
                $.post('ajax/store_date.php',{date:selectedDate});
                $.ajax({
                    url:'ajax/ajax_requester.php',
                    type:'POST',
                    data:{action:'get_day_snapshot', date:selectedDate},
                    dataType:'json',
                    success:function(payload){
                        const pendingCounts = payload.pendingCounts || {};
                        const schedules = payload.schedules || {};
                        $('.card').each(function(){
                            const card=$(this);
                            const labName=card.data('lab');
                            renderSlotList(card, schedules[labName] || []);
                            const pending=pendingCounts[labName] || 0;
                            card.find('.pending-count').text(`${pending} pending request(s)`);
                        });
                    },
                    error:function(){
                        showToast('Unable to refresh laboratory schedules. Please try again.', 'error');
                    }
                });
            }

            $('#datepicker').on('change', function() {
                updateCardsForDate($(this).val());
            });

            // initial load
            updateCardsForDate(today);

            window.addEventListener("pageshow", e=>{ if(e.persisted || (window.performance && window.performance.navigation.type===2)) window.location.reload(); });

        });
    </script>
</html>
