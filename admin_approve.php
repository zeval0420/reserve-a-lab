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
    if (count($formIDs) > 0) {
        $idList = implode(",", array_map('intval', $formIDs));
        $matResult = $conn->query("SELECT formID, quantity, item, description FROM scilab_material_requests WHERE formID IN ($idList)");

        while ($mat = $matResult->fetch_assoc()) {
            $line = $mat['quantity'] . "x " . $mat['item'];
            if (!empty($mat['description'])) $line .= " (" . $mat['description'] . ")";
            $materials[$mat['formID']][] = $line;
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
                    <a href="?status=Pending" class="btn <?= $statusFilter === 'Pending' ? 'btn-primary' : 'btn-outline-primary' ?>">
                        <span class="badge badge-pill badge-secondary"><?= $counts['Pending'] ?></span> Pending
                    </a>
                    <a href="?status=Approved" class="btn <?= $statusFilter === 'Approved' ? 'btn-success' : 'btn-outline-success' ?>">
                        <span class="badge badge-pill badge-secondary"><?= $counts['Approved'] ?></span> Approved
                    </a>
                    <a href="?status=Rejected" class="btn <?= $statusFilter === 'Rejected' ? 'btn-danger' : 'btn-outline-danger' ?>">
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
                                <th>Materials</th>
                                <th>Teacher-in-Charge</th>
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
                                        $materialText = isset($materials[$formID]) ? implode(", ", $materials[$formID]) : '—';
                                        $teacherInCharge = !empty($row['teacherInCharge']) ? htmlspecialchars($row['teacherInCharge']) : '—';
                                ?>
                                    <tr id="row-<?= $row['id'] ?>">
                                        <td><?= $i++ ?></td>
                                        <td><?= htmlspecialchars($fullName) ?></td>
                                        <?php if ($statusFilter === 'Approved'): ?><td><?= htmlspecialchars($row['controlNumber']) ?></td><?php endif; ?>
                                        <td><?= htmlspecialchars($row['scilabName']) ?></td>
                                        <td><?= htmlspecialchars("Grade ".$row['gradeLevel']." - ".$row['section/s']) ?></td>
                                        <td><?= htmlspecialchars($row['subject']) ?></td>
                                        <td><?= htmlspecialchars($row['subjectTopic']) ?></td>
                                        <td><?= htmlspecialchars($row['inclusiveDate']).' ('.htmlspecialchars($row['inclusiveTime']).')' ?></td>
                                        <td><?= htmlspecialchars($materialText) ?></td>
                                        <td><?= $teacherInCharge ?></td>

                                        <?php if ($statusFilter === 'Rejected'): ?>
                                            <td><?= htmlspecialchars($row['feedback'] ?? 'No reason provided') ?></td>
                                        <?php endif; ?>

                                        <?php if ($statusFilter !== 'Rejected'): ?>
                                            <td>
                                                <?php if ($statusFilter === 'Pending'): ?>
                                                    <button class="btn btn-success btn-sm approve-btn" style="width: 90%;  margin-bottom: 10px; border-radius: 6767px;" data-request='<?= json_encode($row) ?>'>Approve</button>
                                                    <button class="btn btn-danger btn-sm reject-btn" style="width: 90%; border-radius: 6767px;" data-request='<?= json_encode($row) ?>'>Reject</button>
                                                <?php elseif ($statusFilter === 'Approved'): ?>
                                                    <a href="ajax/generate_pdf.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-default btn-sm">
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
                                <h5 class="modal-title">Approve Request</h5>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                            </div>
                            <div class="modal-body" id="approveDetails"></div>
                            <div class="modal-footer">
                                <input type="hidden" name="approveId" id="approveId">
                                <input type="text" class="form-control" name="controlNumber" id="controlNumber" placeholder="Enter Control Number" required>
                                <br>
                                <button type="submit" class="btn btn-success">Confirm</button>
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
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
                                <textarea id="rejectionFeedback" class="form-control" rows="3" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" id="confirmReject">Yes, Reject</button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
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
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-primary date-range-btn" data-range="week">Past Week</button>
                                    <button type="button" class="btn btn-outline-primary date-range-btn" data-range="month">Past Month</button>
                                    <button type="button" class="btn btn-outline-primary date-range-btn" data-range="3-months">Past 3 Months</button>
                                    <button type="button" class="btn btn-outline-primary date-range-btn" data-range="year">Past Year</button>
                                </div>
                            </div>
                            <hr>
                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label for="startDate"><strong>Or Select Manually:</strong> Start Date</label>
                                    <input type="date" class="form-control" id="startDate">
                                </div>
                                <div class="col-md-6">
                                    <label for="endDate">End Date</label>
                                    <input type="date" class="form-control" id="endDate">
                                </div>
                            </div>
                            <div id="summaryContent"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="generateSummary">Generate Summary</button>
                        </div>
                </div>
            </div>
        </div>

        <?php include 'helperFiles/footer.php'; ?>
    </body>

    <script>
        $(document).ready(function () {
            $('#admin-approval-table').DataTable({
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
            // APPROVE BUTTON
            $('.approve-btn').click(function () {
                const data = $(this).data('request');
                $('#approveId').val(data.id);
                $('#approveDetails').html(`
                    <p><strong>Requester:</strong> ${data.requesterEmployeeID}</p>
                    <p><strong>Subject:</strong> ${data.subject}</p>
                    <p><strong>Topic:</strong> ${data.subjectTopic}</p>
                    <p><strong>Date of Use:</strong> ${data.inclusiveDate}</p>
                    <p><strong>Time:</strong> ${data.inclusiveTime}</p>

                    <p><strong>Materials:</strong> ${data.inclusiveTime}</p>
                `);
                $('#approveModal').modal('show');
            });

            $('#approveForm').submit(function (e) {
                e.preventDefault();
                const id = $('#approveId').val();
                const control = $('#controlNumber').val().trim();

                if (control === '') {
                    alert('Control Number required.');
                    return;
                }

                $.post('ajax/ajax_admin_action.php', { action: 'approve', id: id, controlNumber: control }, function (response) {
                    alert(response);
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
                `);
                $('#confirmReject').data('id', data.id);
                $('#rejectModal').modal('show');
            });

            $('#confirmReject').click(function () {
                const id = $(this).data('id');
                const feedback = $('#rejectionFeedback').val().trim();
                if (feedback === '') {
                    alert('Please provide feedback.');
                    return;
                }

                $.post('ajax/ajax_admin_action.php', { action: 'reject', id: id, feedback: feedback }, function (response) {
                    alert(response);
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
                    alert('Please select both start and end dates.');
                    return;
                }

                if (new Date(startDate) > new Date(endDate)) {
                    alert('Start date cannot be after the end date.');
                    return;
                }

                // Show a loading state and disable the button
                $('#summaryContent').html('<p class="text-center mt-3">Generating summary, please wait...</p>');
                $(this).prop('disabled', true);

                $.post('ajax/ajax_admin_action.php', {
                    action: 'generate_summary',
                    startDate: startDate,
                    endDate: endDate
                }, function (response) {
                    $('#generateSummary').prop('disabled', false); // Re-enable button
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
                                content += '<table class="table table-bordered table-striped mt-2 mb-4"><thead><tr><th>Item</th><th>Description</th><th>Total Quantity Used</th></tr></thead><tbody>';
                                response.items[classification].forEach(i => {
                                    const description = i.description || 'N/A';
                                    const unit = i.unit || ''; // Fallback for items with no unit
                                    content += `<tr><td>${i.item}</td><td>${description}</td><td>${i.total_quantity} ${unit}</td></tr>`;
                                });
                                content += '</tbody></table>';
                            }
                        }

                        if (hasItems) {
                            const printButtonHtml = '<div class="text-right mb-3"><button type="button" class="btn btn-info" id="printSummary">Print Report</button></div>';
                            $('#summaryContent').html(printButtonHtml + content);
                        } else {
                            $('#summaryContent').html('<p class="text-center mt-3">No items were used in the selected date range.</p>');
                        }
                    } else {
                        // Handle cases where success is false or the response is malformed
                        $('#summaryContent').html(`<p class="text-danger mt-3">Error: ${response.message || 'Could not generate summary.'}</p>`);
                    }
                }).fail(function() {
                    $('#generateSummary').prop('disabled', false);
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
                    alert('Please select a date range before printing.');
                    return;
                }
                if (!summaryContent || !summaryContent.includes('<table')) {
                    alert('Please generate a summary before printing.');
                    return;
                }

                const url = `helperFiles/generate_summary_pdf.php?startDate=${startDate}&endDate=${endDate}`;
                window.open(url, '_blank');
            });
        });
    </script>
</html>
