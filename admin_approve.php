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

    $statusFilter = $_GET['status'] ?? 'Pending';
    $syResult = $conn->query("SELECT value FROM current WHERE description = 'School Year' ORDER BY id DESC LIMIT 1");
    $currentSY = ($syResult && $syResult->num_rows > 0) ? $syResult->fetch_assoc()['value'] : null;

    $sql = "SELECT * FROM scilab_form_requests WHERE statusScilabPersonnel = '$statusFilter' AND sy = '$currentSY' ORDER BY dateRequested DESC";
    $result = $conn->query($sql);

    $requests = [];
    $formIDs = [];

    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
        $formIDs[] = $row['id'];
    }

    $counts = [
        'Pending' => 0,
        'Approved' => 0,
        'Rejected' => 0
    ];

    $countQuery = $conn->query("
        SELECT statusScilabPersonnel AS status, COUNT(*) AS total
        FROM scilab_form_requests
        GROUP BY statusScilabPersonnel
    ");

    while ($row = $countQuery->fetch_assoc()) {
        $status = ucfirst(strtolower($row['status']));
        if (isset($counts[$status])) {
            $counts[$status] = $row['total'];
        }
    }

    $materials = [];
    $materialsDetailed = [];

    if ($formIDs) {
        $ids = implode(',', array_map('intval', $formIDs));

        $matQ = $conn->query("
            SELECT formID, quantity, item, description
            FROM scilab_material_requests
            WHERE formID IN ($ids)
        ");

        while ($m = $matQ->fetch_assoc()) {

            $item = htmlspecialchars($m['item']);
            $desc = !empty($m['description']) ? htmlspecialchars($m['description']) : '';
            $qty = $m['quantity'];

            // TABLE VERSION (ONLY ITEM)
            $materials[$m['formID']][] = "
                <div class='material-line'>
                    <span class='bullet'>•</span>
                    <span class='material-text'>{$item}</span>
                </div>
            ";

            // DETAILED VERSION (QTY + ITEM + DESCRIPTION)
            $materialsDetailed[$m['formID']][] = "
                <div class='material-line-detailed'>
                    <span class='material-qty'>[{$qty}x]</span>
                    <span class='material-text'>
                        {$item}" . ($desc ? " ({$desc})" : "") . "
                    </span>
                </div>
            ";
        }
    }
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Admin Approval</title>
        <?php include('helperFiles/headData.php'); ?>
        <style>
            body { background-color: #f5f5f5; }
            .form-container { background-color: #fff; padding: 25px; margin: 25px auto; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); width: 98%; }
            .table th { background-color: #2B55C4; color: white; text-align: center; white-space: nowrap; }
            .table td, .table th { font-size: 13px; vertical-align: middle; }
            .table-responsive { width: 100%; overflow-x: auto; }
            table tbody td { white-space: normal !important; word-wrap: break-word; vertical-align: top; }
            table td { max-width: 200px; }
            .action-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
            .dataTables_wrapper { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
            table.dataTable { width: 100% !important; table-layout: auto !important; white-space: nowrap; }
            .btn-scan { background-color: #0078D7; color: #fff; border: none; padding: 8px 12px; border-radius: 6px; margin-right: 8px; }
            .btn-scan:hover { background-color: #005fa3; }

            @media (max-width: 768px) {
                .form-container { padding: 10px; margin: 10px auto; }
                .action-header { flex-direction: column; align-items: flex-start; gap: 10px; }
            }

            .status-buttons { display: flex; gap: 10px; flex-wrap: wrap; }
            
            .btn-liquid.active {
                background: #2B55C4; color: white;
            }
            .btn-liquid-success.active {
                background: #28a745; color: white;
            }
            .btn-liquid-danger.active {
                background: #dc3545; color: white;
            }
            
            .btn-liquid .badge, .btn-liquid-success .badge, .btn-liquid-danger .badge {
                margin-right: 5px; background: rgba(0,0,0,0.1); color: inherit;
            }
            .btn-liquid.active .badge, .btn-liquid-success.active .badge, .btn-liquid-danger.active .badge {
                background: rgba(255,255,255,0.2); color: white;
            }

            @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
            .spin { animation: spin 1s linear infinite; display: inline-block; }

            .material-line {
                display: flex;
                align-items: flex-start;
                gap: 4px;
                margin-bottom: 4px;
            }

            .bullet {
                width: 14px; /* fixed width so all align */
                flex-shrink: 0;
                font-size: 16px;
                line-height: 1.4;
            }

            .material-text {
                flex: 1;
                line-height: 1.4;
            }
            .material-line-detailed {
                display: flex;
                align-items: flex-start;
                margin-bottom: 6px;
            }

            .material-qty {
                min-width: 30px;
                font-weight: 600;
            }

            .material-line-detailed .material-text {
                flex: 1;
                line-height: 1.4;
            }
        </style>
    </head>
    <body>
        <?php include('helperFiles/header.php'); ?>

        <div class="main-wrapper">
            
            <div class="form-container">
                <div class="action-header">
                    <h2>Request Forms Management</h2>
                    <div>
                        <button id="scanBarcodeBtn" class="btn-scan">Generate Summary</button>
                    </div>
                </div>

                <div class="status-buttons mb-3">
                    <a href="?status=Pending" class="btn-liquid <?= $statusFilter === 'Pending' ? 'active' : '' ?>">
                        <span class="badge badge-pill badge-secondary"><?= $counts['Pending'] ?></span> Pending
                    </a>
                    <a href="?status=Approved" class="btn-liquid-success <?= $statusFilter === 'Approved' ? 'active' : '' ?>">
                        <span class="badge badge-pill badge-secondary"><?= $counts['Approved'] ?></span> Approved
                    </a>
                    <a href="?status=Rejected" class="btn-liquid-danger <?= $statusFilter === 'Rejected' ? 'active' : '' ?>">
                        <span class="badge badge-pill badge-secondary"><?= $counts['Rejected'] ?></span> Rejected
                    </a>
                </div>
                <br/>

                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="admin-approval-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Requester Name</th>
                                <?php if ($statusFilter === 'Approved'): ?><th>Control #</th><?php endif; ?>
                                <th>Lab Name</th>
                                <th>Grade - Section/s</th>
                                <th>Subject</th>
                                <th>Topic</th>
                                <th>Date of Use</th>
                                <th>Requested Materials</th>
                                <th>Teacher-in-Charge</th>
                                <?php if ($statusFilter === 'Approved'): ?><th>Remarks</th><?php endif; ?>
                                <?php if ($statusFilter === 'Rejected'): ?><th>Feedback</th><?php endif; ?>
                                <?php if ($statusFilter !== 'Rejected'): ?><th>Action</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php
                                    $i = 1;
                                    foreach ($requests as $row):
                                        $employeeID = $row['requesterEmployeeID'];
                                        $nameQuery = $conn->query("SELECT firstname, middlename, lastname FROM accounts WHERE employeeID = '$employeeID'");
                                        $fullName = $employeeID;
                                        if ($nameQuery && $nameRow = $nameQuery->fetch_assoc()) {
                                            $fullName = $nameRow['firstname'].' '.$nameRow['middlename'].' '.$nameRow['lastname'];
                                        }
                                        $formID = $row['id'];
                                        $materialText = isset($materials[$formID]) ? implode("", $materials[$formID]) : '—';
                                        $row['materialsDetailed'] = isset($materialsDetailed[$formID]) ? implode("", $materialsDetailed[$formID]) : '—';
                                        $teacherInCharge = !empty($row['teacherInCharge']) ? htmlspecialchars($row['teacherInCharge']) : '—';
                                ?>
                                    <tr id="row-<?= $row['id'] ?>">
                                        <td><span style="display:none;"><?= $row['id'] ?></span><?= $i++ ?></td>
                                        <td><?= htmlspecialchars($fullName) ?></td>
                                        <?php if ($statusFilter === 'Approved'): ?><td><?= htmlspecialchars($row['controlNumber']) ?></td><?php endif; ?>
                                        <td><?= htmlspecialchars($row['scilabName']) ?></td>
                                        <td><?= htmlspecialchars("Grade ".$row['gradeLevel']." - ".$row['sections']) ?></td>
                                        <td><?= htmlspecialchars($row['subject']) ?></td>
                                        <td><?= htmlspecialchars($row['subjectTopic']) ?></td>
                                        <td><?= htmlspecialchars($row['inclusiveDate']).' ('.htmlspecialchars($row['inclusiveTime']).')' ?></td>
                                        <td><?= $materialText ?></td>
                                        <td><?= $teacherInCharge ?></td>

                                        <?php if ($statusFilter === 'Approved'): ?>
                                            <td><?= htmlspecialchars($row['feedback'] ?? '—') ?></td>
                                        <?php endif; ?>

                                        <?php if ($statusFilter === 'Rejected'): ?>
                                            <td><?= htmlspecialchars($row['feedback'] ?? 'No reason provided') ?></td>
                                        <?php endif; ?>

                                        <?php if ($statusFilter !== 'Rejected'): ?>
                                            <td>
                                                <?php if ($statusFilter === 'Pending'): ?>
                                                    <a href="supervisor_approve.php?id=<?= $row['id'] ?>" class="btn-liquid" style="width: 90%; margin-bottom: 10px; display:block; text-align:center; padding-left:0; padding-right:0;">Review</a>
                                                    <button class="btn-liquid-success approve-btn" style="width: 90%; margin-bottom: 10px; padding-left:0; padding-right:0;" data-request='<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>'>Force Approve</button>
                                                    <button class="btn-liquid-danger reject-btn" style="width: 90%; padding-left:0; padding-right:0;" data-request='<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>'>Reject</button>
                                                <?php elseif ($statusFilter === 'Approved'): ?>
                                                    <a href="templates/print_template.php?id=<?= $row['id'] ?>" target="_blank" class="btn-liquid">
                                                        <i class="glyphicon glyphicon-print"></i> Print
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Approve Modal -->
            <div class="modal fade" id="approveModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <form id="approveForm">
                            <div class="modal-header">
                                <h5 class="modal-title">Force Approve Request</h5>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                            </div>
                            <div class="modal-body">
                                <div id="admin-conflict-warning"></div>
                                <div id="approveDetails"></div>
                                <div class="form-group mt-3">
                                    <label for="controlNumber">Control Number:</label>
                                    <input type="text" class="form-control liquid-input" name="controlNumber" id="controlNumber" placeholder="Enter Control Number" required>
                                </div>
                                <div class="form-group">
                                    <label for="approveRemarks">Remarks:</label>
                                    <textarea class="form-control liquid-input" name="approveRemarks" id="approveRemarks" placeholder="Enter remarks (optional)"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <input type="hidden" name="approveId" id="approveId">
                                <button type="submit" class="btn-liquid-success">Confirm</button>
                                <button type="button" class="btn-liquid-secondary" data-dismiss="modal">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Reject Modal -->
            <div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Reject Request</h5>
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div id="rejectSummaryContent"></div>
                            <div class="form-group mt-3">
                                <label for="rejectionFeedback"><strong>Reason for Rejection:</strong></label>
                                <textarea id="rejectionFeedback" class="form-control liquid-input" rows="3" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn-liquid-danger" id="confirmReject">Yes, Reject</button>
                            <button type="button" class="btn-liquid-secondary" data-dismiss="modal">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Modal --> 
            <div id="summaryModal" class="modal fade" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title">Request Summary</h4>
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group text-center">
                                <label><strong>Quick Select Timeframe</strong></label>
                                <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                                    <button type="button" class="btn-liquid date-range-btn" data-range="week">Past Week</button>
                                    <button type="button" class="btn-liquid date-range-btn" data-range="month">Past Month</button>
                                    <button type="button" class="btn-liquid date-range-btn" data-range="3-months">Past 3 Months</button>
                                    <button type="button" class="btn-liquid date-range-btn" data-range="year">Past Year</button>
                                </div>
                            </div>
                            <hr>
                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label for="startDate"><strong>Or Select Manually:</strong> Start Date</label>
                                    <input type="date" class="form-control liquid-input" id="startDate">
                                </div>
                                <div class="col-md-6">
                                    <label for="endDate">End Date</label>
                                    <input type="date" class="form-control liquid-input" id="endDate">
                                </div>
                            </div>
                            <div id="summaryContent"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn-liquid-secondary" data-dismiss="modal">Cancel</button>
                            <button type="button" class="btn-liquid" id="generateSummary">Generate Summary</button>
                        </div>
                </div>
            </div>
        </div>

        <?php include 'helperFiles/footer.php'; ?>
    </body>

    <script>
        $(document).ready(function () {
            var table = $('#admin-approval-table').DataTable({
                language: {
                    emptyTable: "No requests found."
                },
                responsive: true,
                pageLength: 10,
                lengthChange: true,
                searching: true,
                ordering: true,
                info: true
            });

            // Check for search parameter in URL
            const urlParams = new URLSearchParams(window.location.search);
            const searchParam = urlParams.get('search');
            if (searchParam) {
                table.search(searchParam).draw();
            }

            // APPROVE BUTTON
            $('.approve-btn').click(function () {
                const data = $(this).data('request');
                $('#approveId').val(data.id);
                $('#controlNumber').val('');
                $('#approveRemarks').val('');
                $('#approveDetails').html(`
                    <p><strong>Requester:</strong> ${data.requesterEmployeeID}</p>
                    <p><strong>Subject:</strong> ${data.subject}</p>
                    <p><strong>Topic:</strong> ${data.subjectTopic}</p>
                    <p><strong>Date of Use:</strong> ${data.inclusiveDate}</p>
                    <p><strong>Time:</strong> ${data.inclusiveTime}</p>
                    <p>
                        <strong>Requested Materials:</strong><br>
                        ${data.materialsDetailed}
                    </p>
                `);
                
                $('#admin-conflict-warning').empty();
                $('#approveForm button[type="submit"]').prop('disabled', false);
                
                // Parse inclusiveTime (e.g. "09:30 to 11:30" or similar depending on AM/PM)
                let timeParts = data.inclusiveTime.split(' to ');
                if (timeParts.length === 2 && data.scilabName && data.inclusiveDate) {
                    $.post('ajax/ajax_forms.php', {
                        action: 'check_conflict',
                        scilabName: data.scilabName,
                        date: data.inclusiveDate,
                        startTime: timeParts[0].trim(),
                        endTime: timeParts[1].trim(),
                        exclude_id: data.id
                    }, function(res) {
                        if (res.status === 'success') {
                            if (res.conflict_type === 'approved') {
                                $('#admin-conflict-warning').html(`
                                    <div class="alert alert-danger" style="margin-bottom:15px; border-radius:8px;">
                                        <strong><i class="glyphicon glyphicon-ban-circle"></i> Severe Conflict:</strong> An <b>approved</b> request already exists for this timeframe (${res.details.time}). Force approving this will double-book the room.
                                    </div>
                                `);
                                $('#approveForm button[type="submit"]').prop('disabled', true);
                            } else if (res.conflict_type === 'pending') {
                                $('#admin-conflict-warning').html(`
                                    <div class="alert alert-warning" style="margin-bottom:15px; border-radius:8px;">
                                        <strong><i class="glyphicon glyphicon-warning-sign"></i> Pending Conflict:</strong> There is another pending request for this timeframe (${res.details.time} - ${res.details.subject}).
                                    </div>
                                `);
                            }
                        }
                    }, 'json');
                }

                $('#approveModal').modal('show');
            });

            $('#approveForm').submit(function (e) {
                e.preventDefault();
                const id = $('#approveId').val();
                const control = $('#controlNumber').val().trim();
                const remarks = $('#approveRemarks').val().trim();

                if (control === '') {
                    showToast('Control Number required.', 'warning');
                    return;
                }

                $.post('ajax/ajax_admin_action.php', { action: 'approve', id: id, controlNumber: control, remarks: remarks }, function (response) {
                    showToast(response, response.toLowerCase().includes('approved') ? 'success' : 'error');
                    location.reload();
                });
            });

            // REJECT BUTTON
            $('.reject-btn').click(function () {
                const data = $(this).data('request');
                $('#rejectSummaryContent').html(`
                    <p><strong>Requester:</strong> ${data.requesterEmployeeID}</p>
                    <p><strong>Subject:</strong> ${data.subject}</p>
                    <p><strong>Topic:</strong> ${data.subjectTopic}</p>
                    <p><strong>Date of Use:</strong> ${data.inclusiveDate}</p>
                    <p><strong>Time:</strong> ${data.inclusiveTime}</p>
                    <p>
                        <strong>Requested Materials:</strong><br>
                        ${data.materialsDetailed}
                    </p>
                `);
                $('#confirmReject').data('id', data.id);
                $('#rejectModal').modal('show');
            });

            $('#confirmReject').click(function () {
                const id = $(this).data('id');
                const feedback = $('#rejectionFeedback').val().trim();
                if (feedback === '') {
                    showToast('Please provide feedback.', 'warning');
                    return;
                }

                $.post('ajax/ajax_admin_action.php', { action: 'reject', id: id, feedback: feedback }, function (response) {
                    showToast(response, response.toLowerCase().includes('rejected') ? 'success' : 'error');
                    location.reload();
                });
            });

            // SUMMARY MODAL
            // Set max date for endDate input to today
            const today = new Date().toISOString().split('T')[0];
            $('#endDate').attr('max', today);

            $('#scanBarcodeBtn').click(function () {
                // Clear previous summary content when modal is opened
                $('#summaryContent').html('');
                $('#startDate').val('');
                $('#endDate').val('');
                $('#summaryModal').modal('show');
            });

            // Quick select buttons for date range
            $('.date-range-btn').click(function() {
                const range = $(this).data('range');
                const endDate = new Date();
                let startDate = new Date();

                switch(range) {
                    case 'week':
                        startDate.setDate(endDate.getDate() - 7);
                        break;
                    case 'month':
                        startDate.setMonth(endDate.getMonth() - 1);
                        break;
                    case '3-months':
                        startDate.setMonth(endDate.getMonth() - 3);
                        break;
                    case 'year':
                        startDate.setFullYear(endDate.getFullYear() - 1);
                        break;
                }

                // Helper to format date as YYYY-MM-DD
                const formatDate = (date) => {
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    return `${year}-${month}-${day}`;
                }

                const formattedStartDate = formatDate(startDate);
                const formattedEndDate = formatDate(endDate);

                $('#startDate').val(formattedStartDate);
                $('#endDate').val(formattedEndDate);
            });

            $('#generateSummary').click(function () {
                const startDate = $('#startDate').val();
                const endDate = $('#endDate').val();

                // Basic validation
                if (!startDate || !endDate) {
                    showToast('Please select both start and end dates.', 'warning');
                    return;
                }

                if (new Date(startDate) > new Date(endDate)) {
                    showToast('Start date cannot be after the end date.', 'warning');
                    return;
                }

                const $btn = $(this);
                const originalText = $btn.html();

                // Show a loading state and disable the button
                $('#summaryContent').html('<p class="text-center mt-3">Generating summary, please wait...</p>');
                $btn.prop('disabled', true).html('<i class="bi bi-arrow-repeat spin"></i> Generating...');

                $.post('ajax/ajax_admin_action.php', {
                    action: 'generate_summary',
                    startDate: startDate,
                    endDate: endDate
                }, function (response) {
                    $btn.prop('disabled', false).html(originalText); // Re-enable button
                    // jQuery automatically parses the JSON response, so we can use it directly.
                    // The 'response' variable is already an object.
                    if (response && response.success) {
                        let content = '';
                        let hasItems = false;
                        
                        // Iterate over the classifications returned from the server
                        for (const classification in response.items) {
                            if (response.items.hasOwnProperty(classification) && response.items[classification].length > 0) {
                                hasItems = true;
                                content += `<h4>${classification}</h4>`;
                                content += '<table class="table table-bordered table-striped mt-2 mb-4"><thead><tr><th>Item</th><th>Description</th><th>Quantity Used</th><th>Requestor</th><th>Date of Use</th></tr></thead><tbody>';
                                response.items[classification].forEach(i => {
                                    const description = i.description || 'N/A';
                                    const unit = i.unit || ''; // Fallback for items with no unit
                                    content += `<tr><td>${i.item}</td><td>${description}</td><td>${i.quantity} ${unit}</td><td>${i.requestor}</td><td>${i.date} (${i.time})</td></tr>`;
                                });
                                content += '</tbody></table>';
                            }
                        }

                        if (hasItems) {
                            const printButtonHtml = '<div class="text-right mb-3"><button type="button" class="btn-liquid-info" id="printSummary">Print Report</button></div>';
                            $('#summaryContent').html(printButtonHtml + content);
                        } else {
                            $('#summaryContent').html('<p class="text-center mt-3">No items were used in the selected date range.</p>');
                        }
                    } else {
                        // Handle cases where success is false or the response is malformed
                        $('#summaryContent').html(`<p class="text-danger mt-3">Error: ${response.message || 'Could not generate summary.'}</p>`);
                    }
                }).fail(function() {
                    $btn.prop('disabled', false).html(originalText);
                    $('#summaryContent').html('<p class="text-danger mt-3">Error: Could not connect to the server.</p>');
                });
            });

            // Use event delegation for the dynamically added print button
            $('#summaryModal').on('click', '#printSummary', function() {
                const startDate = $('#startDate').val();
                const endDate = $('#endDate').val();
                const summaryContent = $('#summaryContent').html();

                // Check if dates are selected and a summary has been generated
                if (!startDate || !endDate) {
                    showToast('Please select a date range before printing.', 'warning');
                    return;
                }
                if (!summaryContent || !summaryContent.includes('<table')) {
                    showToast('Please generate a summary before printing.', 'warning');
                    return;
                }

                const url = `helperFiles/generate_summary_pdf.php?startDate=${startDate}&endDate=${endDate}`;
                window.open(url, '_blank');
            });
        });
    </script>
</html>
