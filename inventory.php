<?php
    include('helperFiles/db_connection.php');
    include('helperFiles/session_handler.php');

    $email = $_SESSION['email'];
    $username = $_SESSION['username'];

    if (isset($_SESSION['role']) && $_SESSION['role'] != 'admin') {
        header("Location: requester_home.php");
        exit();
    }

    if (!isset($_SESSION['role'])) {
        header("Location: index.php");
        exit();
    }

    // Fetch only items not marked as Removed
    $CLASSIFICATIONS = [
        'Equipment',
        'Semi Expendable',
        'Consumable',
        'Reagent',
        'Glassware',
        'Food Lab'
    ];

    // Creates a placeholder string for SQL IN clause
    $inClause = "'" . implode("','", $CLASSIFICATIONS) . "'";

    $inventoryQuery = $conn->query("SELECT id, classification, item, productID, description, quantity, unit, status 
                                    FROM scilab_inventory 
                                    WHERE classification IN ($inClause) AND (status IS NULL OR status != 'Removed')
                                    ORDER BY classification, item");

    // Initialize empty array for each classification
    $inventory = [];
    foreach ($CLASSIFICATIONS as $class) {
        $inventory[$class] = [];
    }

    // Populate inventory dynamically
    if ($inventoryQuery && $inventoryQuery->num_rows > 0) {
        while ($row = $inventoryQuery->fetch_assoc()) {
            $class = $row['classification'];
            if (isset($inventory[$class])) {
                $inventory[$class][] = $row;
            }
        }
    }
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Inventory Management</title>
        <?php include('helperFiles/headData.php'); ?>

        <style>
            body { background-color: #f5f5f5; }
            .form-container { background-color: #fff; padding: 25px; margin: 25px auto; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); width: 98%; }
            .inventory-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
            .tab { background-color: #e0e0e0; color: #333; padding: 8px 16px; border-radius: 5px; cursor: pointer; }
            .tab.active { background-color: #2B55C4; color: white; }
            table.inventory-table { width: 100%; border-collapse: collapse; }
            table.inventory-table th { background-color: #2B55C4; color: #fff; padding: 10px; text-align: left; font-size: 13px; }
            table.inventory-table td { padding: 8px; border-bottom: 1px solid #eee; font-size: 13px; }
            table.inventory-table tbody tr:hover { background-color: #fafafa; }
            .action-btn { padding: 6px 10px; border-radius: 4px; border: none; cursor: pointer; font-size: 13px; }
            .btn-edit { background-color: #2B55C4; color: white; }
            .btn-remove { background-color: #e74c3c; color: white; }
            .small-btn { padding: 6px 12px; font-size: 14px; border-radius: 4px; }
            #inventory-table th:last-child, #inventory-table td:last-child { min-width: 67px; }
            .no-items { text-align: center; padding: 12px; background: #fff8e6; border: 1px solid #ffe0a3; border-radius: 6px; color: #7a5a00; }

            /* Scanner modal compact sizing */
            #scanner-container {
                width: 100%;
                display: flex;
                justify-content: center;
                align-items: center;
                background: #000;
                border-radius: 8px;
                overflow: hidden;
                max-height: 300px;

                /* IMPORTANT: make this a positioned containing block so Quagga's
                   absolutely positioned video/canvas stay inside the modal */
                position: relative;

                /* ENSURE the container has a real layout box so Quagga attaches the
                   video element into this element instead of the document body */
                min-height: 240px;
                box-sizing: border-box;
            }

            /* Ensure Quagga's generated video/canvas fill the container and stay anchored */
            #scanner-container video,
            #scanner-container canvas {
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
                height: 100% !important;
                object-fit: cover !important;
            }

            /* Make the modal more compact vertically */
            #barcodeScannerModal .modal-dialog {
                max-width: 500px;
            }

            #barcodeScannerModal .modal-body {
                padding: 10px 15px;
            }

            @media (max-width: 768px) {
                .form-container { padding: 10px; margin: 10px auto; }
                .inventory-header { flex-direction: column; align-items: flex-start; gap: 10px; }
                .inventory-header > div { width: 100%; display: flex; flex-wrap: wrap; gap: 5px; }
                .btn-scan, .btn-add { flex: 1; text-align: center; }
            }

            .modified-row { background-color: #fff8e1 !important; }
        </style>
    </head>

    <body>
        <?php include('helperFiles/header.php'); ?>

        <div class="main-wrapper">
            <div class="form-container">
                <div class="inventory-header">
                    <h2>Inventory Management</h2>
                    <div>
                        <button id="scanBarcodeBtn" class="btn-liquid">Scan Barcode</button>
                        <button id="bulkImportBtn" class="btn-liquid-info">Bulk Import</button>
                        <button id="editModeBtn" class="btn-liquid-warning">Edit Mode</button>
                        <button id="cancelEditBtn" class="btn-liquid-danger" style="display:none;">Cancel</button>
                        <button id="addItemBtn" class="btn-liquid-success">Add Product</button>
                    </div>
                </div>
                <div class="tabs" id="tabsContainer"></div>

                <table id="inventory-table" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Product ID</th>
                            <th>Description</th>
                            <th>Quantity</th>
                            <th>Unit</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="inventory-body"></tbody>
                </table>
            </div>
        </div>

        <!-- Add Product Modal -->
        <div class="modal fade" id="addProductModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Product</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <form id="addProductForm">
                            <div class="form-group">
                                <label>Classification</label>
                                <select class="form-control" name="classification" required></select>
                            </div>
                            <div class="form-group">
                                <label>Product Name</label>
                                <input type="text" class="form-control" name="item" required>
                            </div>
                            <div class="form-group">
                                <label>Product ID</label>
                                <input type="text" class="form-control" name="productID" required>
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <input type="text" class="form-control" name="description" required>
                            </div>
                            <div class="form-group">
                                <label>Quantity</label>
                                <input type="number" class="form-control" name="quantity" min="1" required>
                            </div>
                            <div class="form-group">
                                <label>Quantity Unit</label>
                                <select class="form-control" name="unit" required>
                                    <option value="">Select Unit</option>
                                    <option value="pieces">pieces</option>
                                    <option value="mL">mL</option>
                                    <option value="grams">grams</option>
                                    <option value="boxes">boxes</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select class="form-control" name="status" required>
                                    <option value="">Select Status</option>
                                    <option value="Available">Available</option>
                                    <option value="Out of Stock">Out of Stock</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success" id="saveProductBtn">Add</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Product Modal -->
        <div class="modal fade" id="editProductModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Product</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <form id="editProductForm">
                            <input type="hidden" name="id">
                            <div class="form-group">
                                <label>Classification</label>
                                <select class="form-control" name="classification" required></select>
                            </div>
                            <div class="form-group">
                                <label>Product Name</label>
                                <input type="text" class="form-control" name="item" required>
                            </div>
                            <div class="form-group">
                                <label>Product ID</label>
                                <input type="text" class="form-control" name="productID" required>
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <input type="text" class="form-control" name="description" required>
                            </div>
                            <div class="form-group">
                                <label>Quantity</label>
                                <input type="number" class="form-control" name="quantity" min="1" required>
                            </div>
                            <div class="form-group">
                                <label>Quantity Unit</label>
                                <select class="form-control" name="unit" required>
                                    <option value="pieces">pieces</option>
                                    <option value="mL">mL</option>
                                    <option value="grams">grams</option>
                                    <option value="boxes">boxes</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select class="form-control" name="status" required>
                                    <option value="Available">Available</option>
                                    <option value="Out of Stock">Out of Stock</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success" id="saveEditBtn">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Barcode Scanner Modal (compact vertically) -->
        <div class="modal fade" id="barcodeScannerModal" tabindex="-1" role="dialog" aria-labelledby="barcodeScannerModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                <div class="modal-content" style="border-radius: 10px;">
                    <div class="modal-header bg-success text-white py-2">
                        <h5 class="modal-title" id="barcodeScannerModalLabel">📷 Scan Product Barcode</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body text-center p-2">
                        <div id="scanner-container" aria-hidden="true">
                            <!-- Quagga will attach the camera stream here -->
                            <div style="color: #fff; font-size: 14px;">Starting camera…</div>
                        </div>
                        <p style="margin-top:8px;"><strong>Detected Code:</strong> <span id="result">None</span></p>
                    </div>
                    <div class="modal-footer p-2 justify-content-center">
                        <button id="stopScannerBtn" class="btn btn-danger btn-sm">Stop Scanner</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Import Modal -->
        <div class="modal fade" id="bulkImportModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Bulk Import / Update</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <p>Upload a CSV file with headers: <code>classification, item, productID, description, quantity, unit, status</code></p>
                        <p class="text-muted small">Matches on <b>Item Name + Description</b>. Existing items are updated; new items are added.</p>
                        <input type="file" id="csvFile" accept=".csv" class="form-control-file mb-3">
                        <div id="importProgress" style="display:none;">
                            <div class="progress mb-2">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%">0%</div>
                            </div>
                            <small id="importStatusText" class="text-muted">Processing...</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="processImportBtn">Start Import</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirm Remove Modal -->
        <div class="modal fade" id="confirmRemoveModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header"><h5>Confirm Remove</h5></div>
                    <div class="modal-body">Are you sure you want to remove this item?</div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button class="btn btn-danger" id="confirmRemoveBtn">Remove</button>
                    </div>
                </div>
            </div>
        </div>

        <?php include 'helperFiles/footer.php'; ?>
    </body>

    <script>
        const CLASSIFICATIONS = <?php echo json_encode($CLASSIFICATIONS); ?>;
        
        function addClassificationTabs() {
            const $tabsContainer = $('#tabsContainer');
            $tabsContainer.empty(); // for re-render

            CLASSIFICATIONS.forEach((classification, index) => {
                const isActive = index === 0 ? 'active' : '';
                $tabsContainer.append(`
                    <div class="tab ${isActive}" data-type="${classification}">
                        ${classification}
                    </div>
                `);
            });
        }

        function populateClassificationDropdowns() {
            const $dropdowns = $('select[name="classification"]');
            $dropdowns.empty().append('<option value="">Select Classification</option>');

            CLASSIFICATIONS.forEach(c => {
                $dropdowns.append(`<option value="${c}">${c}</option>`);
            });
        }


        $(document).ready(function () {
            addClassificationTabs();
            populateClassificationDropdowns();

            const allInventory = <?php echo json_encode($inventory); ?>;
            let currentType = 'Equipment';
            let dataTable = null;
            let removeId = null;

            function renderTable(type) {
                if (dataTable) {
                    try { dataTable.destroy(); } catch (e) { /* ignore */ }
                }
                const tbody = $('#inventory-body');
                tbody.empty();

                if (allInventory[type] && allInventory[type].length !== 0) {
                    allInventory[type].forEach(item => {
                        if (item.classification !== type || item.status === 'Removed') return;
                        const row = `
                            <tr data-id="${item.id}">
                                <td>${item.item}</td>
                                <td>${item.productID}</td>
                                <td>${item.description || ''}</td>
                                <td>${item.quantity}</td>
                                <td>${item.unit}</td>
                                <td>${item.status}</td>
                                <td>
                                    <button class="btn-liquid edit-btn" style="padding: 6px 12px;"><i class="bi bi-pencil"></i></button>
                                    <button class="btn-liquid-danger delete-btn" style="padding: 6px 12px;"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>`;
                        tbody.append(row);
                    });
                }

                dataTable = $('#inventory-table').DataTable({
                    language: {
                        emptyTable: "No " + type.toLowerCase() + (type !== "Equipment" ? "s" : "") + " in inventory."
                    },
                    responsive: true,
                    pageLength: 10,
                    lengthChange: true,
                    searching: true,
                    ordering: true,
                    info: true
                });
            }

            // Initial render
            renderTable(currentType);
            
            // Tab switch
            $('.tab').click(function () {
                $('.tab').removeClass('active');
                $(this).addClass('active');
                currentType = $(this).data('type');
                renderTable(currentType);
            });

            // Remove
            $(document).on('click', '.delete-btn', function () {
                removeId = $(this).closest('tr').data('id');
                $('#confirmRemoveModal').modal('show');
            });

            $('#confirmRemoveBtn').click(function () {
                if (!removeId) return;
                $.post('ajax/ajax_inventory.php', { action: 'delete_inventory', id: removeId }, function (res) {
                    if (res.trim() === 'success') {
                        $('tr[data-id="' + removeId + '"]').remove();
                        $('#confirmRemoveModal').modal('hide');
                    } else alert('Failed to remove item.');
                });
            });

            // Add
            $('#addItemBtn').click(() => $('#addProductModal').modal('show'));

            $('#saveProductBtn').click(function () {
                const form = $('#addProductForm')[0];
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }

                const formData = $('#addProductForm').serializeArray();
                formData.push({ name: 'action', value: 'add_inventory' });

                $.post('ajax/ajax_inventory.php', formData, function (res) {
                    if (res.trim() === 'success') {
                        alert('Product added successfully');
                        location.reload();
                    } else alert('Failed to add product');
                });
            });

            // Edit
            $(document).on('click', '.edit-btn', function () {
                const id = $(this).closest('tr').data('id');
                const item = allInventory[currentType].find(i => i.id == id);
                editData = { ...item };

                const form = $('#editProductForm');
                for (const key in item) {
                    form.find(`[name="${key}"]`).val(item[key]);
                }

                $('#editProductModal').modal('show');
            });

            $('#saveEditBtn').click(function () {
                const form = $('#editProductForm')[0];
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }

                const newData = {};
                $('#editProductForm').serializeArray().forEach(i => newData[i.name] = i.value);

                $.post('ajax/ajax_inventory.php', { ...newData, action: 'update_inventory' }, function (res) {
                    if (res.trim() === 'success') {
                        alert('Record successfully updated');

                        const row = $('tr[data-id="' + newData.id + '"]');
                        row.find('td:eq(0)').text(newData.item);
                        row.find('td:eq(1)').text(newData.productID);
                        row.find('td:eq(2)').text(newData.description);
                        row.find('td:eq(3)').text(newData.quantity);
                        row.find('td:eq(4)').text(newData.unit);
                        row.find('td:eq(5)').text(newData.status);

                        const index = allInventory[currentType].findIndex(i => i.id == newData.id);
                        if (index !== -1) allInventory[currentType][index] = { ...newData };

                        $('#editProductModal').modal('hide');
                    } else alert('Failed to update record');
                });
            });

            // ================= BULK IMPORT LOGIC ==================
            $('#bulkImportBtn').click(() => {
                $('#bulkImportModal').modal('show');
                $('#importProgress').hide();
                $('#csvFile').val('');
                $('.progress-bar').css('width', '0%').text('0%');
                $('#processImportBtn').prop('disabled', false);
            });

            $('#processImportBtn').click(function () {
                const fileInput = document.getElementById('csvFile');
                if (!fileInput.files.length) {
                    alert('Please select a CSV file.');
                    return;
                }

                const file = fileInput.files[0];
                const reader = new FileReader();

                reader.onload = function (e) {
                    const text = e.target.result;
                    const rows = parseCSV(text);
                    if (rows.length === 0) {
                        alert('No valid data found or empty file.');
                        return;
                    }
                    processBulkData(rows);
                };
                reader.readAsText(file);
            });

            function parseCSV(text) {
                const lines = text.split(/\r\n|\n/).filter(l => l.trim());
                if (lines.length < 2) return [];
                const headers = lines[0].split(',').map(h => h.trim());
                return lines.slice(1).map(line => {
                    const values = [];
                    let inQuote = false, val = '';
                    for (let c of line) {
                        if (c === '"') inQuote = !inQuote;
                        else if (c === ',' && !inQuote) { values.push(val.trim()); val = ''; }
                        else val += c;
                    }
                    values.push(val.trim());
                    const obj = {};
                    headers.forEach((h, i) => obj[h] = values[i] || '');
                    return obj;
                });
            }

            async function processBulkData(rows) {
                $('#importProgress').show();
                $('#processImportBtn').prop('disabled', true);
                const flatInventory = Object.values(allInventory).flat();
                let success = 0, failed = 0;

                for (let i = 0; i < rows.length; i++) {
                    const row = rows[i];
                    // if (!row.productID) continue;

                    const existing = flatInventory.find(item => 
                        (item.item || '').trim().toLowerCase() === (row.item || '').trim().toLowerCase() && 
                        (item.description || '').trim().toLowerCase() === (row.description || '').trim().toLowerCase()
                    );
                    const action = existing ? 'update_inventory' : 'add_inventory';
                    const payload = { ...row, action: action };
                    if (existing) payload.id = existing.id;

                    try {
                        await new Promise(resolve => $.post('ajax/ajax_inventory.php', payload, res => {
                            if (res.trim() === 'success') success++; else failed++;
                            resolve();
                        }));
                    } catch (e) { failed++; }

                    const pct = Math.round(((i + 1) / rows.length) * 100);
                    $('.progress-bar').css('width', pct + '%').text(pct + '%');
                }

                alert(`Import Complete.\nSuccess: ${success}\nFailed: ${failed}`);
                location.reload();
            }

            // ================= EDIT MODE LOGIC ==================
            let isEditMode = false;
            let hasUnsavedChanges = false;

            $('#editModeBtn').click(function() {
                if (!isEditMode) {
                    enterEditMode();
                } else {
                    saveBulkChanges();
                }
            });

            $('#cancelEditBtn').click(function() {
                if (hasUnsavedChanges && !confirm("You have unsaved changes. Are you sure you want to discard them?")) {
                    return;
                }
                exitEditMode();
            });

            $('#inventory-table').on('input change', 'input, select', function() {
                if (isEditMode) {
                    hasUnsavedChanges = true;
                    $(this).closest('tr').addClass('modified-row');
                }
            });

            function enterEditMode() {
                isEditMode = true;
                hasUnsavedChanges = false;
                $('#editModeBtn').text('Save Changes').removeClass('btn-liquid-warning').addClass('btn-liquid-success');
                $('#cancelEditBtn').show();
                $('#addItemBtn, #scanBarcodeBtn, #bulkImportBtn').prop('disabled', true);
                
                // Disable DataTable controls to prevent state loss during edit
                $('.dataTables_length, .dataTables_filter, .dataTables_paginate, .dataTables_info').hide();

                $('#inventory-table tbody tr').each(function() {
                    const row = $(this);

                    const cells = row.find('td');
                    
                    // Helper to create input safely
                    const mkInput = (val, name, type='text') => {
                        const safeVal = val.replace(/"/g, '&quot;');
                        return `<input type="${type}" class="form-control form-control-sm" name="${name}" value="${safeVal}" style="min-width: 80px;">`;
                    };
                    
                    cells.eq(0).html(mkInput(cells.eq(0).text(), 'item'));
                    cells.eq(1).html(mkInput(cells.eq(1).text(), 'productID'));
                    cells.eq(2).html(mkInput(cells.eq(2).text(), 'description'));
                    cells.eq(3).html(mkInput(cells.eq(3).text(), 'quantity', 'number'));
                    
                    const unitVal = cells.eq(4).text();
                    const units = ['pieces', 'mL', 'grams', 'boxes'];
                    let unitOpts = units.map(u => `<option value="${u}" ${u === unitVal ? 'selected' : ''}>${u}</option>`).join('');
                    cells.eq(4).html(`<select class="form-control form-control-sm" name="unit">${unitOpts}</select>`);

                    const statusVal = cells.eq(5).text();
                    const statuses = ['Available', 'Out of Stock'];
                    let statusOpts = statuses.map(s => `<option value="${s}" ${s === statusVal ? 'selected' : ''}>${s}</option>`).join('');
                    cells.eq(5).html(`<select class="form-control form-control-sm" name="status">${statusOpts}</select>`);

                    // Hide action buttons
                    cells.eq(6).find('button').hide();
                });
            }

            function exitEditMode() {
                isEditMode = false;
                hasUnsavedChanges = false;
                $('#editModeBtn').text('Edit Mode').removeClass('btn-liquid-success').addClass('btn-liquid-warning');
                $('#cancelEditBtn').hide();
                $('#addItemBtn, #scanBarcodeBtn, #bulkImportBtn').prop('disabled', false);
                
                renderTable(currentType);
            }

            async function saveBulkChanges() {
                const updates = [];
                $('#inventory-table tbody tr').each(function() {
                    const row = $(this);
                    if(row.find('.dataTables_empty').length) return;

                    updates.push({
                        id: row.data('id'),
                        classification: currentType,
                        item: row.find('[name="item"]').val(),
                        productID: row.find('[name="productID"]').val(),
                        description: row.find('[name="description"]').val(),
                        quantity: row.find('[name="quantity"]').val(),
                        unit: row.find('[name="unit"]').val(),
                        status: row.find('[name="status"]').val(),
                        action: 'update_inventory'
                    });
                });

                $('#editModeBtn').text('Saving...').prop('disabled', true);
                
                // Process updates sequentially or in parallel. Parallel is faster.
                await Promise.all(updates.map(data => $.post('ajax/ajax_inventory.php', data)));
                
                alert('Changes saved.');
                location.reload();
            }

            // ================= BARCODE SCANNER LOGIC ==================
            let scannerActive = false;
            let quaggaInitialized = false;
            let detectedHandlerBound = false;
            let lastDetected = null;
            const DETECTION_DEBOUNCE_MS = 1200; // avoid repeated detections

            // Show modal; start scanner only when modal is fully shown
            $('#scanBarcodeBtn').click(function () {
                $('#barcodeScannerModal').modal('show');
            });

            $('#barcodeScannerModal').on('shown.bs.modal', function () {
                // Delay start so Bootstrap's modal transform/animation finishes and
                // the container has a non-zero size; prevents Quagga from attaching
                // the video element elsewhere.
                setTimeout(startScanner, 250);
            });

            // When modal hides, ensure scanner stops and event unbind
            $('#barcodeScannerModal').on('hidden.bs.modal', function () {
                stopScanner();
            });

            // Stop button should stop and close modal
            $('#stopScannerBtn').click(function () {
                $('#barcodeScannerModal').modal('hide');
                stopScanner();
            });

            // ===== Start Scanner =====
            function startScanner() {
                if (scannerActive) return;
                scannerActive = true;

                const containerEl = document.querySelector('#scanner-container');
                // if container has no size yet, retry shortly (modal animation may still be running)
                if (!containerEl || containerEl.clientWidth === 0 || containerEl.clientHeight === 0) {
                    scannerActive = false;
                    setTimeout(startScanner, 200);
                    return;
                }

                // remove only existing video/canvas nodes (avoid wiping other content)
                $('#scanner-container').find('video, canvas').remove();

                // If Quagga was already initialized before, just start it again
                if (quaggaInitialized) {
                    try {
                        Quagga.start();
                        console.log('Quagga restarted');
                    } catch (e) {
                        console.warn('Quagga restart failed, reinitializing', e);
                        quaggaInitialized = false;
                    }
                }

                if (!quaggaInitialized) {
                    Quagga.init({
                        inputStream: {
                            type: "LiveStream",
                            target: containerEl,
                            constraints: {
                                facingMode: "environment"
                            }
                        },
                        decoder: {
                            readers: ["code_128_reader", "ean_reader", "ean_8_reader", "upc_reader"]
                        },
                        locate: true
                    }, function (err) {
                        if (err) {
                            console.error('Quagga init error:', err);
                            alert("Error initializing scanner: " + (err.message || err));
                            scannerActive = false;
                            return;
                        }
                        quaggaInitialized = true;
                        try {
                            Quagga.start();
                            console.log("Quagga started (init)");
                        } catch (e) {
                            console.error('Quagga start after init failed:', e);
                            scannerActive = false;
                            return;
                        }

                        // bind detection handler only once
                        if (!detectedHandlerBound) {
                            Quagga.onDetected(function (data) {
                                // debounce repeated detections
                                const code = data && data.codeResult && data.codeResult.code ? data.codeResult.code.trim() : null;
                                if (!code) return;
                                const now = Date.now();
                                if (lastDetected && (now - lastDetected.time < DETECTION_DEBOUNCE_MS) && lastDetected.code === code) return;
                                lastDetected = { code, time: now };

                                // pause scanner to stabilize UI while handling
                                try { Quagga.pause(); } catch (e) { /* ignore */ }

                                handleScannedBarcode(code);
                            });
                            detectedHandlerBound = true;
                        }
                    });

                    // Optional: log processing errors for debugging
                    Quagga.onProcessed(function(result) {
                        // no-op or console.debug(result);
                    });
                }
            }

            // ===== Stop Scanner =====
            function stopScanner() {
                if (!scannerActive) return;
                scannerActive = false;

                // Try to stop Quagga properly
                try {
                    if (quaggaInitialized) {
                        // Quagga.stop() will stop the camera stream; Quagga.pause() can be used to resume
                        Quagga.stop();
                    }
                } catch (e) {
                    console.warn("Error stopping Quagga:", e);
                }

                // Explicitly stop any remaining camera tracks attached to video element(s)
                try {
                    const videos = document.querySelectorAll('#scanner-container video');
                    videos.forEach(video => {
                        const stream = video.srcObject;
                        if (stream && stream.getTracks) {
                            stream.getTracks().forEach(track => {
                                try { track.stop(); } catch (e) { /* ignore */ }
                            });
                        }
                        // also revoke srcObject
                        try { video.srcObject = null; } catch (e) {}
                    });
                } catch (e) {
                    console.warn('Error stopping video tracks:', e);
                }

                // Clear container for next use
                $('#scanner-container').empty();
                // keep quaggaInitialized true so restart can call Quagga.start() without reinit
            }

            // ===== Handle Scanned Barcode =====
            function handleScannedBarcode(code) {
                if (!code) return;

                $('#result').text(code);

                // flatten inventory for easier lookup
                const foundItem = Object.values(allInventory).flat()
                    .find(item => (item.productID || '').toString().trim() === code);

                if (foundItem) {
                    // Product exists — highlight in table
                    $('#barcodeScannerModal').modal('hide');
                    alert("Item found: " + foundItem.item);

                    // Switch tab if necessary
                    if (foundItem.classification !== currentType) {
                        $(`.tab[data-type="${foundItem.classification}"]`).click();
                    }

                    // Highlight after small delay to allow tab switch and DataTable redraw
                    setTimeout(() => {
                        const row = $(`tr[data-id="${foundItem.id}"]`);
                        if (row.length > 0) {
                            $('html, body').animate({ scrollTop: row.offset().top - 100 }, 600);
                            row.css('background-color', '#fff3cd'); // yellow highlight
                            setTimeout(() => row.css('background-color', ''), 2000);
                        } else {
                            $('#inventory-table').DataTable().rows().every(function () {
                                const d = this.data();
                                if (d && d[1] && d[1].toString().trim() === code) {
                                    const node = this.node();
                                    $(node).css('background-color', '#fff3cd');
                                    $('html, body').animate({ scrollTop: $(node).offset().top - 100 }, 600);
                                    setTimeout(() => $(node).css('background-color', ''), 2000);
                                }
                            });
                        }
                    }, 400);
                } else {
                    // Product not found — open Add Product modal with prefilled productID
                    $('#barcodeScannerModal').modal('hide');
                    setTimeout(() => {
                        $('#addProductModal').modal('show');
                        $('#addProductForm [name="productID"]').val(code);
                    }, 400);
                }
            }
        });
    </script>
</html>
