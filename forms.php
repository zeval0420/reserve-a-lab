<?php
    include('helperFiles/db_connection.php');
    include('helperFiles/session_handler.php');

    $email = $_SESSION['email'];
    $username = $_SESSION['username'];

    if ($_SESSION['role'] == 'admin') {
        header("Location: index.php");
        exit();
    }

    $selected_date = $_SESSION['selected_date'] ?? null;
    $defaultVenue = $_GET['scilabname'] ?? '';

    $venues = [];
    $result = $conn->query("SELECT scilabName FROM scilab_availability WHERE availability='Available' AND status='active'");
    while ($row = $result->fetch_assoc()) {
        $venues[$row['scilabName']] = $row['scilabName'];
    }

    // Grades and Sections
    $sectionOptions = [];
    $result = $conn->query("SELECT grade, section FROM section ORDER BY grade, section");
    while ($row = $result->fetch_assoc()) {
        $sectionOptions[$row['grade']][] = $row['section'];
    }

    // Subjects (active only)
    $subjectOptions = [];
    if (isset($_GET['grade']) && is_numeric($_GET['grade'])) {
        $selectedGrade = intval($_GET['grade']);
        $stmt = $conn->prepare("SELECT DISTINCT subjectCode, subjectAcademicUnit FROM subject WHERE status='active' AND subjectGradeLevel=? ORDER BY subjectCode ASC");
        $stmt->bind_param("i", $selectedGrade);
        $stmt->execute();
        $subjectOptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Teachers (active only)
    $teacherOptions = [];
    $result = $conn->query("SELECT employeeID, firstname, middlename, lastname FROM accounts WHERE status='active' ORDER BY lastname, firstname, middlename ASC");
    while ($row = $result->fetch_assoc()) {
        $teacherOptions[] = [
            'id' => $row['employeeID'],
            'name' => $row['lastname'] . ', ' . $row['firstname'] . ' ' . $row['middlename']
        ];
    }

    // Students grouped by grade
    $studentOptions = [];
    $result = $conn->query("SELECT firstname, middlename, lastname, batch FROM student ORDER BY lastname, firstname, middlename ASC");
    while ($row = $result->fetch_assoc()) {
        $studentOptions[$row['batch']][] = $row['lastname'] . ', ' . $row['firstname'] . ' ' . $row['middlename'];
    }

    // Inventory items with unit, grouped by classification
    $CLASSIFICATIONS = [
        'Equipment',
        'Semi Expendable',
        'Consumable',
        'Reagent',
        'Glassware',
        'Food Lab'
    ];

    // Initialize empty array for each classification
    $itemOptions = [];
    foreach ($CLASSIFICATIONS as $class) {
        $itemOptions[$class] = [];
    }

    // Creates a placeholder string for SQL IN clause
    $inClause = "'" . implode("','", $CLASSIFICATIONS) . "'";

    $result = $conn->query("SELECT classification, item, description, unit, laboratory
                            FROM scilab_inventory 
                            WHERE classification IN ($inClause) AND (status IS NULL OR status != 'Removed')
                            ORDER BY classification, item ASC");

    // Populate itemOptions
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $class = $row['classification'];
            $laboratory = $row['laboratory'];

            if (str_contains($laboratory, 'Specialized Equipment Room')) {
                $class = 'Specialized Equipment';
            } elseif ($class === 'Equipment' || $class === 'Semi Expendable') {
                $class = 'Regular Equipment';
            }

            $item = $row['item'];
            $desc = $row['description'];
            $unit = $row['unit'];

            if (!isset($itemOptions[$class][$item])) {
                $itemOptions[$class][$item] = [
                    'descriptions' => [],
                    'unit' => $unit
                ];
            }

            $itemOptions[$class][$item]['descriptions'][] = $desc;
        }
    }

    // CREATE NEW CLASSIFICATIONS LIST WITHOUT CHANGING THE ORIGINAL
    $DISPLAY_CLASSIFICATIONS = ['Regular Equipment', 'Specialized Equipment']; // new classes to be displayed

    foreach ($CLASSIFICATIONS as $class) {
        // skip these classes
        if ($class === 'Equipment' || $class === 'Semi Expendable') {
            continue;
        }
        // avoid duplicates (just in case)
        if (!in_array($class, $DISPLAY_CLASSIFICATIONS)) {
            $DISPLAY_CLASSIFICATIONS[] = $class;
        }
    }
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Request Form</title>
        <?php include('helperFiles/headData.php'); ?>

        <style>
            :root { --main-blue: #2B55C4; }
            .form-title {
                width: 75%; margin: 10px auto; display: flex; justify-content: center; align-items: center; border-radius: 10px;
                background-image: linear-gradient(#0e005475,#0036af75,#0e005475), url(img/laboratoryBackground.jpg);
                background-position: center; background-size: cover; background-repeat: no-repeat; padding: 30px 8%; text-align: center;
            }
            .form-title h4 { color: white; font-size: 2.7rem; font-weight: bold; }
            .container { min-height: 150vh; }
            .form-container {
                min-height: 120vh; background-color: white; padding: 30px; margin: 20px 0 60px;
                border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.07);
            }
            .form-containers h5 { margin-top: 30px; color: var(--main-blue); border-bottom: 2px solid var(--main-blue); padding-bottom: 5px; }
            label { font-weight: bold; color: #333; }
            .form-group input.form-control, .form-group select.form-control { border-radius: 4px; border: 1px solid #ced4da; box-shadow: none; }
            .table th { background-color: var(--main-blue); color: white;  text-align: center; }
            .table td, .table th { vertical-align: middle !important; }
            .btn-primary { background-color: var(--main-blue); border-color: var(--main-blue); }
            .btn-primary:hover, .btn-primary:focus { background-color: #2345a5; border-color: #2345a5; }
            .input-group { margin-bottom: 10px; }
            .input-group-btn .btn { margin-left: 5px; }
            .checkbox-list label, #section-checkboxes label.section-checkbox {
                display: inline-block; margin-right: 15px; margin-top: 5px;
                font-weight: normal; cursor: pointer; user-select: none;
            }
            .image-divider { border-top: 1px solid #dcdcdc; margin: 15px auto; width: 95%; }

            /* Multiselect consistency */
            .form-group .multiselect-native-select .btn-group, 
            .form-group .btn-group { width: 100%; }
            button.multiselect {
                width: 100%; text-align: left; 
                background: linear-gradient(135deg, rgba(43, 85, 196, 0.05), rgba(43, 85, 196, 0.15)) !important;
                backdrop-filter: blur(8px);
                -webkit-backdrop-filter: blur(8px);
                border: 1px solid rgba(43, 85, 196, 0.2) !important;
                color: #2B55C4 !important; 
                display: flex; align-items: center; justify-content: space-between;
                height: 38px; border-radius: 20px !important; box-shadow: 0 4px 12px rgba(43, 85, 196, 0.1); background-image: none;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                font-weight: 600;
            }
            button.multiselect:focus, button.multiselect.active {
                box-shadow: 0 8px 20px rgba(43, 85, 196, 0.2); border-color: rgba(43, 85, 196, 0.4) !important; outline: 0;
            }
            button.multiselect .caret { margin-left: auto; }
            .multiselect-container {
                width: 100%;
                background: rgba(255, 255, 255, 0.95) !important;
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
                border: 1px solid rgba(43, 85, 196, 0.2) !important;
                border-radius: 15px !important;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15) !important;
                margin-top: 5px !important;
                padding: 8px 0;
                max-height: 300px;
                overflow-y: auto;
            }
            .multiselect-container > li { margin-bottom: 2px; }
            .multiselect-container > li > a > label {
                padding: 2px 15px; width: 100%; cursor: pointer; font-weight: normal; margin: 0;
            }
            .multiselect-container > li > a { margin: 0 5px; border-radius: 8px; transition: all 0.2s; }
            .multiselect-container > li > a:hover { background-color: rgba(43, 85, 196, 0.1); color: #2B55C4; }
            .multiselect-container > li.active > a {
                background: linear-gradient(135deg, rgba(43, 85, 196, 0.8), rgba(43, 85, 196, 0.9)) !important;
                color: white !important;
                box-shadow: 0 4px 10px rgba(43, 85, 196, 0.3);
            }
            
            /* Custom Checkbox Style inside Dropdown */
            .multiselect-container input[type="checkbox"] {
                appearance: none;
                -webkit-appearance: none;
                width: 18px;
                height: 18px;
                border: 2px solid #2B55C4;
                border-radius: 5px;
                margin-right: 10px;
                position: relative;
                cursor: pointer;
                vertical-align: middle;
                margin-top: 0;
                background-color: rgba(255, 255, 255, 0.5);
                transition: all 0.2s ease;
            }

            @keyframes pulse-blue {
                0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(43, 85, 196, 0.7); }
                70% { transform: scale(1.15); box-shadow: 0 0 0 4px rgba(43, 85, 196, 0); }
                100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(43, 85, 196, 0); }
            }
            
            .multiselect-container input[type="checkbox"]:checked {
                background-color: #2B55C4;
                border-color: #2B55C4;
                animation: pulse-blue 0.4s ease-out;
            }
            
            .multiselect-container input[type="checkbox"]:checked::after {
                content: '';
                position: absolute;
                left: 5px;
                top: 1px;
                width: 5px;
                height: 10px;
                border: solid white;
                border-width: 0 2px 2px 0;
                transform: rotate(45deg);
            }
            
            /* Ensure label text aligns nicely with custom checkbox */
            .multiselect-container > li > a > label {
                display: flex !important;
                align-items: center;
                padding: 8px 15px !important;
            }
            
            /* Invert colors for active state row */
            .multiselect-container > li.active > a input[type="checkbox"] {
                border-color: rgba(255, 255, 255, 0.8);
            }
            .multiselect-container > li.active > a input[type="checkbox"]:checked {
                background-color: white;
                border-color: white;
            }
            .multiselect-container > li.active > a input[type="checkbox"]:checked::after {
                border-color: #2B55C4;
            }

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
            
            select.liquid-input {
                appearance: none;
                -webkit-appearance: none;
                -moz-appearance: none;
                background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%232B55C4' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e") !important;
                background-repeat: no-repeat !important;
                background-position: right 1rem center !important;
                background-size: 1em !important;
                padding-right: 2.5rem !important;
            }
            
            .is-invalid {
                border-color: #dc3545 !important;
                box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
            }

            @media (max-width: 768px) {
                .form-title { width: 95%; padding: 20px 5%; margin-top: 20px; }
                .form-title h4 { font-size: 1.8rem; }
                .form-container { padding: 15px; margin: 10px 0 30px; }
                .container { padding-left: 10px; padding-right: 10px; }
            }
        </style>
    </head>

    <body>
        <?php include('helperFiles/header.php'); ?>

        <div class="form-title">
            <h4>LABORATORY REQUEST AND EQUIPMENT ACCOUNTABILITY FORM</h4>
        </div>

        <div class="container">
            <div class="form-container">
                <form method="post" action="#">
                    <input type="hidden" name="employee_id" value="<?= htmlspecialchars($employeeID) ?>">
                    <input type="hidden" name="requestor_name" value="<?= htmlspecialchars($username) ?>">

                    <!-- Venue Selection -->
                    <div class="form-group">
                        <label>Facility:</label>
                        <select id="venue_select" class="form-control liquid-input" name="venue" required>
                            <?php foreach ($venues as $name => $label): ?>
                                <option value="<?= htmlspecialchars($name) ?>" <?= $name == $defaultVenue ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Grade and Section -->
                    <div class="form-row row">
                        <div class="form-group col-md-6">
                            <label>Grade Level:</label>
                            <select id="grade_select" name="grade_level" class="form-control liquid-input" required>
                                <option value="">Select Grade</option>
                                <?php ksort($sectionOptions); foreach ($sectionOptions as $grade => $sections): ?>
                                    <option value="<?= $grade ?>"><?= $grade ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Section:</label>
                            <select id="sections-checkboxes" name="sections[]" multiple="multiple" disabled>
                            </select>
                            <div id="section-checkboxes" class="checkbox-list"></div>
                        </div>
                    </div>

                    <!-- Subject and Topic -->
                    <div class="form-row row">
                        <div class="form-group col-md-6">
                            <label>Subject:</label>
                            <select id="subject_select" name="subject" class="form-control liquid-input" required disabled>
                                <option value="">Select Grade First</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Concurrent Topic:</label>
                            <input type="text" class="form-control liquid-input" name="topic" required>
                        </div>
                    </div>

                    <!-- Unit and Teacher -->
                    <div class="form-row row">
                        <div class="form-group col-md-6">
                            <label>Academic Unit:</label>
                            <select class="form-control liquid-input" name="unit" id="unit-select" required disabled>
                                <option value="">Select Grade First</option>
                                <?php $units = array_unique(array_column($subjectOptions, 'subjectAcademicUnit'));
                                foreach ($units as $unit): ?>
                                    <option value="<?= htmlspecialchars($unit) ?>"><?= htmlspecialchars($unit) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Teacher In-Charge:</label>
                            <select id="teacher-checkboxes" class="form-control" name="teacher[]" multiple="multiple" required>
                                <?php foreach ($teacherOptions as $t): ?>
                                    <option value="<?= htmlspecialchars($t['name']) ?>"><?= htmlspecialchars($t['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Schedule -->
                    <div class="form-row row">
                        <div class="form-group col-md-4">
                            <label>Date:</label>
                            <input id="datepicker" type="date" class="form-control liquid-input" name="inclusive_date" required
                                value="<?= htmlspecialchars($_SESSION['selected_date'] ?? '') ?>">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Start Time:</label>
                            <input type="time" class="form-control liquid-input" name="start_time" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label>End Time:</label>
                            <input type="time" class="form-control liquid-input" name="end_time" required>
                        </div>
                    </div>
                        
                    <!-- Inventory Tables -->
                    <div id="materials-container"></div>

                    <br>

                    <!-- Student List -->
                    <h5><strong>Group Members (if applicable):</strong></h5>
                    <div id="student-list-container">
                        <table class="table table-bordered" id="student-list-table">
                            <colgroup>
                                <col style="width: 95%">
                                <col style="width: 5%">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                        <button type="button" class="btn btn-success" id="add-student-btn">
                            <span class="glyphicon glyphicon-plus"></span> Add Row
                        </button>
                    </div>

                    <br>
                    <hr class="image-divider">
                    <div style="width: 100%; text-align: right; margin-top: 15px;">
                        <button type="submit" class="btn-liquid" style="font-weight: bold;">
                            SUBMIT
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php include 'helperFiles/footer.php'; ?>

        <!-- Summary Modal -->
        <div id="summaryModal" class="modal fade" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Request Summary</h4>
                    </div>
                    <div class="modal-body" id="summaryContent"></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="confirmSubmit">Confirm</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Modal -->
        <div class="modal fade" id="progressModal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog modal-sm" role="document" style="margin-top: 30vh;">
                <div class="modal-content">
                    <div class="modal-body text-center">
                        <h4 class="modal-title mb-3">Submitting Request...</h4>
                        <div class="progress">
                            <div id="submissionProgressBar" class="progress-bar progress-bar-striped active" role="progressbar" style="width: 0%">0%</div>
                        </div>
                        <small class="text-muted">Please wait while we process your request.</small>
                    </div>
                </div>
            </div>
        </div>
    </body>

    <!-- JavaScript -->
    <script> 
        const CLASSIFICATIONS = <?= json_encode($DISPLAY_CLASSIFICATIONS) ?>;

        const $container = $('#materials-container');
        CLASSIFICATIONS.forEach(classification => {
            const slug = slugify(classification);

            $container.append(`
                <h5><strong>${classification} Needed:</strong></h5>
                <div id="${slug}-table-container">
                    <table class="table table-bordered material-table">
                        <colgroup>
                            <col style="width:20%">
                            <col style="width:35%">
                            <col style="width:40%">
                            <col style="width:5%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Quantity</th>
                                <th>Item</th>
                                <th>Description</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="${slug}-table-body" data-classification="${classification}"></tbody>
                    </table>
                    <button type="button"
                        class="btn btn-success add-row-btn"
                        data-target="#${slug}-table-body">
                        Add Row
                    </button>
                </div>
                <br>
            `);
        });

        const itemDescriptions = <?= json_encode($itemOptions) ?>; // supports { item: ['desc1','desc2'] } or { item: { descriptions: [...], unit: 'pcs' } }

        // Get items by classification
        function getItemsByClassification(classification) {
            return itemDescriptions[classification] || {};
        }

        // Helper to build options based on classification
        function buildItemOptions(itemsObj) {
            return Object.keys(itemsObj).map(item => {
                const data = itemsObj[item];
                return `<option value="${item}" data-unit="${data.unit || ''}">${item}</option>`;
            }).join('');
        }

        // Helper to get description array for an item key within its classification
        function getDescriptionsForItem(itemKey, classType) {
            if (!itemKey || !classType) return [];
            const itemsObj = itemDescriptions[classType];
            if (!itemsObj || !itemsObj[itemKey]) return [];
            const v = itemsObj[itemKey];
            if (Array.isArray(v)) return v;
            if (v && Array.isArray(v.descriptions)) return v.descriptions;
            return [];
        }

        // Helper to get unit for an item key
        function getUnitForItem(itemKey) {
            if (!itemKey) return '';
            const v = itemDescriptions[itemKey];
            if (!v) return '';
            if (v && typeof v.unit === 'string') return v.unit;
            // if itemDescriptions is an array-only structure, there's no unit
            return '';
        }

        // Delegated handler: when item changes, update descriptions and hidden unit input
        $(document).on('change', '.item-select', function () {
            const $itemSelect = $(this);
            const selectedItem = $itemSelect.val();
            const $row = $itemSelect.closest('tr');
            const $descSelect = $row.find('.description-select');
            const $descInput = $row.find('.description-input');
            const $qty = $row.find('.quantity-input');
            const $unitInput = $row.find('.unit-input');

            // Determine classification based on which table this row is in
            const classType = $itemSelect.closest('tbody').data('classification');

            if (selectedItem) {
                $qty.prop('disabled', false);
                $unitInput.prop('disabled', false);
                if ($descInput.length > 0) $descInput.prop('disabled', false);

                // populate description dropdown (or disable) if it exists
                if ($descSelect.length > 0) {
                    $descSelect.empty().append('<option value="">Select Description</option>');
                    const descs = getDescriptionsForItem(selectedItem, classType);
                    if (selectedItem && descs.length) {
                        descs.forEach(d => $descSelect.append(`<option value="${d}">${d}</option>`));
                        $descSelect.prop('disabled', false);
                    } else {
                        $descSelect.prop('disabled', true).val('');
                    }
                }

                // set the unit input
                const unit = $itemSelect.find('option:selected').data('unit') || '';
                $unitInput.val(unit);
            } else {
                $qty.prop('disabled', true);
                $unitInput.prop('disabled', true).val('');
                if ($descInput.length > 0) $descInput.prop('disabled', true).val('');
                if ($descSelect.length > 0) {
                    $descSelect.prop('disabled', true).empty().append('<option value="">Select Description</option>');
                }
            }

            // ensure quantity has default
            if (!$qty.val() || isNaN($qty.val()) || parseInt($qty.val()) < 1) $qty.val(1);
        });

        // ensure initial rows have default values (quantity=1, description disabled & cleared, unit empty)
        function initRowDefaults($row) {
            const $qty = $row.find('.quantity-input');
            if (!$qty.val()) $qty.val(1);
            $qty.prop('disabled', true);

            const $desc = $row.find('.description-select');
            if ($desc.length > 0) {
                $desc.prop('disabled', true).empty().append('<option value="">Select Description</option>');
            }
            
            const $descInput = $row.find('.description-input');
            if ($descInput.length > 0) $descInput.prop('disabled', true);

            const $unit = $row.find('.unit-input');
            if ($unit.length && !$unit.val()) $unit.val('');
            $unit.prop('disabled', true);
        }

        function createRowHtml(itemsObj, classification) {
            const itemOpts = buildItemOptions(itemsObj);
            
            let descriptionField = '';
            if (classification === 'Reagent') {
                descriptionField = `<input type="text" class="form-control description-input liquid-input" name="description[]" placeholder="Description (Optional)" disabled>`;
            } else {
                descriptionField = `
                    <select class="form-control description-select liquid-input" name="description[]" disabled>
                        <option value="">Select Description</option>
                    </select>`;
            }

            return `
                <tr>
                    <td>
                        <div style="display: flex; align-items: center;">
                            <input type="number" class="form-control quantity-input liquid-input" name="quantity[]" min="1" value="1" style="width: 60%;" disabled>
                            <input type="text" class="form-control unit-input liquid-input" name="unit[]" style="width: 40%; margin-left: 5px;" placeholder="Unit" disabled>
                        </div>
                    </td>
                    <td>
                        <select class="form-control item-select liquid-input" name="item[]">
                            <option value="">Select Item</option>${itemOpts}
                        </select>
                    </td>
                    <td>
                        ${descriptionField}
                    </td>
                    <td style="text-align:center;">
                        <button type="button" class="btn btn-danger remove-row-btn">
                            <span class="glyphicon glyphicon-minus"></span>
                        </button>
                    </td>
                </tr>
            `;
        }

        // function to populate each table
        function populateTableBody($tbody, itemsObj, classification, rowCount = 1) {
            for (let i = 0; i < rowCount; i++) {
                const row = $(createRowHtml(itemsObj, classification));
                $tbody.append(row);
                initRowDefaults(row);
            }
        }

        // function to help table format table label display (removes spaces in between classification names)
        function slugify(name) {
            return name.toLowerCase().replace(/\s+/g, '');
        }

        function defaultRows() {
            // Default 1 row per classification table
            CLASSIFICATIONS.forEach(classification => {
                const slug = slugify(classification);
                const $tbody = $(`#${slug}-table-body`);
                const itemsObj = getItemsByClassification(classification);

                populateTableBody($tbody, itemsObj, classification);
            });

            // Default 1 student row
            const allStudentList = Object.values(allStudents).flat();
            const studentOpts = allStudentList.map(s => `<option value="${s}">${s}</option>`).join('');
            $('#student-list-table tbody').append(`
                <tr>
                    <td>
                        <select class="form-control student-select liquid-input" name="students[]">
                            <option value="">Select Student</option>
                            ${studentOpts}
                        </select>
                    </td>
                    <td style="text-align:center;">
                        <button type="button" class="btn btn-danger remove-student-btn">
                            <span class="glyphicon glyphicon-minus"></span>
                        </button>
                    </td>
                </tr>
            `);
        }

        function resetForm() {
            const form = $('form')[0];
            form.reset();

            // Reset sections multiselect
            $('#sections-checkboxes').empty().multiselect('rebuild');

            // Clear material tables dynamically
            CLASSIFICATIONS.forEach(classification => {
                const slug = slugify(classification);
                $(`#${slug}-table-body`).empty();
            });

            // Clear student list table
            $('#student-list-table tbody').empty();
        }

        $(function() {
            // initialize existing rows
            defaultRows()
            $('tbody[data-classification]').each(function () {
                $(this).find('tr').each(function () {
                    initRowDefaults($(this));
                });
            });

            $('.add-row-btn').click(function() {
                const $tbody = $($(this).data('target'));
                const classification = $tbody.data('classification');
                const itemsObj = getItemsByClassification(classification);

                const row = $(createRowHtml(itemsObj, classification));
                $tbody.append(row);
                initRowDefaults(row);
            });

            // quantity sanity
            $(document).on('input', '.quantity-input', function() {
                if (parseInt(this.value) < 1 || isNaN(this.value)) this.value = 1;
            });

            // Automatically fill unit when item is selected
            $(document).on('change', '.item-select', function() {
                // Logic handled in the main item-select change handler
            });

            // Form submit with summary modal
            $('form').submit(function(e) {
                e.preventDefault();

                // Reset validation visual cues
                $('.is-invalid').removeClass('is-invalid');
                let isValid = true;

                // Check that section field is filled
                const selectedSections = $('#sections-checkboxes').val();
                if (!selectedSections || selectedSections.length === 0) {
                    showToast("Please select at least one section.", 'warning');
                    $('#sections-checkboxes').next('.btn-group').find('.multiselect').addClass('is-invalid');
                    isValid = false;
                }

                // Check that teacher field is filled
                const selectedTeachers = $('#teacher-checkboxes').val();
                if (!selectedTeachers || selectedTeachers.length === 0) {
                    showToast("Please select at least one teacher.", 'warning');
                    $('#teacher-checkboxes').next('.btn-group').find('.multiselect').addClass('is-invalid');
                    isValid = false;
                }

                if (!isValid) return false;

                const form = $(this);
                const sectionsArr = $('#sections-checkboxes').val() || [];

                let summary = `
                    <strong>Facility:</strong> ${form.find('[name="venue"]').val()}<br>
                    <strong>Grade Level:</strong> ${form.find('[name="grade_level"]').val()}<br>
                    <strong>Sections:</strong> ${sectionsArr.join(", ")}<br>
                    <strong>Subject:</strong> ${form.find('[name="subject"]').val()}<br>
                    <strong>Topic:</strong> ${form.find('[name="topic"]').val()}<br>
                    <strong>Unit:</strong> ${form.find('[name="unit"]').val()}<br>
                    <strong>Teacher:</strong> ${(form.find('[name="teacher[]"]').val() || []).join(', ')}<br>
                    <strong>Date:</strong> ${form.find('[name="inclusive_date"]').val()}<br>
                    <strong>Time:</strong> ${form.find('[name="start_time"]').val()} to ${form.find('[name="end_time"]').val()}<br>
                    <hr>
                `;

                // Build categorized materials summary with merging of duplicates
                const categories = CLASSIFICATIONS.map(c => {
                    const slug = slugify(c);
                    return {
                        name: c,
                        selector: `#${slug}-table-body`
                    };
                });

                categories.forEach((cat, index) => {
                    let catItems = {};

                    $(cat.selector + ' tr').each(function() {
                        const $r = $(this);
                        const itemVal = $r.find('select[name="item[]"]').val();
                        const itemLabel = $r.find('select[name="item[]"] option:selected').text();
                        const desc = $r.find('select[name="description[]"], input[name="description[]"]').val() || 'N/A';
                        const qty = parseInt($r.find('input[name="quantity[]"]').val()) || 1;
                        const unit = $r.find('.unit-input').val() || '';

                        if (!itemVal) return;

                        const key = `${itemVal}__${desc}`;
                        if (!catItems[key]) {
                            catItems[key] = { qty: 0, unit, itemLabel, desc };
                        }
                        catItems[key].qty += qty;
                    });

                    // Always display category header
                    summary += `<strong>${cat.name}:</strong><br>`;

                    // Display items or "No items selected"
                    if (Object.keys(catItems).length > 0) {
                        for (const key in catItems) {
                            let { qty, unit, itemLabel, desc } = catItems[key];

                            // Remove any "(unit)" in itemLabel
                            if (unit) {
                                const regex = new RegExp(`\\(${unit}\\)`, 'gi');
                                itemLabel = itemLabel.replace(regex, '').trim();
                            }

                            const unitPart = unit ? ` ${unit}` : '';
                            summary += `• ${qty}${unitPart} of ${itemLabel} (${desc})<br>`;
                        }
                    } else {
                        summary += `No items selected<br>`;
                    }

                    // // Add <br> only if not the last category
                    if (index < categories.length - 1){
                        summary += `<br>`;
                    }
                });

                // Students
                summary += `<hr><strong>Students:</strong><br>`;
                let hasStudents = false;
                $('.student-select').each((i, el) => {
                    const val = $(el).val();
                    if (val) {
                        hasStudents = true;
                        summary += `• ${val}<br>`;
                    }
                });

                if (!hasStudents) summary += `No students selected<br>`;

                $('#summaryContent').html(summary);
                $('#summaryModal').modal('show');
            });

            // Confirm submit
            $('#confirmSubmit').click(function() {
                $('#summaryModal').modal('hide');
                
                // Show progress modal
                $('#progressModal').modal('show');
                let progress = 0;
                const $bar = $('#submissionProgressBar');
                $bar.css('width', '0%').text('0%');
                
                const progressInterval = setInterval(() => {
                    progress += Math.random() * 10;
                    if (progress > 90) progress = 90;
                    $bar.css('width', progress + '%').text(Math.round(progress) + '%');
                }, 200);

                // Collect and merge duplicate materials
                const itemsMap = {};
                const bodySelectors = CLASSIFICATIONS
                    .map(c => `#${slugify(c)}-table-body tr`)
                    .join(', ');

                $(bodySelectors).each(function() {
                    const $r = $(this);
                    const itemVal = $r.find('select[name="item[]"]').val();
                    const itemLabel = $r.find('select[name="item[]"] option:selected').text();
                    const desc = $r.find('select[name="description[]"], input[name="description[]"]').val() || 'N/A';
                    const qty = parseInt($r.find('input[name="quantity[]"]').val()) || 1;
                    const unit = $r.find('.unit-input').val() || '';

                    if (!itemVal) return;

                    const key = `${itemVal}__${desc}`;
                    if (!itemsMap[key]) {
                        itemsMap[key] = { item: itemVal, itemLabel, desc, qty: 0, unit };
                    }
                    itemsMap[key].qty += qty;
                });

                // Create FormData for AJAX
                const formData = new FormData($('form')[0]);
                formData.append('action', 'request_submission');
                formData.append('mergedMaterials', JSON.stringify(Object.values(itemsMap)));

                // Handle teacher array -> string conversion for backend compatibility
                const teachers = $('#teacher-checkboxes').val();
                formData.delete('teacher[]');
                formData.append('teacher', teachers ? teachers.join(', ') : '');

                $.ajax({
                    url: 'ajax/ajax_forms.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        clearInterval(progressInterval);
                        $bar.css('width', '100%').text('100%');
                        
                        setTimeout(() => {
                            $('#progressModal').modal('hide');
                            if (res.trim() === "success") {
                                showToast("Request submitted successfully!", 'success');
                                resetForm();
                                location.reload();
                            } else if (res === "conflict") {
                                showToast("Schedule conflict. Please choose another time.", 'error');
                            } else if (res === "invalid_scilab") {
                                showToast("Invalid laboratory selected.", 'error');
                            } else if (res === "session_error") {
                                showToast("Session expired. Please log in again.", 'error');
                            } else {
                                showToast("Error: " + res, 'error');
                            }
                        }, 500);
                    },
                    error: function(xhr, status, error) {
                        clearInterval(progressInterval);
                        $('#progressModal').modal('hide');
                        showToast("Error submitting request.", 'error');
                        console.log("Error submitting request.");
                        console.error("AJAX error:", status, error);
                    }
                });
            });

            setMinDate();
            populateStudentDropdowns();
            $('#sections-checkboxes').multiselect({ 
                includeSelectAllOption: true,
                nonSelectedText: 'Select Grade First',
                onChange: function() {
                    $('#sections-checkboxes').next('.btn-group').find('.multiselect').removeClass('is-invalid');
                }
            });
            $('#teacher-checkboxes').multiselect({
                includeSelectAllOption: false,
                nonSelectedText: 'Select Teacher',
                onChange: function() {
                    $('#teacher-checkboxes').next('.btn-group').find('.multiselect').removeClass('is-invalid');
                }
            });

            // time validation
            $('input[name="start_time"], input[name="end_time"]').on('change', function() {
                const start = $('input[name="start_time"]').val();
                const end = $('input[name="end_time"]').val();
                
                $('input[name="end_time"]').removeClass('is-invalid');
                
                if (start && end && start >= end) {
                    showToast("End time must be later than start time.", 'warning');
                    $('input[name="end_time"]').val('');
                    $('input[name="end_time"]').addClass('is-invalid');
                }
            });
            
            $('input[name="start_time"], input[name="end_time"]').on('input', function() {
                $(this).removeClass('is-invalid');
            });

            // grade -> sections & subjects
            $('#grade_select').on('change', function() {
                const grade = $(this).val();
                populateStudentDropdownsByGrade(grade);

                if (!grade) {
                    $('#sections-checkboxes').empty().prop('disabled', true);
                    $('#sections-checkboxes').multiselect('destroy').multiselect({ 
                        includeSelectAllOption: true, 
                        nonSelectedText: 'Select Grade First',
                        onChange: function() {
                            $('#sections-checkboxes').next('.btn-group').find('.multiselect').removeClass('is-invalid');
                        }
                    });
                    
                    $('#subject_select').prop('disabled', true).html('<option value="">Select Grade First</option>');
                    $('#unit-select').prop('disabled', true).html('<option value="">Select Grade First</option>');
                    return;
                }

                // Sections checkboxes
                $.get('ajax/ajax_forms.php', { action:'get_sections', grade }, data => {
                    $('#sections-checkboxes').html(data).prop('disabled', false);
                    $('#sections-checkboxes').multiselect('destroy').multiselect({
                        includeSelectAllOption: true,
                        nonSelectedText: 'Select Section',
                        onChange: function() {
                            $('#sections-checkboxes').next('.btn-group').find('.multiselect').removeClass('is-invalid');
                        }
                    });
                }).fail(() => $('#sections-checkboxes').html('Error loading sections').multiselect('rebuild'));

                // Subjects & Units
                $.post('ajax/ajax_forms.php', { action:'get_subjects_by_grade', grade }, function(subjects) {
                    let subjOpts = '<option value="">Select Subject</option>';
                    let seenUnits = new Set();
                    let unitOpts = '<option value="">Select Unit</option>';
                    subjects.forEach(s => {
                        subjOpts += `<option value="${s.description}">${s.description}</option>`;
                        if (!seenUnits.has(s.unit)) {
                            seenUnits.add(s.unit);
                            unitOpts += `<option value="${s.unit}">${s.unit}</option>`;
                        }
                    });
                    $('#subject_select').html(subjOpts).prop('disabled', false);
                    $('#unit-select').html(unitOpts).prop('disabled', false);
                }, 'json').fail(() => $('#subject_select, #unit-select').html('<option value="">Error loading</option>'));
            });

            // add student row
            $('#add-student-btn').click(function() {
                const grade = $('#grade_select').val();
                if (!grade) return showToast("Please select a grade level first.", 'warning');
                populateStudentDropdownsByGrade(grade);
                const row = $(`
                    <tr>
                        <td><select class="form-control student-select liquid-input" name="students[]"><option value="">Select Student</option></select></td>
                        <td style="text-align:center;"><button type="button" class="btn btn-danger remove-student-btn"><span class="glyphicon glyphicon-minus"></span></button></td>
                    </tr>
                `);
                $('#student-list-table tbody').append(row);
            });

            // Scilab disabled dates
            let currentDisabledDates = [];
            const today = new Date().toISOString().split('T')[0];
            const datepicker = document.getElementById('datepicker');
            const sessionDate = "<?php echo $_SESSION['selected_date'] ?? ''; ?>";

            function dateInputHandler() {
                if(currentDisabledDates.includes(this.value)) { showToast('This date is unavailable for the selected venue.', 'warning'); this.value=''; }
            }

            document.addEventListener('DOMContentLoaded', function() {
                datepicker.setAttribute('min', today);
                datepicker.value = sessionDate && sessionDate >= today ? sessionDate : '';
                datepicker.addEventListener('input', dateInputHandler);
                loadDisabledDates($('#venue_select').val());
            });

            $('#venue_select').on('change', function() { datepicker.value=''; loadDisabledDates($(this).val()); });

            function loadDisabledDates(scilabName) {
                $.post('ajax/ajax_forms.php', { action:'get_disabled_dates', scilabName }, function(dates) { currentDisabledDates = dates; }, 'json')
                .fail((xhr,status,error) => showToast('Failed to load disabled dates: '+error, 'error'));
            }
        });

        const allStudents = <?= json_encode($studentOptions) ?>;
        const sections = <?= json_encode($sectionOptions) ?>;
        
        function populateStudentDropdowns(grade) {
            const students = allStudents[grade] || [];
            $('.student-select').each(function () {
                const select = $(this);
                const current = select.val();
                select.empty().append('<option value="">Select Student</option>');
                students.forEach(s => select.append(`<option value="${s}" ${s === current ? 'selected' : ''}>${s}</option>`));
            });
        }

        function populateStudentDropdownsByGrade(grade) {
            $.post('ajax/ajax_forms.php', { action: 'get_students_by_grade', grade }, function(students) {
                const options = students.length ? students.map(s => `<option value="${s.name}">${s.name}</option>`).join('') : '';
                $('.student-select').each(function() {
                    const current = $(this).val();
                    $(this).html('<option value="">Select Student</option>' + options).val(current);
                });
            }, 'json').fail(() => $('.student-select').html('<option value="">Error loading students</option>'));
        }

        // document-level handlers for remove rows
        $(document).on('click', '.remove-row-btn', function () {
            $(this).closest('tr').remove();
        });
        $(document).on('click', '.remove-student-btn', function () {
            $(this).closest('tr').remove();
        });

        function setMinDate() {
            const today = new Date().toISOString().split('T')[0];
            $('input[type="date"]').attr('min', today);
        }
    </script>
</html>