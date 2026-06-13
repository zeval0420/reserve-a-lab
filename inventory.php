<?php
    include('../scilab/helperFiles/db_connection.php');
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

    $inventoryQuery = $conn->query("SELECT id, classification, item, productID, description, quantity, unit, status, threshold_qty, threshold_notified 
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
            .tabs { display: flex; gap: 10px; margin-bottom: 15px; overflow-x: auto; padding-bottom: 5px; -webkit-overflow-scrolling: touch; }
            .tabs::-webkit-scrollbar { height: 6px; }
            .tabs::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.2); border-radius: 10px; }
            
            .tab { 
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 8px 20px;
                border-radius: 20px;
                font-weight: 600;
                font-size: 13px;
                cursor: pointer;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                border: 1px solid rgba(43, 85, 196, 0.2);
                background: linear-gradient(135deg, rgba(43, 85, 196, 0.05), rgba(43, 85, 196, 0.15));
                color: #2B55C4;
                backdrop-filter: blur(8px);
                -webkit-backdrop-filter: blur(8px);
                box-shadow: 0 4px 12px rgba(43, 85, 196, 0.1);
                white-space: nowrap;
                flex-shrink: 0;
            }
            .tab:hover {
                background: linear-gradient(135deg, rgba(43, 85, 196, 0.1), rgba(43, 85, 196, 0.25));
                transform: translateY(-2px);
                box-shadow: 0 8px 20px rgba(43, 85, 196, 0.2);
                color: #1a3a8f;
                border-color: rgba(43, 85, 196, 0.3);
            }
            .tab.active { 
                background: #2B55C4; 
                color: white; 
                border-color: #2B55C4;
            }
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
                        <button id="stockInBtn" class="btn-liquid-info">Stock In</button>
                        <button id="bulkImportBtn" class="btn-liquid-info">Bulk Import</button>
                        <button id="thresholdAlertsBtn" class="btn-liquid-warning">Alert Thresholds</button>
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
                                <select class="form-control liquid-input" name="classification" required></select>
                            </div>
                            <div class="form-group">
                                <label>Product Name</label>
                                <input type="text" class="form-control liquid-input" name="item" required>
                            </div>
                            <div class="form-group">
                                <label>Product ID</label>
                                <input type="text" class="form-control liquid-input" name="productID" required>
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <input type="text" class="form-control liquid-input" name="description" required>
                            </div>
                            <div class="form-group">
                                <label>Quantity</label>
                                <input type="number" class="form-control liquid-input" name="quantity" min="1" required>
                            </div>
                            <div class="form-group">
                                <label>Quantity Unit</label>
                                <select class="form-control liquid-input" name="unit" required>
                                    <option value="">Select Unit</option>
                                    <option value="pieces">pieces</option>
                                    <option value="mL">mL</option>
                                    <option value="grams">grams</option>
                                    <option value="boxes">boxes</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select class="form-control liquid-input" name="status" required>
                                    <option value="">Select Status</option>
                                    <option value="Available">Available</option>
                                    <option value="Out of Stock">Out of Stock</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Alert Threshold Quantity (Optional)</label>
                                <input type="number" class="form-control liquid-input" name="threshold_qty" min="0" placeholder="No alert">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-liquid-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn-liquid-success" id="saveProductBtn">Add</button>
                    </div>
                </div>
            </div>
        </div>
        </div>

        <!-- Stock In Modal -->
        <div class="modal fade" id="stockInModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Stock In Item</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <!-- Navigation tabs for Manual vs CSV -->
                        <ul class="nav nav-tabs" id="stockInTabs" role="tablist" style="margin-bottom: 15px;">
                            <li role="presentation" class="active" style="width: 50%; text-align: center;">
                                <a href="#manualStockIn" aria-controls="manualStockIn" role="tab" data-toggle="tab" style="font-weight:bold; color:#2B55C4;">Manual Add</a>
                            </li>
                            <li role="presentation" style="width: 50%; text-align: center;">
                                <a href="#csvStockIn" aria-controls="csvStockIn" role="tab" data-toggle="tab" style="font-weight:bold; color:#2B55C4;">CSV Import</a>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <!-- Manual Stock In Form -->
                            <div class="tab-pane fade in active" id="manualStockIn" role="tabpanel">
                                <form id="stockInForm">
                                    <div id="stockInRowsContainer">
                                        <!-- Rows will be dynamically appended here -->
                                    </div>
                                </form>
                                <div class="mt-2">
                                    <button type="button" class="btn-liquid-info" id="addStockInRowBtn"><i class="bi bi-plus-circle"></i> Add Row</button>
                                </div>
                                <div class="text-right mt-3">
                                    <button type="button" class="btn-liquid-success" id="saveStockInBtn">Stock In</button>
                                </div>
                            </div>
                            <!-- CSV Stock In -->
                            <div class="tab-pane fade" id="csvStockIn" role="tabpanel">
                                <p>Upload a CSV file with headers: <code>classification, item, productID, description, quantity, unit, status</code></p>
                                <p class="text-muted small">Matches on <b>Item Name + Description</b>. Only existing items will be updated (quantities will be added).</p>
                                <input type="file" id="csvStockInFile" accept=".csv" class="form-control-file mb-3">
                                <div id="stockInImportProgress" style="display:none;">
                                    <div class="progress mb-2">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" role="progressbar" style="width: 0%">0%</div>
                                    </div>
                                    <small id="stockInImportStatusText" class="text-muted">Processing...</small>
                                </div>
                                <div class="text-right mt-3">
                                    <button type="button" class="btn-liquid-info" id="processStockInCsvBtn">Start CSV Stock In</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-liquid-secondary" data-dismiss="modal">Close</button>
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
                                <select class="form-control liquid-input" name="classification" required></select>
                            </div>
                            <div class="form-group">
                                <label>Product Name</label>
                                <input type="text" class="form-control liquid-input" name="item" required>
                            </div>
                            <div class="form-group">
                                <label>Product ID</label>
                                <input type="text" class="form-control liquid-input" name="productID" required>
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <input type="text" class="form-control liquid-input" name="description" required>
                            </div>
                            <div class="form-group">
                                <label>Quantity</label>
                                <input type="number" class="form-control liquid-input" name="quantity" min="1" required>
                            </div>
                            <div class="form-group">
                                <label>Quantity Unit</label>
                                <select class="form-control liquid-input" name="unit" required>
                                    <option value="pieces">pieces</option>
                                    <option value="mL">mL</option>
                                    <option value="grams">grams</option>
                                    <option value="boxes">boxes</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select class="form-control liquid-input" name="status" required>
                                    <option value="Available">Available</option>
                                    <option value="Out of Stock">Out of Stock</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Alert Threshold Quantity (Optional)</label>
                                <input type="number" class="form-control liquid-input" name="threshold_qty" min="0" placeholder="No alert">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-liquid-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn-liquid-success" id="saveEditBtn">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Barcode Scanner Modal (compact vertically) -->
        <div class="modal fade" id="barcodeScannerModal" tabindex="-1" role="dialog" aria-labelledby="barcodeScannerModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header py-2">
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
                        <button id="stopScannerBtn" class="btn-liquid-danger">Stop Scanner</button>
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
                        <button type="button" class="btn-liquid-secondary" data-dismiss="modal">Close</button>
                        <button type="button" class="btn-liquid" id="processImportBtn">Start Import</button>
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
                        <button class="btn-liquid-secondary" data-dismiss="modal">Cancel</button>
                        <button class="btn-liquid-danger" id="confirmRemoveBtn">Remove</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert Threshold Settings Modal -->
        <div class="modal fade" id="thresholdSettingsModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">🔔 Alert Threshold Settings</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">Set threshold values for items. An automated email notification will be sent to admins when an item's quantity reaches or drops below its threshold.</p>
                        <div style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-bordered table-striped" id="thresholds-edit-table" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Item Name</th>
                                        <th>Description</th>
                                        <th>Current Stock</th>
                                        <th>Alert Threshold Quantity</th>
                                    </tr>
                                </thead>
                                <tbody id="thresholds-edit-body">
                                    <!-- Populated dynamically -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-liquid-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn-liquid-success" id="saveThresholdsBtn">Save Thresholds</button>
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

                        const qtyVal = parseInt(item.quantity);
                        const thresholdVal = (item.threshold_qty !== null && item.threshold_qty !== '') ? parseInt(item.threshold_qty) : null;
                        const isLow = thresholdVal !== null && qtyVal <= thresholdVal;
                        
                        const qtyDisplay = isLow ? `<span class="text-danger" style="font-weight:bold;"><i class="bi bi-exclamation-triangle-fill"></i> ${item.quantity}</span>` : item.quantity;
                        const statusDisplay = isLow ? `<span class="badge badge-danger" style="background-color:#c0392b; color:#fff; padding: 4px 8px; border-radius: 4px;">Low Stock</span>` : item.status;

                        const row = `
                            <tr data-id="${item.id}">
                                <td>${item.item}</td>
                                <td>${item.productID}</td>
                                <td>${item.description || ''}</td>
                                <td data-quantity="${item.quantity}">${qtyDisplay}</td>
                                <td>${item.unit}</td>
                                <td data-status="${item.status}">${statusDisplay}</td>
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
                    } else showToast('Failed to remove item.', 'error');
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
                        showToast('Product added successfully', 'success');
                        location.reload();
                    } else showToast('Failed to add product', 'error');
                });
            });
            // Stock In Logic
            $('#stockInBtn').click(function() {
                $('#stockInRowsContainer').empty();
                addStockInRow();
                
                $('#stockInImportProgress').hide();
                $('#csvStockInFile').val('');
                $('#stockInModal .progress-bar').css('width', '0%').text('0%');
                $('#processStockInCsvBtn').prop('disabled', false);
                
                $('#stockInModal').modal('show');
            });

            $('#addStockInRowBtn').click(function() {
                addStockInRow();
            });

            function addStockInRow() {
                const flatItems = Object.values(allInventory).reduce((acc, val) => acc.concat(val), []);
                const uniqueItems = [...new Set(flatItems.filter(i => i.status !== 'Removed').map(i => i.item))];
                uniqueItems.sort();
                
                const $itemSelect = $('<select class="form-control liquid-input stock-in-item-select" required></select>');
                $itemSelect.append('<option value="" style="color:#333;">Select an Item...</option>');
                uniqueItems.forEach(item => {
                    $itemSelect.append($('<option></option>').val(item).text(item).css('color', '#333'));
                });

                const rowHtml = $(`
                    <div class="stock-in-row" style="display:flex; gap:10px; margin-bottom:10px; align-items:flex-end;">
                        <div style="flex:2;">
                            <label style="font-size:12px;">Search Item</label>
                            <!-- select goes here -->
                        </div>
                        <div style="flex:2;">
                            <label style="font-size:12px;">Description</label>
                            <select class="form-control liquid-input stock-in-desc-select" disabled required>
                                <option value="" style="color:#333;">Select Item First</option>
                            </select>
                        </div>
                        <div style="flex:1;">
                            <label style="font-size:12px;">Quantity</label>
                            <input type="number" class="form-control liquid-input stock-in-qty" min="1" required>
                        </div>
                        <div>
                            <button type="button" class="btn-liquid-danger remove-stock-in-row-btn" style="padding:6px 12px; margin-bottom: 2px;"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                `);
                
                rowHtml.find('div').eq(0).append($itemSelect);
                $('#stockInRowsContainer').append(rowHtml);
            }

            $(document).on('click', '.remove-stock-in-row-btn', function() {
                if ($('.stock-in-row').length > 1) {
                    $(this).closest('.stock-in-row').remove();
                } else {
                    showToast('You must have at least one row.', 'warning');
                }
            });

            $(document).on('change', '.stock-in-item-select', function() {
                const selectedItem = $(this).val();
                const descSelect = $(this).closest('.stock-in-row').find('.stock-in-desc-select');
                
                if (!selectedItem) {
                    descSelect.empty().append('<option value="" style="color:#333;">Select Item First</option>').prop('disabled', true);
                    return;
                }
                
                descSelect.empty().append('<option value="" style="color:#333;">Select Description...</option>');
                const flatItems = Object.values(allInventory).reduce((acc, val) => acc.concat(val), []);
                const matches = flatItems.filter(i => i.item === selectedItem && i.status !== 'Removed');
                matches.forEach(m => {
                    const descText = m.description ? m.description : 'No Description';
                    descSelect.append($('<option></option>').val(m.id).text(`${descText} (Current Qty: ${m.quantity} ${m.unit})`).css('color', '#333'));
                });
                descSelect.prop('disabled', false);
            });

            $('#saveStockInBtn').click(async function() {
                const form = $('#stockInForm')[0];
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }

                const rows = $('.stock-in-row');
                const updates = [];
                let valid = true;

                rows.each(function() {
                    const id = $(this).find('.stock-in-desc-select').val();
                    const qty = parseInt($(this).find('.stock-in-qty').val());
                    
                    if (!id || isNaN(qty) || qty <= 0) {
                        valid = false;
                        return false; // Break loop
                    }
                    updates.push({ action: 'stock_in_inventory', id: id, quantity: qty });
                });

                if (!valid || updates.length === 0) {
                    showToast('Please ensure all rows have a valid item, description, and quantity greater than 0.', 'warning');
                    return;
                }

                $('#saveStockInBtn').prop('disabled', true).text('Processing...');
                let successCount = 0;
                let failedCount = 0;

                for (const update of updates) {
                    try {
                        await new Promise(resolve => $.post('ajax/ajax_inventory.php', update, res => {
                            if (res.trim() === 'success') successCount++;
                            else failedCount++;
                            resolve();
                        }));
                    } catch (e) { failedCount++; }
                }

                if (failedCount > 0) {
                    showToast(`Processed: ${successCount} successful, ${failedCount} failed.`, 'warning');
                } else {
                    showToast('All items successfully stocked in!', 'success');
                }
                
                location.reload();
            });

            $('#processStockInCsvBtn').click(function() {
                const fileInput = document.getElementById('csvStockInFile');
                if (!fileInput.files.length) {
                    showToast('Please select a CSV file.', 'warning');
                    return;
                }

                const file = fileInput.files[0];
                const reader = new FileReader();

                reader.onload = function (e) {
                    const text = e.target.result;
                    const rows = parseCSV(text);
                    if (rows.length === 0) {
                        showToast('No valid data found or empty file.', 'warning');
                        return;
                    }
                    processStockInBulkData(rows);
                };
                reader.readAsText(file);
            });

            async function processStockInBulkData(rows) {
                $('#stockInImportProgress').show();
                $('#processStockInCsvBtn').prop('disabled', true);
                const flatItems = Object.values(allInventory).reduce((acc, val) => acc.concat(val), []);
                const flatInventory = flatItems.filter(i => i.status !== 'Removed');
                let success = 0, failed = 0, skipped = 0;

                for (let i = 0; i < rows.length; i++) {
                    const row = rows[i];
                    const rowQty = parseInt(row.quantity);
                    
                    if (isNaN(rowQty) || rowQty <= 0) {
                        skipped++;
                        continue;
                    }

                    const existing = flatInventory.find(item => 
                        (item.item || '').trim().toLowerCase() === (row.item || '').trim().toLowerCase() && 
                        (item.description || '').trim().toLowerCase() === (row.description || '').trim().toLowerCase()
                    );
                    
                    if (existing) {
                        try {
                            await new Promise(resolve => $.post('ajax/ajax_inventory.php', {
                                action: 'stock_in_inventory',
                                id: existing.id,
                                quantity: rowQty
                            }, res => {
                                if (res.trim() === 'success') success++; else failed++;
                                resolve();
                            }));
                        } catch (e) { failed++; }
                    } else {
                        skipped++; // Item doesn't exist, we don't create it in Stock In mode
                    }

                    const pct = Math.round(((i + 1) / rows.length) * 100);
                    $('#stockInModal .progress-bar').css('width', pct + '%').text(pct + '%');
                }

                showToast(`Stock In Complete. Success: ${success}, Failed: ${failed}, Skipped/Not Found: ${skipped}`, 'info', 5000);
                location.reload();
            }

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
                        showToast('Record successfully updated', 'success');

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
                    } else showToast('Failed to update record', 'error');
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
                    showToast('Please select a CSV file.', 'warning');
                    return;
                }

                const file = fileInput.files[0];
                const reader = new FileReader();

                reader.onload = function (e) {
                    const text = e.target.result;
                    const rows = parseCSV(text);
                    if (rows.length === 0) {
                        showToast('No valid data found or empty file.', 'warning');
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

                showToast(`Import Complete. Success: ${success}, Failed: ${failed}`, 'info', 5000);
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
                        const safeVal = (val || '').replace(/"/g, '&quot;');
                        return `<input type="${type}" class="form-control form-control-sm liquid-input" name="${name}" value="${safeVal}" style="min-width: 80px;">`;
                    };
                    
                    cells.eq(0).html(mkInput(cells.eq(0).text(), 'item'));
                    cells.eq(1).html(mkInput(cells.eq(1).text(), 'productID'));
                    cells.eq(2).html(mkInput(cells.eq(2).text(), 'description'));
                    cells.eq(3).html(mkInput(cells.eq(3).attr('data-quantity') || cells.eq(3).text(), 'quantity', 'number'));
                    
                    const unitVal = cells.eq(4).text();
                    const units = ['pieces', 'mL', 'grams', 'boxes'];
                    let unitOpts = units.map(u => `<option value="${u}" ${u === unitVal ? 'selected' : ''}>${u}</option>`).join('');
                    cells.eq(4).html(`<select class="form-control form-control-sm liquid-input" name="unit">${unitOpts}</select>`);

                    const statusVal = cells.eq(5).attr('data-status') || cells.eq(5).text();
                    const statuses = ['Available', 'Out of Stock'];
                    let statusOpts = statuses.map(s => `<option value="${s}" ${s === statusVal ? 'selected' : ''}>${s}</option>`).join('');
                    cells.eq(5).html(`<select class="form-control form-control-sm liquid-input" name="status">${statusOpts}</select>`);

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
                $('#inventory-table tbody tr.modified-row').each(function() {
                    const row = $(this);
                    if(row.find('.dataTables_empty').length) return;

                    const itemData = allInventory[currentType].find(i => i.id == row.data('id')) || {};
                    updates.push({
                        id: row.data('id'),
                        classification: currentType,
                        item: row.find('[name="item"]').val(),
                        productID: row.find('[name="productID"]').val(),
                        description: row.find('[name="description"]').val(),
                        quantity: row.find('[name="quantity"]').val(),
                        unit: row.find('[name="unit"]').val(),
                        status: row.find('[name="status"]').val(),
                        threshold_qty: itemData.threshold_qty !== null ? itemData.threshold_qty : '',
                        action: 'update_inventory'
                    });
                });

                if (updates.length === 0) {
                    showToast("No changes to save.", 'info');
                    exitEditMode();
                    return;
                }

                $('#editModeBtn').text('Saving...').prop('disabled', true);
                
                // Process updates sequentially or in parallel. Parallel is faster.
                await Promise.all(updates.map(data => $.post('ajax/ajax_inventory.php', data)));
                
                showToast('Changes saved.', 'success');
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
                            showToast("Error initializing scanner: " + (err.message || err), 'error');
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

            // ================= ALERT THRESHOLDS EVENT HANDLERS =================
            $('#thresholdAlertsBtn').click(function() {
                const tbody = $('#thresholds-edit-body');
                tbody.empty();

                // Flatten all items across classifications
                const flatItems = Object.values(allInventory).reduce((acc, val) => acc.concat(val), []);
                const activeItems = flatItems.filter(i => i.status !== 'Removed');

                if (activeItems.length === 0) {
                    tbody.append('<tr><td colspan="4" class="text-center">No products in inventory.</td></tr>');
                } else {
                    activeItems.sort((a, b) => a.item.localeCompare(b.item));
                    activeItems.forEach(item => {
                        const thresholdVal = (item.threshold_qty !== null && item.threshold_qty !== undefined) ? item.threshold_qty : '';
                        const desc = item.description ? item.description : '<span class="text-muted">No Description</span>';
                        
                        const qtyVal = parseInt(item.quantity);
                        const isLow = item.threshold_qty !== null && item.threshold_qty !== '' && qtyVal <= parseInt(item.threshold_qty);
                        const rowStyle = isLow ? 'background-color: #fff3f3;' : '';
                        
                        tbody.append(`
                            <tr style="${rowStyle}" data-id="${item.id}">
                                <td><strong>${item.item}</strong> <small class="text-muted">(${item.classification})</small></td>
                                <td>${desc}</td>
                                <td>${item.quantity} ${item.unit}</td>
                                <td>
                                    <input type="number" class="form-control liquid-input threshold-input" 
                                           value="${thresholdVal}" min="0" placeholder="No Alert" 
                                           style="width: 120px;">
                                </td>
                            </tr>
                        `);
                    });
                }

                $('#thresholdSettingsModal').modal('show');
            });

            $('#saveThresholdsBtn').click(function() {
                const thresholds = [];
                $('#thresholds-edit-body tr').each(function() {
                    const row = $(this);
                    const id = row.data('id');
                    if (!id) return;
                    
                    const val = row.find('.threshold-input').val();
                    thresholds.push({
                        id: id,
                        threshold: val
                    });
                });

                $('#saveThresholdsBtn').prop('disabled', true).text('Saving...');

                $.post('ajax/ajax_inventory.php', {
                    action: 'update_thresholds',
                    thresholds: thresholds
                }, function(res) {
                    $('#saveThresholdsBtn').prop('disabled', false).text('Save Thresholds');
                    if (res.trim() === 'success') {
                        showToast('Alert thresholds updated successfully.', 'success');
                        $('#thresholdSettingsModal').modal('hide');
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        showToast('Failed to update thresholds.', 'error');
                    }
                });
            });

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
                    showToast("Item found: " + foundItem.item, 'success');

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
