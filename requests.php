<?php
    include('helperFiles/db_connection.php');
    include('helperFiles/session_handler.php');

    $email = $_SESSION['email'] ?? null;
    $username = $_SESSION['username'] ?? null;

    if(isset($_SESSION['role'])) {
        if($_SESSION['role'] === 'admin') header("Location: admin_home.php");
    } else {
        header("Location: index.php");
        exit();
    }

    // Fetch employee ID
    $employeeID = null;
    if ($email) {
        $empQuery = $conn->query("SELECT employeeID FROM accounts WHERE email = '$email' LIMIT 1");
        $employeeID = ($empQuery && $empQuery->num_rows > 0) ? $empQuery->fetch_assoc()['employeeID'] : die("Error: Employee ID not found.");
    }

    // Current school year
    $syResult = $conn->query("SELECT value FROM current WHERE description='School Year' ORDER BY id DESC LIMIT 1");
    $currentSY = ($syResult && $syResult->num_rows > 0) ? $syResult->fetch_assoc()['value'] : null;

    // User requests
    $userRequests = ($employeeID && $currentSY) ? 
        $conn->query("SELECT * FROM scilab_form_requests WHERE requesterEmployeeID='$employeeID' AND sy='$currentSY' ORDER BY dateRequested DESC") 
        : null;

    // Mark approved/rejected as seen
    if ($employeeID && $currentSY) {
        $seenQuery = $conn->query("SELECT id FROM scilab_form_requests WHERE requesterEmployeeID='$employeeID' AND statusScilabPersonnel IN ('Approved','Rejected') AND sy='$currentSY'");
        if (!$seenQuery) die("Error fetching seen requests: " . $conn->error);
        $_SESSION['seen_requests'] = array_column($seenQuery->fetch_all(MYSQLI_ASSOC), 'id');
    }
?>

<!DOCTYPE html>
<html>
    <head>
        <title>My Requests</title>
        <?php include('helperFiles/headData.php'); ?>
        <style>
            body { background-color: #f5f5f5; }
            .form-title {
                width: 95%; margin: 20px auto; display: flex; justify-content: center; align-items: center;
                background: linear-gradient(#0e005475,#0036af75,#0e005475), url(img/laboratoryBackground.jpg) center/cover no-repeat;
                padding: 30px 8%; border-radius: 10px; text-align: center;
            }
            .form-title h4 { color: white; font-size: 3rem; font-weight: bold; }
            .form-container {
                background-color: #fff; padding: 25px; margin: 25px auto; border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.05); width: 95%; min-height: 60vh;
            }
            .table th { background-color: #2B55C4; color: white; text-align: center; white-space: nowrap; }
            table tbody td { white-space: normal !important; word-wrap: break-word; vertical-align: top; max-width:200px; }
            .date-pick { margin-left: 20px; }

            .btn-liquid {
                display: inline-block;
                padding: 6px 18px;
                color: #2B55C4;
                background: linear-gradient(135deg, rgba(43, 85, 196, 0.05), rgba(43, 85, 196, 0.15));
                backdrop-filter: blur(8px);
                -webkit-backdrop-filter: blur(8px);
                border: 1px solid rgba(43, 85, 196, 0.2);
                border-radius: 20px;
                font-weight: 600;
                font-size: 13px;
                text-decoration: none;
                box-shadow: 0 4px 12px rgba(43, 85, 196, 0.1);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                position: relative;
                overflow: hidden;
            }

            .btn-liquid:hover {
                background: linear-gradient(135deg, rgba(43, 85, 196, 0.1), rgba(43, 85, 196, 0.25));
                transform: translateY(-2px);
                box-shadow: 0 8px 20px rgba(43, 85, 196, 0.2);
                color: #1a3a8f;
                text-decoration: none;
                border-color: rgba(43, 85, 196, 0.3);
            }

            .btn-liquid:active {
                transform: translateY(0);
                box-shadow: 0 2px 8px rgba(43, 85, 196, 0.1);
            }
        </style>
    </head>

    <body>
        <?php include('helperFiles/header.php'); ?>

        <div class="main-wrapper">
            <div class="form-title"><h4>MY SCI-LAB REQUESTS</h4></div>
            <div class="form-container">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="client-requests-table">
                        <thead>
                            <tr>
                                <th>#</th><th>Lab Name</th><th>Grade - Section</th><th>Subject</th>
                                <th>Topic</th><th>Date of Use</th><th>Time</th><th>Status</th><th>Process</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($userRequests && $userRequests->num_rows > 0):
                                $i=1; while($row = $userRequests->fetch_assoc()):
                                    $status = $row['statusScilabPersonnel'];
                                    $labelClass = match($status) {
                                        'Approved' => 'label-success',
                                        'Rejected' => 'label-danger',
                                        default => strtolower($status) === 'pending' ? 'label-warning' : 'label-default',
                                    };
                            ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= htmlspecialchars($row['scilabName']) ?></td>
                                <td><?= htmlspecialchars("Grade {$row['gradeLevel']} - {$row['sections']}") ?></td>
                                <td><?= htmlspecialchars($row['subject']) ?></td>
                                <td><?= htmlspecialchars($row['subjectTopic']) ?></td>
                                <td><?= htmlspecialchars($row['inclusiveDate']) ?></td>
                                <td><?= htmlspecialchars($row['inclusiveTime']) ?></td>
                                <td><span class="label <?= $labelClass ?>" style="font-size:12px;text-transform:uppercase;padding:5px;"><?= htmlspecialchars($status) ?></span></td>
                                <td><a href="supervisor_approve.php?id=<?= $row['id'] ?>" class="btn-liquid">View</a></td>
                            </tr>
                            <?php endwhile; else: ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php include 'helperFiles/footer.php'; ?>
    </body>

    <script>
        $(document).ready(() => {
            $('#client-requests-table').DataTable({ language: { emptyTable: "No requests found." } });

        });
    </script>
</html>
