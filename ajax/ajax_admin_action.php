<?php
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    require '../PHPMailer/src/Exception.php';
    require '../PHPMailer/src/PHPMailer.php';
    require '../PHPMailer/src/SMTP.php';

    // centralized db_connection and session_handler
    include('../../scilab/helperFiles/db_connection.php');
    include('../helperFiles/session_handler.php');

    // Get session data
    $email = $_SESSION['email'];
    $username   = $_SESSION['username'];

    function formatTime($time) {
        return date("g:i A", strtotime($time));
    }

    function sendNotificationEmail($conn, $requestID, $status, $controlNumber = null) {
        $stmt = $conn->prepare("SELECT r.*, a.email, a.firstname, a.middlename, a.lastname 
                                FROM scilab_form_requests r
                                JOIN accounts a ON r.requesterEmployeeID = a.employeeID
                                WHERE r.id = ?");
        $stmt->bind_param("i", $requestID);
        $stmt->execute();
        $result = $stmt->get_result();
        if (!$result || $result->num_rows === 0) return;

        $row = $result->fetch_assoc();
        $email = $row['email'];
        $fullName = trim($row['firstname'] . ' ' . $row['middlename'] . ' ' . $row['lastname']);

        // Load the approved.php HTML template
        $templatePath = __DIR__ . '/../templates/approved_email.php';
        $bodyTemplate = file_get_contents($templatePath);

        // Fill in placeholders
        $replacements = [
            '[NAME]' => $fullName,
            '[STATUS]' => '<span style="color:' . (strtoupper($status) === 'APPROVED' ? 'green' : (strtoupper($status) === 'REJECTED' ? 'red' : 'black')) . '">' . strtoupper($status) . '</span>',
            '[Control Number]' => $controlNumber ?? 'N/A',
            '[Facility]' => htmlspecialchars($row['scilabName']),
            '[Grade & Section]' => "Grade {$row['gradeLevel']} - {$row['sections']}",
            '[Subject]' => htmlspecialchars($row['subject']),
            '[Concurrent Topic]' => htmlspecialchars($row['subjectTopic']),
            '[Schedule]' => htmlspecialchars("{$row['inclusiveDate']} at {$row['inclusiveTime']}"),
        ];

        foreach ($replacements as $key => $val) {
            $bodyTemplate = str_replace($key, $val, $bodyTemplate);
        }

        // Send Email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; 
            $mail->SMTPAuth = true;
            $mail->Username = 'pshsircscilab@gmail.com';
            $mail->Password = 'wxzmkkrffptfchcc';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('pshsircscilab@gmail.com', 'SciLab Admin');
            $mail->addAddress($email, $fullName);

            $mail->isHTML(true);
            $mail->Subject = "SciLab Request - " . ucfirst($status);
            $mail->Body    = $bodyTemplate;

            $mail->send();
        } catch (Exception $e) {
            error_log("Email sending failed to {$email}: {$mail->ErrorInfo}");
        }
    }


    if (isset($_POST['action'])) {
        $id = isset($_POST['id']) ? $_POST['id'] : null;

        if ($_POST['action'] === 'check_conflict') {
            $id = $_POST['id'];

            // Get the request's date and time
            $stmt = $conn->prepare("SELECT inclusiveDate, inclusiveTime FROM scilab_form_requests WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 0) {
                echo "not_found";
                exit();
            }
            $request = $result->fetch_assoc();
            $stmt->close();

            $date = $request['inclusiveDate'];
            $timeRange = explode(' to ', $request['inclusiveTime']);
            if (count($timeRange) !== 2) {
                echo "invalid_time_format";
                exit();
            }

            $startTime = strtotime($timeRange[0]);
            $endTime = strtotime($timeRange[1]);

            // Get all other approved requests on the same date
            $stmt = $conn->prepare("SELECT inclusiveTime FROM scilab_form_requests WHERE id != ? AND statusScilabPersonnel = 'Approved' AND inclusiveDate = ?");
            $stmt->bind_param("is", $id, $date);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $otherTimeRange = explode(' to ', $row['inclusiveTime']);
                if (count($otherTimeRange) !== 2) continue;

                $otherStart = strtotime($otherTimeRange[0]);
                $otherEnd = strtotime($otherTimeRange[1]);

                if ($startTime < $otherEnd && $endTime > $otherStart) {
                    echo "conflict";
                    exit();
                }
            }

            echo "no_conflict";
            exit();
        }
        elseif ($_POST['action'] === 'approve') {
            $controlNumber = isset($_POST['controlNumber']) ? (int) $_POST['controlNumber'] : 0;
            $remarks = $_POST['remarks'] ?? '';

            if ($controlNumber <= 0) {
                echo "Invalid control number.";
                exit();
            }

            // ---------------------------------------------------------
            // Get requester type from accounts table
            // ---------------------------------------------------------
            $requesterStmt = $conn->prepare("
                SELECT a.type
                FROM scilab_form_requests r
                INNER JOIN accounts a
                    ON r.requesterEmployeeID = a.employeeID
                WHERE r.id = ?
                LIMIT 1
            ");

            $requesterStmt->bind_param("i", $id);
            $requesterStmt->execute();

            $requesterResult = $requesterStmt->get_result();
            $requester = $requesterResult->fetch_assoc();

            $requesterStmt->close();

            if (!$requester) {
                echo "Requester account not found.";
                exit();
            }

            $requesterType = strtolower(trim($requester['type'] ?? ''));

            // Faculty/staff/sysadmin requests can be fully approved
            $canSkipApproval = in_array($requesterType, [
                'faculty',
                'staff',
                'sysadmin'
            ], true);


            // ---------------------------------------------------------
            // Check for control number duplication
            // ---------------------------------------------------------
            $checkStmt = $conn->prepare("
                SELECT id
                FROM scilab_form_requests
                WHERE controlNumber = ?
                AND id != ?
            ");

            $checkStmt->bind_param("ii", $controlNumber, $id);
            $checkStmt->execute();

            $checkResult = $checkStmt->get_result();

            if ($checkResult->num_rows > 0) {
                echo "Control number already exists.";
                $checkStmt->close();
                exit();
            }

            $checkStmt->close();


            // ---------------------------------------------------------
            // Update approval statuses
            // ---------------------------------------------------------

            if ($canSkipApproval) {

                // FACULTY / STAFF / SYSADMIN
                // Approve all statuses

                $stmt = $conn->prepare("
                    UPDATE scilab_form_requests
                    SET
                        statusScilabPersonnel = 'Approved',
                        supervisor_status = 'approved',
                        subject_teacher_status = 'approved',
                        lab_personnel_status = 'approved',
                        cid_chief_status = 'approved',
                        controlNumber = ?,
                        feedback = ?
                    WHERE id = ?
                ");

            } else {

                // STUDENT / OTHER REQUESTER
                // Do NOT approve SciLab Personnel or CID Chief.
                // Only the other approval steps are approved.

                $stmt = $conn->prepare("
                    UPDATE scilab_form_requests
                    SET
                        supervisor_status = 'approved',
                        subject_teacher_status = 'approved',
                        lab_personnel_status = 'approved',
                        controlNumber = ?,
                        feedback = ?
                    WHERE id = ?
                ");
            }

            $stmt->bind_param("isi", $controlNumber, $remarks, $id);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {

                sendNotificationEmail(
                    $conn,
                    $id,
                    'approved',
                    $controlNumber
                );

                echo "Request approved.";

            } else {

                echo "Update failed.";
            }

            $stmt->close();
            exit();
        }
elseif ($_POST['action'] === 'reject') {
            $feedback = $_POST['feedback'] ?? '';
            $stmt = $conn->prepare("UPDATE scilab_form_requests SET statusScilabPersonnel = 'Rejected', supervisor_status = 'rejected', subject_teacher_status = 'rejected', lab_personnel_status = 'rejected', cid_chief_status = 'rejected', controlNumber = NULL, feedback = ? WHERE id = ?");
            $stmt->bind_param("si", $feedback, $id);

            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                sendNotificationEmail($conn, $id, 'rejected');
                echo "Request rejected.";
            } else {
                echo "Update failed.";
            }
            $stmt->close();
            exit();
        }elseif ($_POST['action'] === 'generate_summary') {
            header('Content-Type: application/json');
            $startDate = $_POST['startDate'] ?? null;
            $endDate = $_POST['endDate'] ?? null;
            $classificationFilter = $_POST['classification'] ?? [];

            if (!$startDate || !$endDate) {
                echo json_encode(['success' => false, 'message' => 'Start and end dates are required.']);
                exit();
            }

            if (!is_array($classificationFilter)) {
                if ($classificationFilter === 'all' || $classificationFilter === '') {
                    $classifications = [];
                } else {
                    $classifications = explode(',', $classificationFilter);
                }
            } else {
                $classifications = $classificationFilter;
            }

            $filterByClassification = !empty($classifications) && !in_array('all', $classifications);

            $sql = "
                SELECT 
                    COALESCE(si.classification, 'Uncategorized') as classification,
                    mr.item, 
                    mr.description,
                    si.unit,
                    mr.quantity,
                    a.firstname,
                    a.middlename,
                    a.lastname,
                    st.firstname AS student_firstname,
                    st.middlename AS student_middlename,
                    st.lastname AS student_lastname,
                    fr.inclusiveDate,
                    fr.inclusiveTime,
                    fr.id AS formID
                FROM scilab_material_requests mr
                JOIN scilab_form_requests fr ON mr.formID = fr.id
                LEFT JOIN scilab_inventory si ON mr.item = si.item
                LEFT JOIN accounts a ON fr.requesterEmployeeID = a.employeeID
                LEFT JOIN student st ON fr.requesterEmployeeID = st.LRN
                WHERE fr.statusScilabPersonnel = 'Approved'
                AND fr.inclusiveDate BETWEEN ? AND ?
            ";

            if ($filterByClassification) {
                $placeholders = implode(',', array_fill(0, count($classifications), '?'));
                $sql .= " AND COALESCE(si.classification, 'Uncategorized') IN ($placeholders) ";
            }

            $sql .= " ORDER BY COALESCE(si.classification, 'Uncategorized') ASC, mr.item ASC, fr.inclusiveDate ASC ";

            $stmt = $conn->prepare($sql);

            if ($stmt === false) {
                echo json_encode(['success' => false, 'message' => 'Database query preparation failed.']);
                exit();
            }

            if ($filterByClassification) {
                $bindTypes = 'ss' . str_repeat('s', count($classifications));
                $bindParams = array_merge([$startDate, $endDate], $classifications);
                $stmt->bind_param($bindTypes, ...$bindParams);
            } else {
                $stmt->bind_param("ss", $startDate, $endDate);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            $categorizedItems = [];
            while ($row = $result->fetch_assoc()) {
                $classification = $row['classification'];
                // Initialize the category array if it doesn't exist
                if (!isset($categorizedItems[$classification])) {
                    $categorizedItems[$classification] = [];
                }
                
                $requestorName = trim(
                    COALESCE($row['firstname'], $row['student_firstname']) . ' ' .
                    COALESCE($row['middlename'], $row['student_middlename']) . ' ' .
                    COALESCE($row['lastname'], $row['student_lastname'])
                );
                
                // Add the item to its category
                $categorizedItems[$classification][] = [
                    'item' => $row['item'], 
                    'description' => $row['description'], 
                    'quantity' => $row['quantity'], 
                    'unit' => $row['unit'],
                    'requestor' => $requestorName,
                    'date' => $row['inclusiveDate'],
                    'time' => $row['inclusiveTime'],
                    'formID' => $row['formID']
                ];
            }

            echo json_encode(['success' => true, 'items' => $categorizedItems]);
            $stmt->close();
            exit();
        }

        $conn->close();
    }
?>