<?php
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    require '../PHPMailer/src/Exception.php';
    require '../PHPMailer/src/PHPMailer.php';
    require '../PHPMailer/src/SMTP.php';

    // centralized db_connection and session_handler
    include('../scilab/../helperFiles/db_connection.php');
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
        }elseif ($_POST['action'] === 'approve') {
            $controlNumber = isset($_POST['controlNumber']) ? (int) $_POST['controlNumber'] : 0;
            $remarks = $_POST['remarks'] ?? '';

            if ($controlNumber <= 0) {
                echo "Invalid control number.";
                exit();
            }

            $stmt = $conn->prepare("UPDATE scilab_form_requests SET statusScilabPersonnel = 'Approved', supervisor_status = 'approved', subject_teacher_status = 'approved', lab_personnel_status = 'approved', cid_chief_status = 'approved', controlNumber = ?, feedback = ? WHERE id = ?");
            $stmt->bind_param("isi", $controlNumber, $remarks, $id);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                sendNotificationEmail($conn, $id, 'approved', $controlNumber);
                echo "Request approved.";
            } else {
                echo "Update failed.";
            }
            $stmt->close();
            exit();
        }elseif ($_POST['action'] === 'reject') {
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

            if (!$startDate || !$endDate) {
                echo json_encode(['success' => false, 'message' => 'Start and end dates are required.']);
                exit();
            }

            $stmt = $conn->prepare("
                SELECT 
                    COALESCE(si.classification, 'Uncategorized') as classification,
                    mr.item, 
                    mr.description,
                    si.unit,
                    mr.quantity,
                    a.firstname,
                    a.middlename,
                    a.lastname,
                    fr.inclusiveDate,
                    fr.inclusiveTime
                FROM scilab_material_requests mr
                JOIN scilab_form_requests fr ON mr.formID = fr.id
                LEFT JOIN scilab_inventory si ON mr.item = si.item
                LEFT JOIN accounts a ON fr.requesterEmployeeID = a.employeeID
                WHERE fr.statusScilabPersonnel = 'Approved'
                AND fr.inclusiveDate BETWEEN ? AND ?
                ORDER BY COALESCE(si.classification, 'Uncategorized') ASC, mr.item ASC, fr.inclusiveDate ASC
            ");

            if ($stmt === false) {
                echo json_encode(['success' => false, 'message' => 'Database query preparation failed.']);
                exit();
            }

            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $result = $stmt->get_result();

            $categorizedItems = [];
            while ($row = $result->fetch_assoc()) {
                $classification = $row['classification'];
                // Initialize the category array if it doesn't exist
                if (!isset($categorizedItems[$classification])) {
                    $categorizedItems[$classification] = [];
                }
                
                $requestorName = trim($row['firstname'] . ' ' . $row['middlename'] . ' ' . $row['lastname']);
                
                // Add the item to its category
                $categorizedItems[$classification][] = [
                    'item' => $row['item'], 
                    'description' => $row['description'], 
                    'quantity' => $row['quantity'], 
                    'unit' => $row['unit'],
                    'requestor' => $requestorName,
                    'date' => $row['inclusiveDate'],
                    'time' => $row['inclusiveTime']
                ];
            }

            echo json_encode(['success' => true, 'items' => $categorizedItems]);
            $stmt->close();
            exit();
        }

        $conn->close();
    }
?>