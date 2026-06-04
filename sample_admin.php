<?php
    include('../scilab/helperFiles/db_connection.php');
    include('helperFiles/session_handler.php');

    if (!isset($_SESSION['role'])) {
        header("Location: index.php");
        exit();
    }
    if ($_SESSION['role'] != 'admin') {
        header("Location: requester_home.php");
        exit();
    }

    $username = $_SESSION['username'] ?? 'Admin';

    /* Fetch current school year for pending counts. */
    $syResult = $conn->query("SELECT MAX(value) AS currentSY FROM current");
    $currentSY = ($syResult && $syResult->num_rows > 0) ? $syResult->fetch_assoc()['currentSY'] : null;

    /* Fetch all active labs with pending request counts. */
    $labsJson = [];
    $labs = $conn->query("SELECT * FROM scilab_availability WHERE status='active' ORDER BY scilabName ASC");
    if ($labs) {
        while ($lab = $labs->fetch_assoc()) {
            $id = $lab['scilabName'];
            $pending = 0;
            if ($currentSY) {
                $pStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM scilab_form_requests WHERE scilabName=? AND statusScilabPersonnel='Pending' AND sy=?");
                $pStmt->bind_param("ss", $id, $currentSY);
                $pStmt->execute();
                $pending = (int)($pStmt->get_result()->fetch_assoc()['cnt'] ?? 0);
                $pStmt->close();
            }
            $imgVersion = file_exists($lab['mainImagePath']) ? filemtime($lab['mainImagePath']) : time();
            $labsJson[] = [
                'id'               => $id,
                'laboratoryName'   => $id,
                'location'         => $lab['location'] ?? '',
                'image'            => htmlspecialchars($lab['mainImagePath']) . '?v=' . $imgVersion,
                'availability'     => $lab['availability'] ?? 'Not Available',
                'color'            => $lab['color'] ?? '#2B55C4',
                'pendingRequests'  => $pending,
            ];
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Admin Home</title>
        <?php include('helperFiles/headData.php'); ?>
        <link rel="stylesheet" href="css/laboratory-card.css">
        <style>
            .main-wrapper {
                width: 80%;
                margin: 0 auto;
            }
            @media (max-width: 768px) {
                .main-wrapper { width: 100%; }
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

            /* ─── ADD LAB CARD ──────────────────────────────────── */
            .add-lab-card {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                min-height: 260px;
                border-radius: var(--radius-card);
                border: 2px dashed var(--color-border);
                background: rgba(43,85,196,0.03);
                cursor: pointer;
                transition: all 0.25s ease;
                color: var(--color-text-secondary);
                font-weight: 600;
                gap: 10px;
            }
            .add-lab-card:hover {
                border-color: var(--color-primary-mid);
                background: rgba(43,85,196,0.07);
                color: var(--color-primary-mid);
                transform: translateY(-2px);
            }
            .add-lab-card i { font-size: 2rem; }

            /* ─── COLOR PICKER ──────────────────────────────────── */
            .scilab-color-picker {
                -webkit-appearance: none;
                -moz-appearance: none;
                appearance: none;
                width: 26px; height: 26px;
                background: transparent;
                border: none;
                cursor: pointer;
                border-radius: 50%;
                padding: 0;
                display: inline-block;
                vertical-align: middle;
            }
            .scilab-color-picker::-webkit-color-swatch { border-radius: 50%; border: 1px solid #ddd; }
            .scilab-color-picker::-moz-color-swatch   { border-radius: 50%; border: 1px solid #ddd; }

            /* ─── SWITCH (availability toggle) ─────────────────── */
            .switch {
                position: relative; display: inline-block;
                width: 140px; height: 34px; vertical-align: middle;
            }
            .switch input { opacity: 0; width: 0; height: 0; }
            .slider {
                position: absolute; top: 0; left: 0; right: 0; bottom: 0;
                background: linear-gradient(135deg,rgba(200,200,200,.3),rgba(200,200,200,.5));
                backdrop-filter: blur(4px);
                -webkit-backdrop-filter: blur(4px);
                border: 1px solid rgba(255,255,255,.4);
                transition: .4s; border-radius: 34px;
                box-shadow: inset 0 1px 4px rgba(0,0,0,.1);
            }
            .slider::before {
                content: ""; position: absolute;
                height: 26px; width: 26px; left: 4px; bottom: 3px;
                background: white; transition: .4s; border-radius: 50%;
                box-shadow: 0 2px 5px rgba(0,0,0,.2); z-index: 2;
            }
            input:checked + .slider {
                background: linear-gradient(135deg,rgba(43,85,196,.7),rgba(43,85,196,.9));
                border-color: rgba(43,85,196,.3);
            }
            input:checked + .slider::before { transform: translateX(106px); }
            .slider::after {
                content: 'Not Available'; color: #666;
                position: absolute; right: 15px; top: 50%; transform: translateY(-50%);
                font-size: 12px; font-weight: bold; transition: all .4s;
            }
            input:checked + .slider::after { content: 'Available'; color: white; right: unset; left: 15px; }

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
            #existingGalleryImages img { height: 100px; object-fit: cover; border-radius: .25rem; margin-right: 10px; }
            .image-wrapper    { margin: 10px; position: relative; display: inline-block; }
            .image-wrapper img { height: 100px; width: auto; border-radius: .5rem; object-fit: cover; }
            .image-wrapper .delete-image-btn {
                position: absolute; top: -10px; right: -10px;
                padding: 6px 8px; font-size: 12px; z-index: 5;
            }

            /* ─── CARD ADMIN EXTRAS ─────────────────────────────── */
            .lab-card__admin-footer {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 10px;
                padding: 12px 0 4px;
                border-top: 1px solid var(--color-border);
                margin-top: 10px;
            }
            .lab-card__admin-footer .lab-card__color-row {
                display: flex;
                align-items: center;
                gap: 6px;
                font-size: 12px;
                color: var(--color-text-secondary);
                font-weight: 600;
            }
            .lab-card__admin-actions {
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
                justify-content: center;
            }

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

        <!-- Pass PHP lab data to JavaScript -->
        <script>
            const labsData = <?= json_encode($labsJson, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        </script>

        <div class="main-wrapper">
            <!-- Heading row -->
            <div class="heading">
                <p class="heading__welcome">Welcome, <span><?= htmlspecialchars($username) ?></span></p>
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

        <!-- ══════════════════════════════════════════════════
             MODALS (ported from admin_home.php)
        ═══════════════════════════════════════════════════ -->

        <!-- Add Lab Modal -->
        <div class="modal fade" id="addLabModal" tabindex="-1">
            <div class="modal-dialog">
                <form id="addLabForm" enctype="multipart/form-data" class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Science Laboratory</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group"><label>Lab Name:</label><input type="text" name="lab_name" class="form-control liquid-input" required></div>
                        <div class="form-group"><label>Location:</label><input type="text" name="lab_location" class="form-control liquid-input" required></div>
                        <div class="form-group"><label>Upload Lab Image (JPG only):</label><input type="file" name="lab_image" class="form-control liquid-input" accept=".jpg,.jpeg" required></div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-liquid-success" type="submit">Add Lab</button>
                        <button class="btn-liquid-secondary" data-dismiss="modal" type="button">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Confirm Remove Modal -->
        <div class="modal fade" id="confirmRemoveModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Remove</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">Are you sure you want to remove this science laboratory?</div>
                    <div class="modal-footer">
                        <input type="hidden" id="removeScilabId">
                        <button class="btn-liquid-secondary" data-dismiss="modal" type="button">Cancel</button>
                        <button class="btn-liquid-danger" onclick="confirmRemoveScilab()" type="button">Yes, Remove</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Lab Modal -->
        <div class="modal fade" id="editLabModal" tabindex="-1">
            <div class="modal-dialog">
                <form id="editLabForm" enctype="multipart/form-data" class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Science Laboratory</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="lab_id"       id="editLabId">
                        <input type="hidden" name="lab_old_name" id="editLabOldName">
                        <div class="form-group"><label>Lab Name:</label><input type="text" name="lab_name" id="editLabNameInput" class="form-control liquid-input" required></div>
                        <div class="form-group"><label>Location:</label><input type="text" name="lab_location" id="editLabLocation" class="form-control liquid-input" required></div>
                        <div class="form-group">
                            <label>Upload New Image (JPG only):</label>
                            <input type="file" name="lab_image" id="labImageInput" class="form-control liquid-input" accept=".jpg,.jpeg">
                            <small class="text-muted">If uploaded, this will replace the current image.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-liquid-success" type="submit">Save</button>
                        <button class="btn-liquid-secondary" data-dismiss="modal" type="button">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Gallery Modal -->
        <div class="modal fade" id="editGalleryModal" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content shadow-lg">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-images me-2"></i>Edit Lab Gallery</h5>
                        <button class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <form id="galleryEditForm" enctype="multipart/form-data">
                            <input type="hidden" name="labName" id="galleryEditLabName">
                            <div class="form-group mb-4">
                                <label class="fw-bold">Add New Images</label>
                                <input type="file" name="galleryImages[]" id="newGalleryImages" class="form-control" multiple accept=".jpg,.jpeg,.png,.gif">
                                <small class="form-text text-muted">Select multiple JPG, PNG, or GIF images.</small>
                            </div>
                            <div>
                                <label class="fw-bold">Existing Images</label>
                                <div id="existingGalleryImages" class="d-flex flex-wrap gap-3 border rounded p-3 bg-light" style="min-height:120px;"></div>
                            </div>
                            <div class="text-right mt-4">
                                <button class="btn-liquid-success" type="submit"><i class="bi bi-upload me-1"></i>Upload Images</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
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
                <div class="gallery-footer mt-3">
                    <button id="editGalleryBtn" class="btn-liquid-info">Edit Gallery</button>
                </div>
            </div>
        </div>

        <?php include('helperFiles/footer.php'); ?>
    </body>

    <!-- Laboratory card component -->
    <script src="helperFiles/laboratory-card.js"></script>

    <script>
        /* ══════════════════════════════════════════════════════
           GALLERY RENDERER + VIEW SWITCHER
        ═══════════════════════════════════════════════════════ */

        let currentVariant = 'complete';

        /**
         * Build the gallery including the "Add Lab" card at the end.
         */
        function renderGallery(variant) {
            const container = document.getElementById('lab-gallery');
            if (!container) return;

            container.className = `lab-gallery--${variant}`;

            // Render lab cards via the JS component
            container.innerHTML = labsData.map(lab => createLabCard(lab, variant)).join('');

            // Append "Add Lab" card
            const addCard = document.createElement('div');
            addCard.className = 'add-lab-card';
            addCard.innerHTML = '<i class="bi bi-plus-circle"></i><span>Add Science Laboratory</span>';
            addCard.onclick = () => $('#addLabModal').modal('show');
            container.appendChild(addCard);

            // Inject admin extras (color picker + action buttons + toggle) into each card
            labsData.forEach(lab => {
                const card = document.getElementById(`lab-card-${variant}-${lab.id}`);
                if (!card) return;
                _injectAdminControls(card, lab, variant);
            });

            // Set toggle states from live availability
            _refreshToggles();

            if (typeof AOS !== 'undefined') AOS.refresh();
        }

        /**
         * Append color picker + action buttons + availability toggle into a rendered card.
         */
        function _injectAdminControls(card, lab, variant) {
            const footer = card.querySelector('.lab-card__footer');
            if (!footer) return;

            // Build admin extras HTML
            const extras = document.createElement('div');
            extras.className = 'lab-card__admin-footer';
            extras.innerHTML = `
                <div class="lab-card__color-row">
                    <span style="font-size:12px;font-weight:600;">Calendar Color:</span>
                    <input type="color"
                        class="scilab-color-picker"
                        value="${_escapeHtml(lab.color)}"
                        data-old="${_escapeHtml(lab.color)}"
                        data-scilab="${_escapeHtml(lab.id)}">
                </div>
                <div class="lab-card__admin-actions">
                    <a href="admin_approve.php?status=Pending&search=${encodeURIComponent(lab.id)}"
                       class="btn-liquid" style="font-size:12px;">
                        <i class="bi bi-clock me-1"></i>${lab.pendingRequests} Pending
                    </a>
                    <button class="btn-liquid admin-edit-btn" data-scilab="${_escapeHtml(lab.id)}" style="font-size:12px;">
                        <i class="bi bi-pencil me-1"></i>Edit
                    </button>
                    <button class="btn-liquid-danger admin-remove-btn" data-scilab="${_escapeHtml(lab.id)}" style="font-size:12px;">
                        <i class="bi bi-trash me-1"></i>Remove
                    </button>
                </div>
                <label class="switch m-0">
                    <input type="checkbox"
                        class="lab-toggle-chk"
                        data-scilab="${_escapeHtml(lab.id)}"
                        onchange="toggleLab(this)">
                    <span class="slider"></span>
                </label>`;

            // Replace the default footer (which has a Request button) with admin version
            footer.replaceWith(extras);
        }

        /**
         * Fetch live availability and set all toggle checkboxes.
         */
        function _refreshToggles() {
            $.post('ajax/ajax_admin.php', { action: 'get_availability' }, res => {
                let data;
                try { data = JSON.parse(res); } catch(e) { return; }
                document.querySelectorAll('.lab-toggle-chk').forEach(chk => {
                    const name = chk.dataset.scilab;
                    if (data[name] !== undefined) chk.checked = data[name] === 'Available';
                });
            }).fail(() => showToast('Failed to fetch availability.', 'error'));
        }

        /**
         * switchView — called by heading buttons.
         */
        function switchView(btn, variant) {
            document.querySelectorAll('.view-selector__btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentVariant = variant;
            renderGallery(variant);
        }

        /* ══════════════════════════════════════════════════════
           AVAILABILITY TOGGLE
        ═══════════════════════════════════════════════════════ */

        function toggleLab(chk) {
            const name     = chk.dataset.scilab;
            const newState = chk.checked ? 'Available' : 'Not Available';
            $.post('ajax/ajax_admin.php', { scilabName: name, availability: newState })
             .fail(() => showToast('Failed to update availability.', 'error'));
        }

        /* ══════════════════════════════════════════════════════
           EDIT LABORATORY
        ═══════════════════════════════════════════════════════ */

        function openEditModal(labName) {
            $.post('ajax/ajax_admin.php', { action: 'get_lab_details', lab_id: labName }, data => {
                if (!data) return showToast('Failed to fetch lab details.', 'error');
                $('#editLabOldName').val(labName);
                $('#editLabNameInput').val(data.oldName);
                $('#editLabLocation').val(data.location);
                $('#labImageInput').val('');
                $('#editLabModal').modal('show');
            }, 'json').fail(() => showToast('Error fetching lab details.', 'error'));
        }

        $('#editLabForm').submit(function(e) {
            e.preventDefault();
            const fd = new FormData(this);
            fd.append('action', 'edit_lab_image');
            $.ajax({
                url: 'ajax/ajax_admin.php', type: 'POST', data: fd,
                contentType: false, processData: false,
                success: res => {
                    res = res.trim();
                    if (res !== 'success') return showToast('Update failed: ' + res, 'error');
                    showToast('Lab updated successfully!', 'success');
                    $('#editLabModal').modal('hide');
                    location.reload();
                },
                error: () => showToast('Error updating lab.', 'error')
            });
        });

        /* ══════════════════════════════════════════════════════
           REMOVE LABORATORY
        ═══════════════════════════════════════════════════════ */

        function openRemoveModal(labName) {
            $('#removeScilabId').val(labName);
            $('#confirmRemoveModal').modal('show');
        }

        function confirmRemoveScilab() {
            const id = $('#removeScilabId').val();
            $.post('ajax/ajax_admin.php', { action: 'remove_scilab', scilabName: id }, res => {
                res.trim() === 'Success' ? location.reload() : showToast('Failed: ' + res, 'error');
            }).fail(() => showToast('Failed to send remove request.', 'error'));
        }

        /* ══════════════════════════════════════════════════════
           ADD LABORATORY
        ═══════════════════════════════════════════════════════ */

        $('#addLabForm').submit(function(e) {
            e.preventDefault();
            const fd = new FormData(this);
            fd.append('action', 'add_new_laboratory');
            $.ajax({
                url: 'ajax/ajax_admin.php', method: 'POST', data: fd,
                contentType: false, processData: false,
                success: res => {
                    if (res.trim() === 'Success') {
                        showToast('Lab added successfully!', 'success');
                        location.reload();
                    } else {
                        showToast(res, 'error');
                    }
                },
                error: () => showToast('Error adding lab.', 'error')
            });
        });

        /* ══════════════════════════════════════════════════════
           GALLERY (image carousel)
        ═══════════════════════════════════════════════════════ */

        let galleryImages = [], currentIndex = 0;

        function showGallery(images, title, isMain = false) {
            galleryImages = images;
            currentIndex = 0;
            if (isMain) {
                $('#galleryCarousel').html(`<img src="${images[0]}" class="img-fluid rounded" alt="Main Lab Image" style="max-height:500px;">`);
            } else {
                updateGalleryImage();
            }
            $('#galleryTitle').text(title + ' — Gallery');
            $('#galleryOverlay').fadeIn();
        }

        function updateGalleryImage() {
            if (!galleryImages.length) return;
            $('#galleryCarousel').html(`<img src="${galleryImages[currentIndex]}" alt="Lab Image">`);
        }

        function nextImage() { if (galleryImages.length) { currentIndex = (currentIndex + 1) % galleryImages.length; updateGalleryImage(); } }
        function prevImage() { if (galleryImages.length) { currentIndex = (currentIndex - 1 + galleryImages.length) % galleryImages.length; updateGalleryImage(); } }

        // Gallery controls
        $('.gallery-close').click(() => $('#galleryOverlay').fadeOut());
        $('.gallery-nav.left').click(prevImage);
        $('.gallery-nav.right').click(nextImage);
        $(document).on('keydown', e => { if (e.key === 'Escape') $('#galleryOverlay').fadeOut(); });

        // Open gallery when lab image is clicked (delegated — cards render after page load)
        $(document).on('click', '.lab-card__image', function() {
            const card = $(this).closest('[data-lab-id]');
            const lab  = card.data('lab-id');
            const main = `img/labimages/${lab}.jpg`;
            $.post('ajax/ajax_admin.php', { action: 'get_lab_images', lab }, imgs => {
                imgs.length ? showGallery(imgs, lab) : showGallery([main], lab, true);
            }, 'json').fail(() => showToast('Failed to load gallery.', 'error'));
        });

        /* ══════════════════════════════════════════════════════
           EDIT GALLERY
        ═══════════════════════════════════════════════════════ */

        $('#editGalleryBtn').click(() => {
            const lab = $('#galleryTitle').text().replace(' — Gallery', '').trim();
            $('#galleryEditLabName').val(lab);
            $('#galleryOverlay').fadeOut();
            $.post('ajax/ajax_admin.php', { action: 'get_lab_images', lab }, imgs => {
                const cont = $('#existingGalleryImages').empty();
                if (!imgs.length) return cont.html('<p class="text-muted">No images found.</p>');
                imgs.forEach(img => {
                    const name = img.split('/').pop();
                    cont.append(`
                        <div class="image-wrapper">
                            <img src="${img}" class="img-thumbnail shadow-sm">
                            <button type="button" class="btn btn-sm btn-danger rounded-circle shadow delete-image-btn"
                                data-img="${name}" title="Delete Image">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>`);
                });
            }, 'json');
            $('#editGalleryModal').modal('show');
        });

        $('#galleryEditForm').submit(function(e) {
            e.preventDefault();
            const fd = new FormData(this);
            fd.append('action', 'upload_gallery_images');
            $.ajax({
                url: 'ajax/ajax_admin.php', method: 'POST', data: fd,
                contentType: false, processData: false,
                success: () => { showToast('Images uploaded!', 'success'); $('#editGalleryModal').modal('hide'); },
                error:   () => showToast('Upload failed.', 'error')
            });
        });

        $(document).on('click', '.delete-image-btn', function() {
            const img = $(this).data('img');
            const lab = $('#galleryEditLabName').val();
            $.post('ajax/ajax_admin.php', { action: 'delete_gallery_image', labName: lab, fileName: img }, () => {
                $(this).closest('.image-wrapper').remove();
            });
        });

        // Click event delegation for Edit and Remove buttons
        $(document).on('click', '.admin-edit-btn', function() {
            const lab = $(this).data('scilab');
            openEditModal(lab);
        });

        $(document).on('click', '.admin-remove-btn', function() {
            const lab = $(this).data('scilab');
            openRemoveModal(lab);
        });

        /* ══════════════════════════════════════════════════════
           COLOR PICKER
        ═══════════════════════════════════════════════════════ */

        $(document).on('change', '.scilab-color-picker', function() {
            const picker   = $(this);
            const newColor = picker.val();
            const oldColor = picker.data('old');
            const scilab   = picker.data('scilab');
            if (!confirm('Change the calendar color for ' + scilab + '?')) { picker.val(oldColor); return; }
            $.post('ajax/ajax_admin.php', { action: 'update_scilab_color', scilabName: scilab, color: newColor }, res => {
                if (res.trim() !== 'success') { showToast('Failed to update color', 'error'); picker.val(oldColor); }
                else picker.data('old', newColor);
            }).fail(() => { showToast('Server error', 'error'); picker.val(oldColor); });
        });

        /* ══════════════════════════════════════════════════════
           INIT — render gallery on page load
        ═══════════════════════════════════════════════════════ */

        $(document).ready(() => renderGallery('complete'));
    </script>
</html>