<?php
    include('../scilab/helperFiles/db_connection.php');
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

    // Determine User ID based on role
    $userID = null;
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'student') {
        $stmt = $conn->prepare("SELECT LRN FROM student_directory WHERE studentEmail = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $userID = $stmt->get_result()->fetch_assoc()['LRN'] ?? null;
    } else {
        $stmt = $conn->prepare("SELECT employeeID FROM accounts WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $userID = $stmt->get_result()->fetch_assoc()['employeeID'] ?? null;
    }

    if (!$userID) die("User ID not found");

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

    // ===== UPDATED: Time frame / manual date filter =====
        $filterMode = $_GET['filterMode'] ?? 'timeframe';

        // Only allow these two modes
        if (!in_array($filterMode, ['timeframe', 'manual'])) {
            $filterMode = 'timeframe';
        }

        $timeFrame = $_GET['timeframe'] ?? 'month';

        $timeFrameDays = [
            'month' => 30,
            '3months' => 90,
            'year' => 365
        ];

        if (!isset($timeFrameDays[$timeFrame])) {
            $timeFrame = 'month';
        }

        $days = $timeFrameDays[$timeFrame];

        $fromDate = $_GET['fromDate'] ?? '';
        $toDate = $_GET['toDate'] ?? '';

        // ===== UPDATED: Build date filter =====
        if (
            $filterMode === 'manual' &&
            !empty($fromDate) &&
            !empty($toDate)
        ) {

            // From Date starts at 12:00 AM
            // To Date includes the entire day
            $dateFilter = "
                AND dateRequested >= '$fromDate 00:00:00'
                AND dateRequested < DATE_ADD('$toDate', INTERVAL 1 DAY)
            ";

        } elseif ($filterMode === 'manual') {

            // Manual mode but dates have not been selected yet
            // Do not display any requests
            $dateFilter = "AND 1 = 0";

        } else {

            // Automatic timeframe mode
            $dateFilter = "
                AND dateRequested >= DATE_SUB(NOW(), INTERVAL $days DAY)
            ";
        }
        // ===== END UPDATED =====

    // requests
    $sql = "
        SELECT *
        FROM scilab_form_requests
        WHERE requesterEmployeeID='$userID'
        AND sy='$currentSY'
        AND statusScilabPersonnel='$statusFilter'
        $dateFilter
        ORDER BY dateRequested DESC
    ";
    $result = $conn->query($sql);

    // counts  
    $counts = ['Pending'=>0,'Approved'=>0,'Rejected'=>0];
    $countQuery = $conn->query("
        SELECT statusScilabPersonnel AS status, COUNT(*) AS total
        FROM scilab_form_requests
        WHERE requesterEmployeeID='$userID'
        AND sy='$currentSY'
        $dateFilter
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
        $statuses = [
            strtolower($r['supervisor_status'] ?? 'pending'),
            strtolower($r['subject_teacher_status'] ?? 'pending'),
            strtolower($r['lab_personnel_status'] ?? 'pending'),
            strtolower($r['cid_chief_status'] ?? 'pending'),
        ];
        if (in_array('rejected', $statuses)) {
            $r['_approval_status'] = 'Rejected';
        } elseif (count(array_unique($statuses)) === 1 && $statuses[0] === 'approved') {
            $r['_approval_status'] = 'Approved';
        } else {
            $r['_approval_status'] = 'Pending';
        }
        $requests[] = $r;
        $formIDs[] = $r['id'];
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

            // ✅ TABLE VERSION (simple) — store as array for show-all toggle
            $materials[$m['formID']][] = $m;  // store full row

            // ✅ MODAL VERSION (detailed)
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
            .badge { padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: 600; }
            .badge-success { background: #28a745; color: #fff; }
            .badge-danger { background: #dc3545; color: #fff; }
            .badge-warning { background: #ffc107; color: #212529; }
            .toggle-materials { font-size: 12px; color: #2B55C4; cursor: pointer; text-decoration: none; }
            .toggle-materials:hover { text-decoration: underline; }
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

            .material-line-detailed .material-text {flex: 1;line-height: 1.4;}

            /* ===== ADDED: Time frame buttons ===== */

            .requests-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                width: 100%;
                margin-bottom: 15px;
            }

            .requests-header h2 {
                margin: 0;
            }

            .timeframe-buttons {
                display: flex;
                justify-content: flex-end;
                gap: 8px;
                margin-bottom: 15px;
                border-color: #2B55C4;
            }

              /* ===== ADDED: Timeframe header and toggle ===== */
                .timeframe-header {
                    display: flex;
                    justify-content: flex-end;
                    align-items: center;
                    gap: 10px;
                    margin-bottom: 12px;
                }

                .timeframe-title {
                    font-size: 15px;
                    font-weight: 600;
                    color: #333;
                    margin: 0;
                }

                /* Pill toggle */
                .timeframe-toggle {
                    position: relative;
                    display: inline-block;
                    width: 48px;
                    height: 24px;
                }

                .timeframe-toggle input {
                    opacity: 0;
                    width: 0;
                    height: 0;
                }

                .timeframe-slider {
                    position: absolute;
                    cursor: pointer;
                    inset: 0;
                    background-color: #ccc;
                    border-radius: 24px;
                    transition: 0.2s;
                }

                .timeframe-slider:before {
                    content: "";
                    position: absolute;
                    height: 18px;
                    width: 18px;
                    left: 3px;
                    top: 3px;
                    background-color: white;
                    border-radius: 50%;
                    transition: 0.2s;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
                }

                .timeframe-toggle input:checked + .timeframe-slider {
                    background-color: #2B55C4;
                }

                .timeframe-toggle input:checked + .timeframe-slider:before {
                    transform: translateX(24px);
                }

                .manual-date-container {
                    display: flex;
                    justify-content: flex-end;
                    align-items: flex-end;
                    gap: 10px;
                    margin-bottom: 15px;
                }

                .manual-date-group {
                    display: flex;
                    flex-direction: column;
                    gap: 4px;
                }

                .manual-date-group label {
                    font-size: 12px;
                    font-weight: 600;
                    color: #555;
                }

                .manual-date-group input {
                    font-size: 13px;
                    padding: 6px 8px;
                    border: 1px solid #ccc;
                    border-radius: 5px;
                }

                .manual-date-submit {
                    height: 34px;
                    padding: 5px 14px;
                    border: none;
                    border-radius: 5px;
                    background-color: #2B55C4;
                    color: white;
                    cursor: pointer;
                }

                .manual-date-submit:hover {
                    background-color: #21449f;
                }

                @media (max-width: 768px) {
                    .timeframe-header {
                        align-items: flex-start;
                        gap: 10px;
                    }

                    .manual-date-container {
                        justify-content: flex-start;
                        flex-wrap: wrap;
                    }
                }
                /* ===== END ADDED ===== */

            .timeframe-buttons .btn {
                font-size: 13px;
                padding: 6px 12px;
                border-color: #2B55C4;
            }

            .timeframe-buttons .btn.active {
                background: #2B55C4;
                color: white;
                border-color: #2B55C4;
            }

            @media (max-width: 768px) {
                .timeframe-buttons {
                    justify-content: flex-start;
                    flex-wrap: wrap;
                }
            }
            /* ===== END ADDED ===== */
        </style>
    </head>

    <body>
        <?php include('helperFiles/header.php'); ?>

        <div class="main-wrapper">
            <div class="form-container">

                <h2>My Requests</h2>

                <!-- ===== ADDED: Timeframe controls ===== -->
                <div class="timeframe-header">

                    <h5 class="timeframe-title">
                        Display Past Requests within Timeframe:
                    </h5>

                    <!-- ===== UPDATED: Timeframe toggle ===== -->
                    <form method="GET" id="filterModeForm">

                        <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
                        <input type="hidden" name="timeframe" value="<?= htmlspecialchars($timeFrame) ?>">
                        <input type="hidden" name="fromDate" value="<?= htmlspecialchars($fromDate) ?>">
                        <input type="hidden" name="toDate" value="<?= htmlspecialchars($toDate) ?>">

                        <!-- This hidden field ALWAYS gets submitted -->
                        <input
                            type="hidden"
                            name="filterMode"
                            id="filterMode"
                            value="<?= htmlspecialchars($filterMode) ?>"
                        >

                        <label class="timeframe-toggle">
                            <input
                                type="checkbox"
                                id="timeframeToggle"
                                <?= $filterMode === 'timeframe' ? 'checked' : '' ?>
                            >
                            <span class="timeframe-slider"></span>
                        </label>

                    </form>
                    <!-- ===== END UPDATED ===== -->
                </div>


                <?php if ($filterMode === 'timeframe'): ?>

                    <!-- ===== ON: Simple timeframe buttons ===== -->
                    <div class="timeframe-buttons">

                        <a href="?status=<?= urlencode($statusFilter) ?>&filterMode=timeframe&timeframe=month"
                        class="btn btn-outline-primary <?= $timeFrame === 'month' ? 'active' : '' ?>">
                            Month
                        </a>

                        <a href="?status=<?= urlencode($statusFilter) ?>&filterMode=timeframe&timeframe=3months"
                        class="btn btn-outline-primary <?= $timeFrame === '3months' ? 'active' : '' ?>">
                            3 Months
                        </a>

                        <a href="?status=<?= urlencode($statusFilter) ?>&filterMode=timeframe&timeframe=year"
                        class="btn btn-outline-primary <?= $timeFrame === 'year' ? 'active' : '' ?>">
                            Year
                        </a>

                    </div>

                <?php else: ?>

                    <!-- ===== OFF: Manual date range ===== -->
                    <form method="GET" class="manual-date-container">

                        <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
                        <input type="hidden" name="filterMode" value="manual">

                        <div class="manual-date-group">
                            <label for="fromDate">From Date</label>
                            <input
                                type="date"
                                id="fromDate"
                                name="fromDate"
                                value="<?= htmlspecialchars($fromDate) ?>"
                                required
                            >
                        </div>

                        <div class="manual-date-group">
                            <label for="toDate">To Date</label>
                            <input
                                type="date"
                                id="toDate"
                                name="toDate"
                                value="<?= htmlspecialchars($toDate) ?>"
                                required
                            >
                        </div>

                        <button type="submit" class="manual-date-submit">
                            Apply
                        </button>

                    </form>

                <?php endif; ?>

                <!-- ===== END ADDED ===== -->

                <br>

                <div class="status-buttons mb-3">
                    <a href="?status=Pending&filterMode=<?= urlencode($filterMode) ?>&timeframe=<?= urlencode($timeFrame) ?>&fromDate=<?= urlencode($fromDate) ?>&toDate=<?= urlencode($toDate) ?>"
                    class="btn-liquid <?= $statusFilter==='Pending'?'active':'' ?>">
                        <span class="badge badge-secondary"><?= $counts['Pending'] ?></span> Pending
                    </a>

                    <a href="?status=Approved&filterMode=<?= urlencode($filterMode) ?>&timeframe=<?= urlencode($timeFrame) ?>&fromDate=<?= urlencode($fromDate) ?>&toDate=<?= urlencode($toDate) ?>"
                    class="btn-liquid-success <?= $statusFilter==='Approved'?'active':'' ?>">
                        <span class="badge badge-secondary"><?= $counts['Approved'] ?></span> Approved
                    </a>

                    <a href="?status=Rejected&filterMode=<?= urlencode($filterMode) ?>&timeframe=<?= urlencode($timeFrame) ?>&fromDate=<?= urlencode($fromDate) ?>&toDate=<?= urlencode($toDate) ?>"
                    class="btn-liquid-danger <?= $statusFilter==='Rejected'?'active':'' ?>">
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
                                <th>Requested Materials</th>
                                <th>Teacher-in-Charge</th>
                                <th>Approval Status</th>
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
                                <?php
                                    $allMats = $materials[$row['id']] ?? [];
                                    $totalMats = count($allMats);
                                    $visibleMats = array_slice($allMats, 0, 3);
                                    $hiddenMats = array_slice($allMats, 3);

                                    $materialText = '';
                                    foreach ($visibleMats as $m) {
                                        $item = htmlspecialchars($m['item']);
                                        $materialText .= "
                                            <div class='material-line'>
                                                <span class='bullet'>•</span>
                                                <span class='material-text'>{$item}</span>
                                            </div>
                                        ";
                                    }
                                    if ($hiddenMats) {
                                        $materialText .= "
                                            <div class='materials-hidden' style='display:none;'>
                                        ";
                                        foreach ($hiddenMats as $m) {
                                            $item = htmlspecialchars($m['item']);
                                            $materialText .= "
                                                <div class='material-line'>
                                                    <span class='bullet'>•</span>
                                                    <span class='material-text'>{$item}</span>
                                                </div>
                                            ";
                                        }
                                        $materialText .= "</div>";
                                        $materialText .= "
                                            <a href='javascript:void(0)' class='toggle-materials' data-formid='{$row['id']}'>
                                                Show all ({$totalMats})
                                            </a>
                                        ";
                                    }
                                    $materialText = $totalMats ? $materialText : '—';

                                    $materialsDetailedText = isset($materialsDetailed[$row['id']]) 
                                        ? implode("", $materialsDetailed[$row['id']]) 
                                        : '—';
                                ?>
                                <td><?= $materialText ?></td>
                                <td><?= !empty($row['teacherInCharge']) ? htmlspecialchars($row['teacherInCharge']) : '—' ?></td>

                                <td>
                                    <?php $as = $row['_approval_status']; ?>
                                    <span class="badge badge-<?= $as === 'Approved' ? 'success' : ($as === 'Rejected' ? 'danger' : 'warning') ?>">
                                        <?= $as ?>
                                    </span>
                                </td>

                                <td>
                                    <button
                                        class="btn-liquid view-btn"
                                        data-status="<?= $statusFilter ?>"
                                        data-request='<?= json_encode($row) ?>'
                                        data-materials-detailed='<?= htmlspecialchars($materialsDetailedText, ENT_QUOTES, 'UTF-8') ?>'
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
                const status = $(this).data('status');

                const materialText = $(this).data('materials-detailed') || '—';
                const teacher = data.teacherInCharge ? data.teacherInCharge : '—';

                const html = `
                    <p><strong>Lab:</strong> ${data.scilabName}</p>
                    <p><strong>Grade - Section:</strong> Grade ${data.gradeLevel} - ${data.sections}</p>
                    <p><strong>Subject:</strong> ${data.subject}</p>
                    <p><strong>Topic:</strong> ${data.subjectTopic}</p>
                    <p><strong>Date of Use:</strong> ${data.inclusiveDate} (${data.inclusiveTime})</p>
                    <p>
                        <strong>Requested Materials:</strong><br>
                        ${materialText}
                    </p>
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

            $(document).on('click', '.toggle-materials', function () {
                const $link = $(this);
                const $hidden = $link.siblings('.materials-hidden');
                if ($hidden.is(':visible')) {
                    $hidden.hide();
                    $link.text('Show all (' + $hidden.children('.material-line').length + ')');
                } else {
                    $hidden.show();
                    $link.text('Show less');
                }
            });
        });
        // ===== ADDED: Timeframe toggle =====
        $('#timeframeToggle').on('change', function () {

            if ($(this).is(':checked')) {
                $('#filterMode').val('timeframe');
            } else {
                $('#filterMode').val('manual');
            }

            $('#filterModeForm').submit();
        });
        // ===== END ADDED ======
    </script>
</html>
