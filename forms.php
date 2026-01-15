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
    $itemOptions = [
        'Equipment' => [],
        'Consumable' => [],
        'Reagent' => []
    ];

    $result = $conn->query("SELECT classification, item, description, unit 
                            FROM scilab_inventory 
                            WHERE classification IN ('Equipment', 'Consumable', 'Reagent')
                            AND (status IS NULL OR status != 'Removed')
                            ORDER BY classification, item ASC");

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $class = $row['classification'];
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
                width: 100%; text-align: left; background-color: #fff; border: 1px solid #ced4da;
                color: #495057; display: flex; align-items: center; justify-content: space-between;
                height: 38px; border-radius: 4px; box-shadow: none; background-image: none;
            }
            button.multiselect:focus, button.multiselect.active {
                box-shadow: none; border-color: #80bdff; outline: 0; background-color: #fff;
            }
            button.multiselect .caret { margin-left: auto; }
            .multiselect-container { width: 100%; border: 1px solid #ced4da; border-radius: 4px; box-shadow: none; margin-top: 2px; padding: 5px 0; }
            .multiselect-container > li { margin-bottom: 2px; }
            .multiselect-container > li > a > label {
                padding: 10px 15px; width: 100%; cursor: pointer; font-weight: normal; margin: 0;
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
                        <select id="venue_select" class="form-control" name="venue" required>
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
                            <select id="grade_select" name="grade_level" class="form-control" required>
                                <option value="">Select Grade</option>
                                <?php ksort($sectionOptions); foreach ($sectionOptions as $grade => $sections): ?>
                                    <option value="<?= $grade ?>"><?= $grade ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Section:</label>
                            <select id="sections-checkboxes" name="sections[]" multiple="multiple">
                                <option value="php">Select Grade Level First</option>
                            </select>
                            <div id="section-checkboxes" class="checkbox-list"></div>
                        </div>
                    </div>

                    <!-- Subject and Topic -->
                    <div class="form-row row">
                        <div class="form-group col-md-6">
                            <label>Subject:</label>
                            <select id="subject_select" name="subject" class="form-control" required>
                                <option value="">Select Grade First</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Concurrent Topic:</label>
                            <input type="text" class="form-control" name="topic" required>
                        </div>
                    </div>

                    <!-- Unit and Teacher -->
                    <div class="form-row row">
                        <div class="form-group col-md-6">
                            <label>Academic Unit:</label>
                            <select class="form-control" name="unit" id="unit-select" required>
                                <option value="">Select Academic Unit</option>
                                <?php $units = array_unique(array_column($subjectOptions, 'subjectAcademicUnit'));
                                foreach ($units as $unit): ?>
                                    <option value="<?= htmlspecialchars($unit) ?>"><?= htmlspecialchars($unit) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Teacher In-Charge:</label>
                            <select class="form-control" name="teacher" required>
                                <option value="">Select Teacher</option>
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
                            <input id="datepicker" type="date" class="form-control" name="inclusive_date" required
                                value="<?= htmlspecialchars($_SESSION['selected_date'] ?? '') ?>">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Start Time:</label>
                            <input type="time" class="form-control" name="start_time" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label>End Time:</label>
                            <input type="time" class="form-control" name="end_time" required>
                        </div>
                    </div>
                             
                    <!-- Equipment Table -->
                    <h5><strong>Equipment Needed:</strong></h5>
                    <div id="equipment-table-container">
                        <table class="table table-bordered material-table" id="equipment-table">
                            <colgroup>
                                <col style="width: 20%;">
                                <col style="width: 35%;">
                                <col style="width: 40%;">
                                <col style="width: 5%;">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Quantity</th>
                                    <th>Item</th>
                                    <th>Description</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="equipment-table-body"></tbody>
                        </table>
                        <button type="button" class="btn btn-success add-row-btn" data-target="#equipment-table-body">
                            <span class="glyphicon glyphicon-plus"></span> Add Row
                        </button>
                    </div>

                    <br>
                    
                    <!-- Consumables Table -->
                    <h5><strong>Consumables Needed:</strong></h5>
                    <div id="consumables-table-container">
                        <table class="table table-bordered material-table" id="consumables-table">
                            <colgroup>
                                <col style="width: 20%;">
                                <col style="width: 35%;">
                                <col style="width: 40%;">
                                <col style="width: 5%;">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Quantity</th>
                                    <th>Item</th>
                                    <th>Description</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="consumables-table-body"></tbody>
                        </table>
                        <button type="button" class="btn btn-success add-row-btn" data-target="#consumables-table-body">
                            <span class="glyphicon glyphicon-plus"></span> Add Row
                        </button>
                    </div>

                    <br>

                    <!-- Reagents Table -->
                    <h5><strong>Reagents Needed:</strong></h5>
                    <div id="reagents-table-container">
                        <table class="table table-bordered material-table" id="reagents-table">
                            <colgroup>
                                <col style="width: 20%;">
                                <col style="width: 35%;">
                                <col style="width: 40%;">
                                <col style="width: 5%;">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Quantity</th>
                                    <th>Item</th>
                                    <th>Description</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="reagents-table-body"></tbody>
                        </table>
                        <button type="button" class="btn btn-success add-row-btn" data-target="#reagents-table-body">
                            <span class="glyphicon glyphicon-plus"></span> Add Row
                        </button>
                    </div>

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
                        <button type="submit" class="btn btn-primary" style="background-color: #0036af; font-weight: bold; color: white;">
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
    </body>

    <!-- JavaScript -->
    <script>
        const itemDescriptions = <?= json_encode($itemOptions) ?>; // supports { item: ['desc1','desc2'] } or { item: { descriptions: [...], unit: 'pcs' } }

        // Split items by classification
        const equipmentItems = itemDescriptions['Equipment'] || {};
        const consumableItems = itemDescriptions['Consumable'] || {};
        const reagentItems = itemDescriptions['Reagent'] || {};

        // Helper to build options based on classification
        function buildItemOptions(itemsObj) {
            return Object.keys(itemsObj).map(item => {
                const data = itemsObj[item];
                const unit = data.unit ? ` (${data.unit})` : '';
                return `<option value="${item}" data-unit="${data.unit || ''}">${item}${unit}</option>`;
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
            let classType = '';
            const $tbody = $itemSelect.closest('tbody');
            if ($tbody.is('#equipment-table-body')) classType = 'Equipment';
            else if ($tbody.is('#consumables-table-body')) classType = 'Consumable';
            else if ($tbody.is('#reagents-table-body')) classType = 'Reagent';

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

        function createRowHtml(itemsObj, isReagent) {
            const itemOpts = buildItemOptions(itemsObj);
            
            let descriptionField = '';
            if (isReagent) {
                descriptionField = `<input type="text" class="form-control description-input" name="description[]" placeholder="Description (Optional)" disabled>`;
            } else {
                descriptionField = `
                    <select class="form-control description-select" name="description[]" disabled>
                        <option value="">Select Description</option>
                    </select>`;
            }

            return `
                <tr>
                    <td>
                        <div style="display: flex; align-items: center;">
                            <input type="number" class="form-control quantity-input" name="quantity[]" min="1" value="1" style="width: 60%;" disabled>
                            <input type="text" class="form-control unit-input" name="unit[]" style="width: 40%; margin-left: 5px;" placeholder="Unit" disabled>
                        </div>
                    </td>
                    <td>
                        <select class="form-control item-select" name="item[]">
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
        function populateTableBody($tbody, itemsObj, rowCount = 3) {
            const isReagent = $tbody.is('#reagents-table-body');
            for (let i = 0; i < rowCount; i++) {
                const row = $(createRowHtml(itemsObj, isReagent));
                $tbody.append(row);
                initRowDefaults(row);
            }
        }

        function defaultRows() {
            // Default 5 rows per table
            populateTableBody($('#equipment-table-body'), equipmentItems);
            populateTableBody($('#consumables-table-body'), consumableItems);
            populateTableBody($('#reagents-table-body'), reagentItems);

            // default 1 student row
            const allStudentList = Object.values(allStudents).flat();
            const studentOpts = allStudentList.map(s => `<option value="${s}">${s}</option>`).join('');
            $('#student-list-table tbody').append(`
                <tr>
                    <td><select class="form-control student-select" name="students[]"><option value="">Select Student</option>${studentOpts}</select></td>
                    <td style="text-align:center;"><button type="button" class="btn btn-danger remove-student-btn"><span class="glyphicon glyphicon-minus"></span></button></td>
                </tr>
            `);
        }

        function resetForm() {
            const form = $('form')[0];
            form.reset();
            $('#sections-checkboxes').empty().multiselect('rebuild');
            $('#equipment-table tbody, #consumables-table tbody, #reagents-table tbody, #student-list-table tbody').empty();

            defaultRows()
        }

        $(function() {
            // initialize existing rows
            defaultRows()
            $('#equipment-table tbody tr').each(function() { initRowDefaults($(this)); });

            $('.add-row-btn').click(function() {
                const targetBody = $($(this).data('target'));
                let itemsObj = {};

                if (targetBody.is('#equipment-table-body')) itemsObj = equipmentItems;
                else if (targetBody.is('#consumables-table-body')) itemsObj = consumableItems;
                else if (targetBody.is('#reagents-table-body')) itemsObj = reagentItems;

                const isReagent = targetBody.is('#reagents-table-body');
                const row = $(createRowHtml(itemsObj, isReagent));
                targetBody.append(row);
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

                // Check that section field is filled
                const selectedSections = $('#sections-checkboxes').val();
                if (!selectedSections || selectedSections.length === 0) {
                    alert("Please select at least one section.");
                    $('#sections-checkboxes').focus();
                    return false;
                }

                const form = $(this);
                const sectionsArr = $('#sections-checkboxes').val() || [];

                let summary = `
                    <strong>Facility:</strong> ${form.find('[name="venue"]').val()}<br>
                    <strong>Grade Level:</strong> ${form.find('[name="grade_level"]').val()}<br>
                    <strong>Sections:</strong> ${sectionsArr.join(", ")}<br>
                    <strong>Subject:</strong> ${form.find('[name="subject"]').val()}<br>
                    <strong>Topic:</strong> ${form.find('[name="topic"]').val()}<br>
                    <strong>Unit:</strong> ${form.find('[name="unit"]').val()}<br>
                    <strong>Teacher:</strong> ${form.find('[name="teacher"]').val()}<br>
                    <strong>Date:</strong> ${form.find('[name="inclusive_date"]').val()}<br>
                    <strong>Time:</strong> ${form.find('[name="start_time"]').val()} to ${form.find('[name="end_time"]').val()}<br>
                    <hr>
                `;

                // Build categorized materials summary with merging of duplicates
                const categories = [
                    { name: 'Equipment', selector: '#equipment-table-body' },
                    { name: 'Consumables', selector: '#consumables-table-body' },
                    { name: 'Reagents', selector: '#reagents-table-body' }
                ];

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
                            const { qty, unit, itemLabel, desc } = catItems[key];
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

                // Collect and merge duplicate materials
                const itemsMap = {};
                $('#equipment-table-body tr, #consumables-table-body tr, #reagents-table-body tr').each(function() {
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

                $.ajax({
                    url: 'ajax/ajax_forms.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        if (res.trim() === "success") {
                            alert("Request submitted successfully!");
                            resetForm();
                        } else {
                            alert("Submission failed: " + res);
                        }
                    },
                    error: function() {
                        alert("There was an error submitting the form.");
                    }
                });
            });

            setMinDate();
            populateStudentDropdowns();
            $('#sections-checkboxes').multiselect({ includeSelectAllOption: true });

            // time validation
            $('input[name="start_time"], input[name="end_time"]').on('change', function() {
                const start = $('input[name="start_time"]').val();
                const end = $('input[name="end_time"]').val();
                if (start && end && start >= end) {
                    alert("End time must be later than start time.");
                    $('input[name="end_time"]').val('');
                }
            });

            // grade -> sections & subjects
            $('#grade_select').on('change', function() {
                const grade = $(this).val();
                populateStudentDropdownsByGrade(grade);

                // Sections checkboxes
                $.get('ajax/ajax_forms.php', { action:'get_sections', grade }, data => {
                    $('#sections-checkboxes').html(data).multiselect('rebuild');
                    ensurePlaceholderIfEmpty('#sections-checkboxes');
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
                    $('#subject_select').html(subjOpts);
                    $('#unit-select').html(unitOpts);
                }, 'json').fail(() => $('#subject_select, #unit-select').html('<option value="">Error loading</option>'));
            });

            // add student row
            $('#add-student-btn').click(function() {
                const grade = $('#grade_select').val();
                if (!grade) return alert("Please select a grade level first.");
                populateStudentDropdownsByGrade(grade);
                const row = $(`
                    <tr>
                        <td><select class="form-control student-select" name="students[]"><option value="">Select Student</option></select></td>
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
                if(currentDisabledDates.includes(this.value)) { alert('This date is unavailable for the selected venue.'); this.value=''; }
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
                .fail((xhr,status,error) => alert('Failed to load disabled dates: '+error));
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

        function ensurePlaceholderIfEmpty(selector) {
            const $select = $(selector);
            if (!$select.find('option').length) {
                $select.append('<option disabled selected>Select Grade Level First</option>');
                if ($select.data('multiselect')) $select.multiselect('rebuild');
            }
        }
    </script>
</html>