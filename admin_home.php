<?php
    include('helperFiles/db_connection.php');
    include('helperFiles/session_handler.php');

    if (!isset($_SESSION['role'])) {
        header("Location: index.php");
        exit();
    }
    if ($_SESSION['role'] != 'admin') {
        header("Location: requester_home.php");
        exit();
    }

    $email = $_SESSION['email'];
    $username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Home</title>
        <?php include('helperFiles/headData.php'); ?>

        <style>
            body { background-color: #f5f5f5; }
            .main-wrapper {
                width: 80vw; min-height: 80vh; margin: 0 auto 10vh;
                padding: 20px; background-color: #e6e6e6;
                border-radius: 30px; box-shadow: 0 2px 12px rgba(0,0,0,0.1);
            }
            .card, .lab-card {
                background-color: #fff; border-radius: 15px;
                box-shadow: 0 2px 6px rgba(0,0,0,0.1);
                text-align: center; display: flex;
                flex-direction: column; justify-content: space-between;
                transition: transform 0.2s ease-in-out; margin-bottom: 25px;
                padding: 15px;
            }
            .fixed-card, .add-scilab-card {
                min-height: 550px; display: flex;
                justify-content: center; align-items: center;
                text-align: center;
            }
            .lab-card {
                min-height: 520px; padding: 25px 15px;
                justify-content: space-between; align-items: center;
            }
            .fixed-img, .lab-img {
                width: 100%; object-fit: cover; border-radius: 10px;
            }
            .fixed-img { height: 210px; }
            .lab-img { height: 160px; }
            .unavailable { color: red; }
            .action-btns { margin-bottom: 10px; }
            .availability-label {
                margin-left: 10px; font-weight: bold; vertical-align: middle;
            }
            .image-divider {
                border: none; border-top: 2px solid #e0e0e0;
                width: 80%; margin: 15px auto;
            }

            /* Switch */
            .switch {
                position: relative; display: inline-block;
                width: 40px; height: 22px; vertical-align: middle;
            }
            .switch input { opacity: 0; width: 0; height: 0; }
            .slider {
                position: absolute; top: 0; left: 0; right: 0; bottom: 0;
                background-color: #ccc; transition: 0.4s; border-radius: 22px;
            }
            .slider::before {
                content: ""; position: absolute; height: 16px; width: 16px;
                left: 3px; bottom: 3px; background-color: white;
                transition: 0.4s; border-radius: 50%;
            }
            input:checked + .slider { background-color: #2b55c4; }
            input:checked + .slider::before { transform: translateX(18px); }

            /* Gallery */
            #galleryOverlay {
                position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                background: rgba(15,15,15,0.65); z-index: 9999;
                display: flex; align-items: center; justify-content: center;
                flex-direction: column;
            }
            .overlay-backdrop {
                position: absolute; top: 0; left: 0; width: 100%; height: 100%;
                background: rgba(0,0,0,0.45); z-index: -1;
            }
            .gallery-content {
                position: relative; width: 80%; max-width: 900px;
                text-align: center; color: white;
            }
            .gallery-close {
                position: absolute; top: -20px; right: -20px;
                font-size: 40px; background: transparent; border: none;
                color: white; cursor: pointer;
            }
            .gallery-title {
                margin-bottom: 20px; font-size: 28px; font-weight: bold;
            }
            .carousel-wrapper {
                display: flex; align-items: center; justify-content: space-between;
            }
            .carousel-images { flex: 1; text-align: center; }
            .carousel-images img {
                max-height: 500px; max-width: 100%; border-radius: 10px;
            }
            .gallery-nav {
                font-size: 40px; background: transparent; border: none;
                color: white; cursor: pointer; width: 60px; user-select: none;
            }
            .no-images-message {
                text-align: center; font-size: 1.2rem; color: #888;
                padding: 60px 20px;
            }
            #existingGalleryImages img {
                height: 100px; object-fit: cover;
                border-radius: 0.25rem; margin-right: 10px;
            }
            .image-wrapper {
                margin: 10px; position: relative; display: inline-block;
            }
            .image-wrapper img {
                height: 100px; width: auto; border-radius: 0.5rem;
                object-fit: cover;
            }
            .image-wrapper .delete-image-btn {
                position: absolute; top: -10px; right: -10px;
                padding: 6px 8px; font-size: 12px; z-index: 5;
            }
            .pending-link { color: inherit; text-decoration: none; }
            .pending-link:hover { text-decoration: underline; color: #2b55c4; }
            .scilab-color-picker {
                -webkit-appearance: none;
                -moz-appearance: none;
                appearance: none;
                width: 25px;
                height: 25px;
                background-color: transparent;
                border: none;
                cursor: pointer;
                border-radius: 50%;
                padding: 0;
                display: inline-block;
                vertical-align: middle;
            }
            .scilab-color-picker::-webkit-color-swatch {
                border-radius: 50%;
                border: 1px solid #ddd;
            }
            .scilab-color-picker::-moz-color-swatch { border-radius: 50%; border: 1px solid #ddd; }
        </style>
    </head>

    <body>
        <?php include('helperFiles/header.php'); ?>

        <div style="text-align:center; margin:20px 0; font-size:4vh; font-weight:bold; color:#0e0054;">
            Welcome, <?= htmlspecialchars($username) ?>!
        </div>

        <div class="main-wrapper">
            <div class="container-fluid text-center mt-4">
                <div class="row">
                    <?php
                    $labs = $conn->query("SELECT * FROM scilab_availability WHERE status='active' ORDER BY scilabName ASC");
                    $currentSY = $conn->query("SELECT MAX(value) AS currentSY FROM current")->fetch_assoc()['currentSY'];

                    while ($lab = $labs->fetch_assoc()):
                        $id = $lab['scilabName'];
                        $mainImagePath = '../' . $lab['mainImagePath'];
                        $imgVersion = file_exists($mainImagePath) ? filemtime($mainImagePath) : time();
                        $imgId = 'lab-img-' . preg_replace('/\s+/', '-', $id);

                        $pending = $conn->query("
                            SELECT COUNT(*) AS cnt FROM scilab_form_requests
                            WHERE scilabName='$id' AND statusScilabPersonnel='Pending' AND sy='$currentSY'
                        ")->fetch_assoc()['cnt'];
                    ?>
                    <div class="col-md-4 mb-4">
                        <div class="card fixed-card lab-card text-center" data-scilab="<?= htmlspecialchars($id) ?>">
                            <div style="width: 100%;">
                            <img id="<?= $imgId ?>" src="<?= htmlspecialchars($lab['mainImagePath']) ?>?v=<?= $imgVersion ?>"
                                alt="Lab <?= htmlspecialchars($id) ?>" class="fixed-img gallery-launch"
                                data-lab="<?= htmlspecialchars($id) ?>"
                                style="cursor:pointer; height:200px; object-fit:cover;">

                            <h4 class="mt-3 fw-bold text-primary"><?= htmlspecialchars($id) ?></h4>
                            <hr class="image-divider">
                            <p class="text-muted mb-1">
                                <a href="admin_approve.php?status=Pending&search=<?= urlencode($id) ?>" class="pending-link">
                                    <?= $pending ?> pending request(s)
                                </a>
                            </p>
                            <p class="mb-3"><small><i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($lab['location']) ?></small></p>
                            </div>

                            <div class="mb-3 text-center">
                                <span style="font-weight:bold; vertical-align: middle;">Color Display in Calendar: </span>
                                <input type="color"
                                    class="form-control form-control-color scilab-color-picker"
                                    value="<?= htmlspecialchars($lab['color']) ?>"
                                    data-old="<?= htmlspecialchars($lab['color']) ?>"
                                    data-scilab="<?= htmlspecialchars($id) ?>">
                            </div>

                            <div class="d-flex justify-content-center gap-2 mb-3">
                                <button class="btn btn-danger btn-sm" onclick="openRemoveModal('<?= $id ?>')">
                                    <i class="bi bi-trash"></i> Remove
                                </button>
                                <button class="btn btn-primary btn-sm" onclick="openEditModal('<?= $id ?>')">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                            </div>

                            <div class="d-flex justify-content-center align-items-center gap-2">
                                <label class="switch m-0">
                                    <input type="checkbox" onchange="toggleLab(this)">
                                    <span class="slider round"></span>
                                </label>
                                <span class="availability-label">Not Available</span>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>

                    <div class="col-md-4 mb-4">
                        <div class="card add-scilab-card d-flex align-items-center justify-content-center text-center"
                            style="min-height:420px; cursor:pointer;" onclick="$('#addLabModal').modal('show')">
                            <h4 class="m-auto">Add Science Laboratory</h4>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Lab Modal -->
            <div class="modal fade" id="addLabModal" tabindex="-1">
                <div class="modal-dialog">
                    <form id="addLabForm" enctype="multipart/form-data" class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add Science Laboratory</h5>
                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group"><label>Lab Name:</label><input type="text" name="lab_name" class="form-control" required></div>
                            <div class="form-group"><label>Location:</label><input type="text" name="lab_location" class="form-control" required></div>
                            <div class="form-group"><label>Upload Lab Image (JPG only):</label><input type="file" name="lab_image" class="form-control" accept=".jpg,.jpeg" required></div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-primary" type="submit">Add Lab</button>
                            <button class="btn btn-secondary" data-dismiss="modal">Cancel</button>
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
                            <button class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button class="btn btn-danger" onclick="confirmRemoveScilab()">Yes</button>
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
                            <input type="hidden" name="lab_id" id="editLabId">
                            <input type="hidden" name="lab_old_name" id="editLabOldName">
                            <div class="form-group"><label>Lab Name:</label><input type="text" name="lab_name" id="editLabName" class="form-control" required></div>
                            <div class="form-group"><label>Location:</label><input type="text" name="lab_location" id="editLabLocation" class="form-control" required></div>
                            <div class="form-group">
                                <label>Upload New Image (JPG only):</label>
                                <input type="file" name="lab_image" id="labImage" class="form-control" accept=".jpg,.jpeg">
                                <small class="text-muted">If uploaded, this will replace the current image.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-primary" type="submit">Save</button>
                            <button class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Edit Gallery Modal -->
            <div class="modal fade" id="editGalleryModal" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content shadow-lg rounded">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title"><i class="fas fa-images me-2"></i>Edit Lab Gallery</h5>
                            <button class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <form id="galleryEditForm" enctype="multipart/form-data">
                                <input type="hidden" name="labName" id="editLabName">
                                <div class="form-group mb-4">
                                    <label class="fw-bold">Add New Images</label>
                                    <input type="file" name="galleryImages[]" id="newGalleryImages" class="form-control" multiple accept=".jpg,.jpeg,.png,.gif">
                                    <small class="form-text text-muted">Select multiple JPG, PNG, or GIF images.</small>
                                </div>
                                <div>
                                    <label class="fw-bold">Existing Images</label>
                                    <div id="existingGalleryImages" class="d-flex flex-wrap gap-3 border rounded p-3 bg-light" style="min-height:120px;"></div>
                                </div>
                                <div class="text-end mt-4">
                                    <button class="btn btn-success"><i class="fas fa-upload me-1"></i>Upload Images</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gallery Overlay -->
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
                    <div class="gallery-footer">
                        <button id="editGalleryBtn" class="btn btn-warning text-white fw-bold mt-3">Edit Gallery</button>
                    </div>
                </div>
            </div>
        </div>

        <?php include 'helperFiles/footer.php'; ?>
    </body>

    <script>
        let galleryImages = [], currentIndex = 0;

        // ===== Gallery Display =====
        function showGallery(images, title, isMain = false) {
            galleryImages = images; currentIndex = 0;
            const container = $('#galleryImageContainer').empty();
            if (isMain) {
                container.append(`
                    <div class="text-center">
                        <img src="${images[0]}" class="img-fluid rounded shadow-sm" style="max-height:300px;" alt="Main Lab Image">
                        <p class="text-muted mt-2">No gallery images found. Showing main lab image.</p>
                    </div>
                `);
                nextImage();
            } else updateGalleryImage();
            $('#galleryTitle').text(`${title} - Gallery`);
            $('#galleryOverlay').fadeIn();
        }
        function updateGalleryImage() {
            if (galleryImages.length === 0) return;
            $('#galleryCarousel').html(`<img src="${galleryImages[currentIndex]}" alt="Lab Image">`);
        }
        function nextImage() {
            if (galleryImages.length === 0) return;
            currentIndex = (currentIndex + 1) % galleryImages.length;
            updateGalleryImage();
        }
        function prevImage() {
            if (galleryImages.length === 0) return;
            currentIndex = (currentIndex - 1 + galleryImages.length) % galleryImages.length;
            updateGalleryImage();
        }


        // ===== Gallery and Availability =====
        $(document).ready(() => {
            // Open gallery
            $('.gallery-launch').click(function () {
                const lab = $(this).data('lab'), main = `img/labimages/${lab}.jpg`;
                $.post('ajax/ajax_admin.php', { action: 'get_lab_images', lab }, imgs => {
                    imgs.length ? showGallery(imgs, lab) : showGallery([main], lab, true);
                }, 'json').fail(() => alert('Failed to load gallery.'));
            });

            // Gallery controls
            $('.gallery-close').click(() => $('#galleryOverlay').fadeOut());
            $('.gallery-nav.left').click(prevImage);
            $('.gallery-nav.right').click(nextImage);
            $(document).on('keydown', e => { if (e.key === 'Escape') $('#galleryOverlay').fadeOut(); });

            // Availability setup
            $.post('ajax/ajax_admin.php', { action: 'get_availability' }, res => {
                const data = JSON.parse(res);
                $('.card').each(function () {
                    const card = $(this), id = card.data('scilab'),
                        chk = card.find('input[type="checkbox"]'),
                        lbl = card.find('.availability-label');
                    const avail = data[id] === 'Available';
                    chk.prop('checked', avail);
                    lbl.text(avail ? 'Available' : 'Not Available')
                    .css('color', avail ? '#28a745' : 'red');
                });
            }).fail(() => alert('Failed to fetch availability data.'));
        });

        // ===== Edit Laboratory =====
        function openEditModal(lab) {
            $.post('ajax/ajax_admin.php', { action: 'get_lab_details', lab_id: lab }, data => {
                if (!data) return alert('Failed to fetch lab details.');
                $('#editLabOldName').val(lab);
                $('#editLabName').val(data.oldName);
                $('#editLabLocation').val(data.location);
                $('#labImage').val('');
                $('#editLabModal').modal('show');
            }, 'json').fail(() => alert('Error fetching lab details.'));
        }
        $('#editLabForm').submit(e => {
            e.preventDefault();
            const fd = new FormData(e.target);
            fd.append('action', 'edit_lab_image');
            $.ajax({
                url: 'ajax/ajax_admin.php', type: 'POST', data: fd,
                contentType: false, processData: false,
                success: res => {
                    res = res.trim();
                    if (res !== 'success') return alert('Update failed: ' + res);
                    alert('Lab updated successfully!');
                    $('#editLabModal').modal('hide');
                    const newName = $('#editLabName').val().trim(),
                        oldName = $('#editLabOldName').val().trim(),
                        newSrc = `img/labimages/${newName}.jpg?v=${Date.now()}`,
                        idNew = '#lab-img-' + newName.replace(/\s+/g, '-'),
                        idOld = '#lab-img-' + oldName.replace(/\s+/g, '-');
                    const img = $(idNew).length ? $(idNew) : $(idOld);
                    img.attr({ src: newSrc, id: idNew.substring(1) });
                    if (oldName !== newName) {
                        const card = img.closest('.card');
                        card.find('h4').text(newName);
                        card.attr('data-scilab', newName);
                    }
                }
            });
        });

        // ===== Availability Toggle =====
        function toggleLab(chk) {
            const card = $(chk).closest('.card'),
                name = card.find('h4').text().trim(),
                label = card.find('.availability-label'),
                newState = chk.checked ? 'Available' : 'Not Available';
            $.post('ajax/ajax_admin.php', { scilabName: name, availability: newState }, () => {
                label.text(newState).css('color', chk.checked ? '#28a745' : 'red');
            }).fail(() => alert('Failed to update availability.'));
        }

        // ===== Add and Remove Labs =====
        $('#addLabForm').submit(e => {
            e.preventDefault();
            const fd = new FormData(e.target);
            fd.append('action', 'add_new_laboratory');
            $.ajax({
                url: 'ajax/ajax_admin.php', method: 'POST', data: fd,
                contentType: false, processData: false,
                success: res => {
                    alert(res.trim() === 'Success' ? 'Lab added successfully!' : res);
                    location.reload();
                },
                error: () => alert('Error adding lab.')
            });
        });
        function openRemoveModal(id) {
            $('#removeScilabId').val(id);
            $('#confirmRemoveModal').modal('show');
        }
        function confirmRemoveScilab() {
            const id = $('#removeScilabId').val();
            $.post('ajax/ajax_admin.php', { action: 'remove_scilab', scilabName: id }, res => {
                res.trim() === 'Success' ? location.reload() : alert('Failed: ' + res);
            }).fail(() => alert('Failed to send remove request.'));
        }

        // ===== Edit Gallery =====
        $('#editGalleryBtn').click(() => {
            const lab = $('#galleryTitle').text().split(' - ')[0];
            $('#editLabName').val(lab);
            $('#editGalleryModal, #galleryOverlay').modal('hide').fadeOut();
            $.post('ajax/ajax_admin.php', { action: 'get_lab_images', lab }, imgs => {
                const cont = $('#existingGalleryImages').empty();
                if (!imgs.length) return cont.html('<p>No images found.</p>');
                imgs.forEach(img => {
                    const name = img.split('/').pop();
                    cont.append(`
                        <div class="image-wrapper position-relative">
                            <img src="${img}" class="img-thumbnail shadow-sm">
                            <button type="button" class="btn btn-sm btn-danger rounded-circle shadow delete-image-btn"
                                data-img="${name}" title="Delete Image">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>`);
                });
            }, 'json');
            $('#editGalleryModal').modal('show');
        });
        $('#galleryEditForm').submit(e => {
            e.preventDefault();
            const fd = new FormData(e.target);
            fd.append('labName', $('#editLabName').val());
            fd.append('action', 'upload_gallery_images');
            $.ajax({
                url: 'ajax/ajax_admin.php', method: 'POST', data: fd,
                contentType: false, processData: false,
                success: () => {
                    alert('Images uploaded!');
                    $('#editGalleryModal').modal('hide');
                }
            });
        });
        $(document).on('click', '.delete-image-btn', function () {
            const img = $(this).data('img'), lab = $('#editLabName').val();
            $.post('ajax/ajax_admin.php', { action: 'delete_gallery_image', labName: lab, fileName: img }, () => {
                $(`button[data-img="${img}"]`).parent().remove();
            });
        });

        // ===== Edit SciLab Color =====
        $(document).on('change', '.scilab-color-picker', function () {
            const picker = $(this)
            const newColor = picker.val()
            const oldColor = picker.data('old')
            const scilab = picker.data('scilab')

            if (!confirm('Are you sure you want to change this scilab color?')) {
                picker.val(oldColor)
                return
            }

            $.post('ajax/ajax_admin.php', {
                action: 'update_scilab_color',
                scilabName: scilab,
                color: newColor
            }, res => {
                if (res.trim() !== 'success') {
                    alert('Failed to update color')
                    picker.val(oldColor)
                } else {
                    picker.data('old', newColor)
                }
            }).fail(() => {
                alert('Server error')
                picker.val(oldColor)
            })
        })
    </script>
</html>