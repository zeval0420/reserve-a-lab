<?php
    include('helperFiles/db_connection.php');
    include('helperFiles/session_handler.php');

    $email = $_SESSION['email'] ?? null;

    if (!isset($_SESSION['role'])) {
        header("Location: index.php");
        exit();
    }

    if ($_SESSION['role'] === 'admin') {
        header("Location: admin_home.php");
        exit();
    }

    // employee id  
    $employeeQuery = $conn->query("SELECT employeeID FROM accounts WHERE email='$email' LIMIT 1");
    $employeeID = $employeeQuery->fetch_assoc()['employeeID'] ?? null;
    if (!$employeeID) die("Employee not found");

    // school year
    $syQuery = $conn->query("
        SELECT value 
        FROM current 
        WHERE description='School Year' 
        ORDER BY id DESC 
        LIMIT 1
    ");
    $currentSY = $syQuery->fetch_assoc()['value'] ?? null;

    // status filter
    $statusFilter = $_GET['status'] ?? 'Pending';

    // requests
    $sql = "
        SELECT *
        FROM scilab_form_requests
        WHERE requesterEmployeeID='$employeeID'
        AND sy='$currentSY'
        AND statusScilabPersonnel='$statusFilter'
        ORDER BY dateRequested DESC
    ";
    $result = $conn->query($sql);

    // counts  
    $counts = ['Pending'=>0,'Approved'=>0,'Rejected'=>0];
    $countQuery = $conn->query("
        SELECT statusScilabPersonnel AS status, COUNT(*) AS total
        FROM scilab_form_requests
        WHERE requesterEmployeeID='$employeeID'
        AND sy='$currentSY'
        GROUP BY statusScilabPersonnel
    ");

    while ($row = $countQuery->fetch_assoc()) {
        $status = ucfirst(strtolower($row['status']));
        if (isset($counts[$status])) {
            $counts[$status] = $row['total'];
        }
    }

    // materials 
    $requests = [];
    $formIDs = [];

    while ($r = $result->fetch_assoc()) {
        $requests[] = $r;
        $formIDs[] = $r['id'];
    }

    $materials = [];
    if ($formIDs) {
        $ids = implode(',', array_map('intval', $formIDs));
        $matQ = $conn->query("
            SELECT formID, quantity, item, description
            FROM scilab_material_requests
            WHERE formID IN ($ids)
        ");

        while ($m = $matQ->fetch_assoc()) {
            $line = $m['quantity']."x ".$m['item'];
            if ($m['description']) {
                $line .= " (".$m['description'].")";
            }
            $materials[$m['formID']][] = $line;
        }
    }
?>

<!DOCTYPE html>
<html>
    <head>
        <title>My Requests</title>
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
            .btn-liquid.active { background: #2B55C4; color: white; }
            .btn-liquid-success.active { background: #28a745; color: white; }
            .btn-liquid-danger.active { background: #dc3545; color: white; }
            .btn-liquid .badge, .btn-liquid-success .badge, .btn-liquid-danger .badge { margin-right: 5px; background: rgba(0,0,0,0.1); color: inherit; }
            .btn-liquid.active .badge, .btn-liquid-success.active .badge, .btn-liquid-danger.active .badge { background: rgba(255,255,255,0.2); color: white; }

            @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
            .spin { animation: spin 1s linear infinite; display: inline-block; }
        </style>
    </head>

    <body>
        <?php include('helperFiles/header.php'); ?>

        <div class="main-wrapper">
            <div class="form-container">

                <h2>My Requests</h2>

                <br>

                <div class="status-buttons mb-3">
                    <a href="?status=Pending" class="btn-liquid <?= $statusFilter==='Pending'?'active':'' ?>">
                        <span class="badge badge-secondary"><?= $counts['Pending'] ?></span> Pending
                    </a>
                    <a href="?status=Approved" class="btn-liquid-success <?= $statusFilter==='Approved'?'active':'' ?>">
                        <span class="badge badge-secondary"><?= $counts['Approved'] ?></span> Approved
                    </a>
                    <a href="?status=Rejected" class="btn-liquid-danger <?= $statusFilter==='Rejected'?'active':'' ?>">
                        <span class="badge badge-secondary"><?= $counts['Rejected'] ?></span> Rejected
                    </a>
                </div>

                <br>

                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="requests-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <?php if ($statusFilter === 'Approved'): ?>
                                    <th>Control #</th>
                                <?php endif; ?>
                                <th>Lab Name</th>
                                <th>Grade - Section/s</th>
                                <th>Subject</th>
                                <th>Topic</th>
                                <th>Date of Use</th>
                                <th>Materials</th>
                                <th>Teacher-in-Charge</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; foreach ($requests as $row): ?>
                            <tr>
                                <td><?= $i++ ?></td>

                                <?php if ($statusFilter === 'Approved'): ?>
                                    <td><?= htmlspecialchars($row['controlNumber']) ?></td>
                                <?php endif; ?>

                                <td><?= htmlspecialchars($row['scilabName']) ?></td>
                                <td><?= "Grade {$row['gradeLevel']} - {$row['sections']}" ?></td>
                                <td><?= htmlspecialchars($row['subject']) ?></td>
                                <td><?= htmlspecialchars($row['subjectTopic']) ?></td>
                                <td><?= htmlspecialchars($row['inclusiveDate'])." (".$row['inclusiveTime'].")" ?></td>
                                <td><?= isset($materials[$row['id']]) ? implode(', ', $materials[$row['id']]) : '—' ?></td>
                                <td><?= !empty($row['teacherInCharge']) ? htmlspecialchars($row['teacherInCharge']) : '—' ?></td>

                                <td>
                                    <button
                                        class="btn-liquid view-btn"
                                        data-status="<?= $statusFilter ?>"
                                        data-request='<?= json_encode($row) ?>'
                                        data-materials='<?= json_encode($materials[$row['id']] ?? []) ?>'
                                    >
                                        View
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

        <!-- APPROVED MODAL -->
        <div class="modal fade" id="approveModal">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Approved Request</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <h4 id="approveControlNumber"></h4>
                        <div id="approveDetails"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- REJECTED MODAL -->
        <div class="modal fade" id="rejectModal">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Rejected Request</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div id="rejectDetails"></div>
                        <label>Feedback</label>
                        <textarea id="rejectFeedback" class="form-control" readonly></textarea>
                    </div>
                </div>
            </div>
        </div>

        <?php include('helperFiles/footer.php'); ?>
    </body>

    <script>
        $(function () {
            $('#requests-table').DataTable({
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

            $('.view-btn').on('click', function () {

                const data = $(this).data('request');
                const materials = $(this).data('materials');
                const status = $(this).data('status');

                const materialText = materials.length ? materials.join(', ') : '—';
                const teacher = data.teacherInCharge ? data.teacherInCharge : '—';

                const html = `
                    <p><strong>Lab:</strong> ${data.scilabName}</p>
                    <p><strong>Grade - Section:</strong> Grade ${data.gradeLevel} - ${data.sections}</p>
                    <p><strong>Subject:</strong> ${data.subject}</p>
                    <p><strong>Topic:</strong> ${data.subjectTopic}</p>
                    <p><strong>Date of Use:</strong> ${data.inclusiveDate} (${data.inclusiveTime})</p>
                    <p><strong>Materials:</strong> ${materialText}</p>
                    <p><strong>Teacher-in-Charge:</strong> ${teacher}</p>
                `;

                if (status === 'Approved') {
                    $('#approveControlNumber').text('Control #: ' + data.controlNumber);
                    $('#approveDetails').html(html);
                    $('#approveModal').modal('show');
                }

                if (status === 'Rejected') {
                    $('#rejectDetails').html(html);
                    $('#rejectFeedback').val(data.feedback || '—');
                    $('#rejectModal').modal('show');
                }

                if (status === 'Pending') {
                    window.location.href = 'supervisor_approve.php?id=' + data.id;
                }
            });
        });
    </script>
</html>
