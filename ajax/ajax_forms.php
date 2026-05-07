<?php
    // Catch fatal errors and throw them as JSON responses instead of crashing
    ini_set('display_errors', 0);
    error_reporting(E_ALL);

    set_exception_handler(function($e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error', 
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        exit();
    });

    set_error_handler(function($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            return;
        }
        throw new ErrorException($message, 0, $severity, $file, $line);
    });

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    require '../PHPMailer/src/Exception.php';
    require '../PHPMailer/src/PHPMailer.php';
    require '../PHPMailer/src/SMTP.php';

    /* 
     * Centralized initialization protocol: 
     * Integrates database connectivity and ensures secure session management handling.
     */
    include('../helperFiles/db_connection.php');
    include('../helperFiles/session_handler.php');

    function formatTime($time) {
        return date("g:i A", strtotime($time));
    }

    function createMailer() {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'pshsircscilab@gmail.com';
        $mail->Password = 'wxzmkkrffptfchcc';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->setFrom('pshsircscilab@gmail.com', 'SciLab Notification System');
        $mail->isHTML(true);
        return $mail;
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
            if (!empty($admin['email']) && filter_var($admin['email'], FILTER_VALIDATE_EMAIL)) {
                try {
                    $mail = createMailer();
                    $mail->addAddress($admin['email']);
                    $mail->Subject = $subjectLine;
                    $mail->Body = $bodyTemplate;
                    $mail->send();
                } catch (Exception $e) {
                    error_log("Admin email failed to {$admin['email']}: " . $e->getMessage());
                }
            }
        }
    }

    function sendSubmissionNotificationToSupervisors($conn, $data, $supervisorEmails, $formID) {
        if (empty($supervisorEmails)) return;

        $subjectLine = "Action Required: New SciLab Request for Approval";
        $templatePath = __DIR__ . "/../templates/supervisor_request_email_template.html";

        if (file_exists($templatePath)) {
            $bodyTemplate = file_get_contents($templatePath);
        } else {
            $bodyTemplate = "A new request requires your approval. To view and approve the request, please click the button below.<br><br><a href='[ApprovalLink]' style='background-color: #4CAF50; color: white; padding: 14px 25px; text-align: center; text-decoration: none; display: inline-block;'>View Request Details</a><br><br><strong>Request Details:</strong><br>Facility: [Facility]<br>Requested By: [Requested By]<br>Date: [Start Date]<br>Time: [End Date]";
        }

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $baseURL = $protocol . $_SERVER['HTTP_HOST'] . "/reserve-a-lab";
        $approvalLink = $baseURL . "/supervisor_approve.php?id=" . $formID;

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
            "[ApprovalLink]" => $approvalLink,
        ];

        foreach ($replacements as $key => $val) {
            $bodyTemplate = str_replace($key, htmlspecialchars($val), $bodyTemplate);
        }

        foreach ($supervisorEmails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                try {
                    $mail = createMailer();
                    $mail->addAddress($email);
                    $mail->Subject = $subjectLine;
                    $mail->Body = $bodyTemplate;
                    $mail->send();
                } catch (Exception $e) {
                    error_log("Supervisor email failed to {$email}: " . $e->getMessage());
                }
            }
        }
    }

    if (isset($_POST["action"]) && $_POST["action"] == "request_submission") {
        $scilabName = $_POST['venue'] ?? '';
        $grade = intval($_POST['grade_level'] ?? 0);
        $sections = isset($_POST['sections']) && is_array($_POST['sections']) ? implode(', ', $_POST['sections']) : ($_POST['sections'] ?? '');
        $subject = $_POST['subject'] ?? '';
        $topic = $_POST['topic'] ?? '';
        $unit = $_POST['unit'] ?? '';
        $teacher_input = $_POST['teacher'] ?? [];
        $teachers = is_array($teacher_input) ? $teacher_input : array_filter([$teacher_input]);
        $teacher = implode(', ', $teachers);
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


        $checkStmt = $conn->prepare("SELECT statusScilabPersonnel, inclusiveTime FROM scilab_form_requests WHERE scilabName = ? AND inclusiveDate = ? AND statusScilabPersonnel != 'Rejected'");
        $checkStmt->bind_param("ss", $scilabName, $startDate);
        $checkStmt->execute();
        $checkRes = $checkStmt->get_result();
        
        $startTs = strtotime($startTime);
        $endTs = strtotime($endTime);
        $approvedConflict = false;
        
        while ($row = $checkRes->fetch_assoc()) {
            $timeRange = explode(' to ', $row['inclusiveTime']);
            if (count($timeRange) == 2) {
                $otherStart = strtotime($timeRange[0]);
                $otherEnd = strtotime($timeRange[1]);
                
                if ($startTs < $otherEnd && $endTs > $otherStart) {
                    if ($row['statusScilabPersonnel'] === 'Approved') {
                        $approvedConflict = true;
                        break;
                    }
                }
            }
        }
        
        if ($approvedConflict) {
            echo "conflict_approved";
            exit();
        }

        $syResult = $conn->query("SELECT value FROM current WHERE description = 'School Year' ORDER BY id DESC LIMIT 1");
        $schoolYear = ($syResult && $syResult->num_rows > 0) ? $syResult->fetch_assoc()['value'] : 'N/A';


        $requesterID = $_SESSION['employeeID'] ?? '';
        $dateRequested = date('Y-m-d H:i:s');

        $stmt = $conn->prepare("INSERT INTO scilab_form_requests (
            scilabName, gradeLevel, sections, subject, subjectTopic, inclusiveDate, inclusiveTime, dateRequested, requesterEmployeeID, sy, teacherInCharge, statusScilabPersonnel, supervisor_status, subject_teacher_status, lab_personnel_status, cid_chief_status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', 'pending', 'pending', 'pending', 'pending')");

        $stmt->bind_param("sisssssssss", $scilabName, $grade, $sections, $subject, $topic, $startDate, $formattedTime, $dateRequested, $requesterID, $schoolYear, $teacher);
        
        if (!$stmt->execute()) {
            echo "error";
            exit();
        }

        $formID = $stmt->insert_id;

        /* 
         * Process laboratory material requirements sent as a merged JSON string from the client.
         * Deserializes the array and securely inserts each requested item alongside the generated form identifier.
         */
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
        
        /* Insert the verified participating group members into the student involvement registry. */
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

        if (!empty($teachers)) {
            $teacherEmails = [];
            
            /* 
             * Resolve designated teacher accounts structure by matching full names.
             * Consolidates addresses needed to construct supervisor email notifications.
             */
            $placeholders = rtrim(str_repeat('?,', count($teachers)), ',');
            $email_stmt = $conn->prepare("SELECT email FROM accounts WHERE CONCAT(firstname, ' ', lastname) IN ($placeholders)");

            if ($email_stmt) {
                $types = str_repeat('s', count($teachers));
                $bindParams = [$types];
                for ($i = 0; $i < count($teachers); $i++) {
                    $bindParams[] = &$teachers[$i];
                }
                call_user_func_array([$email_stmt, 'bind_param'], $bindParams);
                $email_stmt->execute();
                $result = $email_stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $teacherEmails[] = $row['email'];
                }
                $email_stmt->close();
            }

            if (!empty($teacherEmails)) {
                sendSubmissionNotificationToSupervisors($conn, [
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
                ], $teacherEmails, $formID);
            }
        }

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

            $html = '';
            while ($row = $result->fetch_assoc()) {
                $section = htmlspecialchars($row['section']);
                $html .= '<option value="' . $section . '">' . $section . '</option>';
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
        $res = mysqli_query($conn, "SELECT value FROM current WHERE description = 'School Year' ORDER BY id DESC LIMIT 1");
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
    }elseif (isset($_POST['action']) && $_POST['action'] === 'check_conflict') {
        header('Content-Type: application/json');
        
        $scilabName = $_POST['scilabName'] ?? '';
        $date = $_POST['date'] ?? '';
        $startTime = $_POST['startTime'] ?? '';
        $endTime = $_POST['endTime'] ?? '';
        $exclude_id = isset($_POST['exclude_id']) ? intval($_POST['exclude_id']) : 0;
        
        if (!$scilabName || !$date || !$startTime || !$endTime) {
            echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
            exit();
        }
        
        $startTs = strtotime($startTime);
        $endTs = strtotime($endTime);
        
        if ($exclude_id > 0) {
            $stmt = $conn->prepare("SELECT id, statusScilabPersonnel, inclusiveTime, requesterEmployeeID, subject, subjectTopic FROM scilab_form_requests WHERE scilabName = ? AND inclusiveDate = ? AND statusScilabPersonnel != 'Rejected' AND id != ?");
            $stmt->bind_param("ssi", $scilabName, $date, $exclude_id);
        } else {
            $stmt = $conn->prepare("SELECT id, statusScilabPersonnel, inclusiveTime, requesterEmployeeID, subject, subjectTopic FROM scilab_form_requests WHERE scilabName = ? AND inclusiveDate = ? AND statusScilabPersonnel != 'Rejected'");
            $stmt->bind_param("ss", $scilabName, $date);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $conflicts = [];
        while ($row = $result->fetch_assoc()) {
            $timeRange = explode(' to ', $row['inclusiveTime']);
            if (count($timeRange) == 2) {
                $otherStart = strtotime($timeRange[0]);
                $otherEnd = strtotime($timeRange[1]);
                
                /* Ensure time slots are fundamentally isolated. Adjacent/Touch boundaries do not establish conflict overlapping. */
                if ($startTs < $otherEnd && $endTs > $otherStart) {
                    $conflicts[] = [
                        'status' => $row['statusScilabPersonnel'],
                        'time' => $row['inclusiveTime'],
                        'subject' => $row['subject'] . ' - ' . $row['subjectTopic']
                    ];
                }
            }
        }
        
        /* Enumerate conflicts to locate the highest severity alert. */
        $conflictType = 'none';
        $conflictDetails = null;
        
        foreach ($conflicts as $c) {
            if ($c['status'] === 'Approved') {
                $conflictType = 'approved';
                $conflictDetails = $c;
                /* Stop checks immediately upon detecting an overriding approval state collision. */
                break; 
            } elseif ($c['status'] === 'Pending') {
                $conflictType = 'pending';
                $conflictDetails = $c;
            }
        }
        
        echo json_encode([
            'status' => 'success',
            'conflict_type' => $conflictType,
            'details' => $conflictDetails
        ]);
        exit();
    }


    $conn->close();
?>