<?php
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    require '../PHPMailer/src/Exception.php';
    require '../PHPMailer/src/PHPMailer.php';
    require '../PHPMailer/src/SMTP.php';

    // centralized db_connection and session_handler
    include('../helperFiles/db_connection.php');
    include('../helperFiles/session_handler.php');

    // Get session data
    $email = $_SESSION['email'];
    $username   = $_SESSION['username'];

    function formatTime($time) {
        return date("g:i A", strtotime($time));
    }

    function sendSubmissionNotificationToAdmins($conn, $data) {
        $admins = $conn->query("SELECT email FROM accounts WHERE status = 'active' AND (position = 'Sci. Res. Assist.' OR position = 'Sci. Research Specialist I')");
        if ($admins->num_rows === 0) return;

        $subjectLine = "New SciLab Request Submitted";
        $templatePath = __DIR__ . "/../templates/request_email_template.html";

        if (file_exists($templatePath)) {
            $bodyTemplate = file_get_contents($templatePath);
        } else {
            $bodyTemplate = "A new request has been submitted. Please check the dashboard for details.";
        }

        $replacements = [
            "[Facility]" => $data['scilabName'],
            "[Grade Level]" => $data['gradeLevel'],
            "[Section]" => $data['section'],
            "[Subject]" => $data['subject'],
            "[Concurrent Topic]" => $data['topic'],
            "[Unit]" => $data['unit'],
            "[Teacher Name]" => $data['teacher'],
            "[Requested By]" => $data['requester'],
            "[Start Date]" => $data['inclusiveDate'],
            "[End Date]" => $data['inclusiveTime'],
            "[Materials]" => $data['materials'],
            "[Group Members]" => $data['students'],
        ];

        foreach ($replacements as $key => $val) {
            $bodyTemplate = str_replace($key, htmlspecialchars($val), $bodyTemplate);
        }

        while ($admin = $admins->fetch_assoc()) {
           if (isset($admin['email'])) {
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'pshsircscilab@gmail.com';
                    $mail->Password = 'wxzmkkrffptfchcc';
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;


                $mail->setFrom('pshsircscilab@gmail.com', 'SciLab Notification System');
                $mail->addAddress($admin['email']);

                $mail->isHTML(true);
                $mail->Subject = $subjectLine;
                $mail->Body = $bodyTemplate;

                $mail->send();
            } catch (Exception $e) {
                error_log("Admin email failed to {$admin['email']}: {$mail->ErrorInfo}");
            }
            }
       }
    }
    if (isset($_POST["action"]) && $_POST["action"] == "request_submission") {
        $scilabName = $_POST['venue'] ?? '';
        $grade = $_POST['grade_level'] ?? '';
        $sections = isset($_POST['sections']) && is_array($_POST['sections']) ? implode(', ', $_POST['sections']) : ($_POST['sections'] ?? '');
        $subject = $_POST['subject'] ?? '';
        $topic = $_POST['topic'] ?? '';
        $unit = $_POST['unit'] ?? '';
        $teacher = $_POST['teacher'] ?? '';
        $startDate = $_POST['inclusive_date'] ?? '';
        $startTime = $_POST['start_time'] ?? '';
        $endTime = $_POST['end_time'] ?? '';
        $formattedTime = formatTime($startTime) . " to " . formatTime($endTime);

        $checkSciLab = $conn->prepare("SELECT 1 FROM scilab_availability WHERE scilabName = ? AND status = 'active'");
        $checkSciLab->bind_param("s", $scilabName);
        $checkSciLab->execute();
        $checkSciLab->store_result();

        if ($checkSciLab->num_rows === 0) {
            echo "invalid_scilab";
            exit();
        }


        $conflictStmt = $conn->prepare("SELECT * FROM scilab_form_requests 
            WHERE scilabName = ? AND inclusiveDate = ? AND (
                (inclusiveTime LIKE CONCAT('%', ?, '%')) OR
                (inclusiveTime LIKE CONCAT('%', ?, '%'))
            ) AND statusScilabPersonnel != 'Rejected'");

        $conflictStmt->bind_param("ssss", $scilabName, $startDate, $startTime, $endTime);
        $conflictStmt->execute();
        $conflictResult = $conflictStmt->get_result();

        if ($conflictResult->num_rows > 0) {
            echo "conflict";
            exit();
        }

        $syResult = $conn->query("SELECT value FROM current WHERE description = 'School Year' ORDER BY id DESC LIMIT 1");
        $schoolYear = ($syResult && $syResult->num_rows > 0) ? $syResult->fetch_assoc()['value'] : 'N/A';


        $requesterID = $_SESSION['employeeID'] ?? '';
        $result = $conn->query("SELECT firstname FROM accounts WHERE employeeID = '$requesterID'");
        $firstName = ($result && $result->num_rows > 0) ? $result->fetch_assoc()['firstname'] : 'User';

        $email = $_SESSION['email'] ?? '';
        $dateRequested = date('Y-m-d H:i:s');

        $stmt = $conn->prepare("INSERT INTO scilab_form_requests (
            scilabName, gradeLevel, sections, subject, subjectTopic, inclusiveDate, inclusiveTime, dateRequested, requesterEmployeeID, sy, teacherInCharge) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("sisssssssss", $scilabName, $grade, $sections, $subject, $topic, $startDate, $formattedTime, $dateRequested, $requesterID, $schoolYear, $teacher);
        
        if (!$stmt->execute()) {
            echo "error";
            exit();
        }

        $formID = $stmt->insert_id;

        // Handle merged materials sent from JS
        $materials = [];
        $stmt2 = $conn->prepare("INSERT INTO scilab_material_requests (formID, item, quantity, unit, description) VALUES (?, ?, ?, ?, ?)");

        if (isset($_POST['mergedMaterials'])) {
            $mergedMaterials = json_decode($_POST['mergedMaterials'], true);

            if (is_array($mergedMaterials)) {
                foreach ($mergedMaterials as $mat) {
                    $item = trim($mat['item'] ?? '');
                    $unit = trim($mat['unit'] ?? 'N/A');
                    $desc = trim($mat['desc'] ?? 'N/A');
                    $qty = isset($mat['qty']) ? (int)$mat['qty'] : 1;

                    if ($item !== '') {
                        $stmt2->bind_param("isiss", $formID, $item, $qty, $unit, $desc);
                        $stmt2->execute();
                        $materials[] = "{$qty} {$unit} of {$item} ({$desc})";
                    }
                }
            }
        }

        $students = $_POST['students'] ?? [];
        $studentList = implode(", ", array_filter($students));
        
        // Insert each student into scilab_students_involved table
        $stmt3 = $conn->prepare("INSERT INTO scilab_students_involved (formID, student_name) VALUES (?, ?)");
        foreach ($students as $student) {
            $student = trim($student);
            if ($student !== '') {
                $stmt3->bind_param("is", $formID, $student);
                $stmt3->execute();
            }
        }
        $stmt3->close();


        $requesterName =
            ($_SESSION['firstname'] ?? '') . ' ' .
            ($_SESSION['middlename'] ?? '') . ' ' .
            ($_SESSION['lastname'] ?? '');

        sendSubmissionNotificationToAdmins($conn, [
            'scilabName' => $scilabName,
            'gradeLevel' => $grade,
            'section' => $sections,
            'subject' => $subject,
            'topic' => $topic,
            'unit' => $unit,
            'teacher' => $teacher,
            'inclusiveDate' => $startDate,
            'inclusiveTime' => $formattedTime,
            'materials' => implode("; ", $materials),
            'students' => $studentList,
            'requester' => $requesterName
        ]);


        echo "success";
        $stmt->close();
        $stmt2->close();
        exit();
    }
    elseif (isset($_POST['action']) && $_POST['action'] === 'get_disabled_dates') {
        header('Content-Type: application/json');

        $scilabName = $_POST['scilabName'];
        $disabled = [];

        $stmt = $conn->prepare("SELECT inclusiveDate FROM scilab_form_requests WHERE scilabName = ? AND statusScilabPersonnel = 'Approved'");
        if (!$stmt) {
            echo json_encode(['error' => 'Prepare failed']);
            exit();
        }

        $stmt->bind_param("s", $scilabName);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            $disabled[] = $row['inclusiveDate'];
        }

        echo json_encode($disabled);
        exit();
    }elseif (isset($_GET['action']) && $_GET['action'] === 'get_sections') {
        $grade = $_GET['grade'] ?? null;

        if ($grade !== null && is_numeric($grade)) {
            $grade = intval($grade);

            $stmt = $conn->prepare("SELECT section FROM section WHERE grade = ? ORDER BY section ASC");
            $stmt->bind_param("i", $grade);
            $stmt->execute();
            $result = $stmt->get_result();

            // Output checkboxes inline
            $html = '';
            while ($row = $result->fetch_assoc()) {
                $section = htmlspecialchars($row['section']);
                $html .= '<option value="' . $section . '">' . $section . '</option>';
                //$html .= '<label class="section-checkbox" style="display:inline-block; margin-right:15px;">';
                //$html .= '<input type="checkbox" name="sections[]" value="' . $section . '"> ' . $section;
                //$html .= '</label>';
            }

            echo $html;
            exit();
        } else {
            echo 'Invalid grade';
            exit();
        }
    }elseif (isset($_POST['action']) && $_POST['action'] === 'get_subjects_by_grade') {
        $grade = $_POST['grade'] ?? null;

        if ($grade !== null && is_numeric($grade)) {
            $grade = intval($grade);
            $stmt = $conn->prepare("SELECT DISTINCT subjectCode, subjectAcademicUnit FROM subject WHERE status = 'active' AND subjectGradeLevel = ?");
            $stmt->bind_param("i", $grade);
            $stmt->execute();
            $result = $stmt->get_result();

            $subjects = [];
            while ($row = $result->fetch_assoc()) {
                $subjects[] = [
                    'description' => $row['subjectCode'],
                    'unit' => $row['subjectAcademicUnit']
                ];
            }

            header('Content-Type: application/json');
            echo json_encode($subjects);
            exit();
        } else {
            // Invalid or missing grade parameter
            header('Content-Type: application/json');
            echo json_encode([]);
            exit();
        }
    }elseif (isset($_POST['action']) && $_POST['action'] === 'get_students_by_grade') {
        $selectedGrade = intval($_POST['grade'] ?? 0);

        if ($selectedGrade < 7 || $selectedGrade > 12) {
            echo json_encode([]);
            exit;
        }

        // Get latest school year
        $res = mysqli_query($conn, "SELECT value FROM current ORDER BY id DESC LIMIT 1");
        $latestBatchStr = mysqli_fetch_assoc($res)['value']; // format: "2024-2025"
        $latestStartYear = intval(explode('-', $latestBatchStr)[1]);

        $targetBatch = $latestStartYear + (12 - $selectedGrade);

        // Fetch students from that batch
        $stmt = $conn->prepare("SELECT LRN, firstname, middlename, lastname, batch FROM student WHERE batch = ? ORDER BY lastname, firstname, middlename ASC");
        $stmt->bind_param("s", $targetBatch);
        $stmt->execute();
        $result = $stmt->get_result();

        $students = [];
        while ($row = $result->fetch_assoc()) {
            $students[] = [
                'LRN' => $row['LRN'],
                'name' => $row['lastname'] . ', ' . $row['firstname'] . ' ' . $row['middlename'],
                'batch' => $row['batch']
            ];
        }

        echo json_encode($students);
        exit;
    }


    $conn->close();
?>