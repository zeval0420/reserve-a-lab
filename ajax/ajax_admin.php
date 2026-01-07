<?php
    // centralized db_connection and session_handler
    include('../helperFiles/db_connection.php');
    include('../helperFiles/session_handler.php');
    include('../helperFiles/variableDeclarations.php');

    // Get session data
    $email = $_SESSION['email'];
    $username   = $_SESSION['username'];
?>

<?php
    // Fetch lab details
    if (isset($_POST['action']) && $_POST['action'] === 'get_lab_details') {
        $labID = ($_POST['lab_id']);

        if ($conn->connect_error) {
            echo json_encode(null);
            exit();
        }

        $stmt = $conn->prepare("SELECT $db_col_scilabName, location FROM $db_table_availability WHERE $db_col_scilabName = ?");
        $stmt->bind_param("s", $labID);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            echo json_encode([
                'oldName' => $row[$db_col_scilabName],
                'location' => $row[$db_col_location]
            ]);
        } else {
            echo json_encode(null);
        }

        $stmt->close();
        $conn->close();
        exit();
    }

    if (isset($_GET['action']) && $_GET['action'] === 'get_pending_count') {
        $syQuery = mysqli_query($conn, "SELECT value FROM $db_table_sy ORDER BY id DESC LIMIT 1");
        $currentSY = mysqli_fetch_assoc($syQuery)['value'];

        $countQuery = mysqli_query($conn, "SELECT COUNT(*) as total FROM $db_table_requests WHERE $db_col_statusPersonnel = 'pending' AND sy = '$currentSY'");
        $pendingCount = mysqli_fetch_assoc($countQuery)['total'];

        echo $pendingCount;
        exit;
    }

    if (isset($_GET['action']) && $_GET['action'] === 'notification_feed') {
        header('Content-Type: application/json');

        $syStmt = $conn->prepare("SELECT value FROM $db_table_sy ORDER BY id DESC LIMIT 1");
        $syStmt->execute();
        $syResult = $syStmt->get_result();
        $currentSY = $syResult->num_rows ? ($syResult->fetch_assoc()['value'] ?? null) : null;
        $syStmt->close();

        if (!$currentSY) {
            echo json_encode(['items' => []]);
            exit();
        }

        $query = "
            SELECT fr.id, fr.scilabName, fr.gradeLevel, fr.`section/s` AS sections, fr.subject, fr.subjectTopic,
                fr.inclusiveDate, fr.inclusiveTime, fr.dateRequested, fr.teacherInCharge,
                CONCAT(a.firstname, ' ', a.lastname) AS requesterName
            FROM scilab_form_requests fr
            LEFT JOIN accounts a ON a.employeeID = fr.requesterEmployeeID
            WHERE fr.statusScilabPersonnel = 'Pending' AND fr.sy = ?
            ORDER BY fr.dateRequested DESC
            LIMIT 15";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $currentSY);
        $stmt->execute();
        $res = $stmt->get_result();

        $notifications = [];
        while ($row = $res->fetch_assoc()) {
            $notifications[] = [
                'id' => (int)$row['id'],
                'lab' => $row['scilabName'],
                'grade' => $row['gradeLevel'],
                'sections' => $row['sections'] ?: 'N/A',
                'subject' => $row['subject'] ?? 'N/A',
                'topic' => $row['subjectTopic'] ?? '',
                'date' => date('M d, Y', strtotime($row['inclusiveDate'])),
                'time' => $row['inclusiveTime'],
                'submitted' => date('M d, Y g:i A', strtotime($row['dateRequested'])),
                'requester' => $row['requesterName'] ? trim($row['requesterName']) : 'Unknown',
                'teacher' => $row['teacherInCharge'] ?? ''
            ];
        }

        echo json_encode([
            'items' => $notifications
        ]);
        exit();
    }

    if (isset($_POST['scilabName']) && isset($_POST['availability'])) {
        $scilabName = $_POST['scilabName'];
        $availability = $_POST['availability'];

        $stmt = $conn->prepare("UPDATE $db_table_availability SET availability = ? WHERE $db_col_scilabName = ?");
        $stmt->bind_param("ss", $availability, $scilabName);

        if ($stmt->execute()) {
            echo "success";
        } else {
            echo "error";
        }

        $stmt->close();
        $conn->close();
    }

    if (isset($_POST['action']) && $_POST['action'] === 'get_availability') {
        $availabilityData = [];
        $query = "SELECT scilabName, availability FROM scilab_availability WHERE status = 'active'";
        $result = $conn->query($query);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $availabilityData[$row['scilabName']] = $row['availability'];
            }
            echo json_encode($availabilityData);
        } else {
            echo json_encode([]);
        }

        $conn->close();
    }

    if (isset($_POST['action']) && $_POST['action'] === 'add_new_laboratory') {
        $labName     = trim($_POST['lab_name']);
        $labLocation = trim($_POST['lab_location']);

        if (empty($labName) || empty($labLocation)) {
            echo "Missing required fields.";
            exit;
        }

        if (!isset($_FILES['lab_image']) || $_FILES['lab_image']['error'] !== UPLOAD_ERR_OK) {
            echo "Image upload failed.";
            exit;
        }

        $imageFileType = strtolower(pathinfo($_FILES["lab_image"]["name"], PATHINFO_EXTENSION));
        if (!in_array($imageFileType, ['jpg', 'jpeg'])) {
            echo "Only JPG/JPEG images are allowed.";
            exit;
        }

        $safeLabName = preg_replace("/[^a-zA-Z0-9\s\-_]/", "", $labName);
        $fileName = $safeLabName . '.jpg';
        $targetPath = "../img/labimages/" . $fileName;

        if (!move_uploaded_file($_FILES["lab_image"]["tmp_name"], $targetPath)) {
            echo "Failed to move uploaded image.";
            exit;
        }

        $relativePath = "img/labimages/" . $fileName;

        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM scilab_availability WHERE scilabName = ?");
        $checkStmt->bind_param("s", $labName);
        $checkStmt->execute();
        $checkStmt->bind_result($count);
        $checkStmt->fetch();
        $checkStmt->close();

        if ($count > 0) {
            echo "Lab name already exists.";
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO scilab_availability (scilabName, mainImagePath, location, availability, status) VALUES (?, ?, ?, 'Available', 'active')");
        $stmt->bind_param("sss", $labName, $relativePath, $labLocation);

        if ($stmt->execute()) {
            $galleryFolder = "../img/labimages/" . $safeLabName;
            if (!file_exists($galleryFolder)) {
                mkdir($galleryFolder, 0777, true);
            }
            echo "Success";
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
        $conn->close();
    }

    if (isset($_POST['action']) && $_POST['action'] === 'remove_scilab') {
        $scilabName = $_POST['scilabName'];
        $stmt = $conn->prepare("UPDATE scilab_availability SET status = 'inactive' WHERE scilabName = ?");
        $stmt->bind_param("s", $scilabName);
        $stmt->execute();
        echo "Success";
        $conn->close();
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'edit_lab_image') {
        $oldLabName = trim($_POST['lab_old_name']);
        $newLabName = trim($_POST['lab_name']);
        $labLocation = trim($_POST['lab_location']);

        if (empty($oldLabName) || empty($newLabName) || empty($labLocation)) {
            echo 'Missing lab name or location.';
            exit;
        }

        $safeLabName = preg_replace("/[^a-zA-Z0-9\s\-_]/", "", $newLabName);
        $imagePath = "img/labimages/" . $safeLabName . ".jpg";
        $fullImagePath = "../" . $imagePath;

        if (isset($_FILES['lab_image']) && $_FILES['lab_image']['error'] === UPLOAD_ERR_OK) {
            $imageFileType = strtolower(pathinfo($_FILES['lab_image']['name'], PATHINFO_EXTENSION));
            if (!in_array($imageFileType, ['jpg', 'jpeg'])) {
                echo 'Invalid file type. Only JPG images allowed.';
                exit;
            }

            if (!move_uploaded_file($_FILES['lab_image']['tmp_name'], $fullImagePath)) {
                echo 'Failed to move uploaded image.';
                exit;
            }
        }

        if ($oldLabName !== $newLabName) {
            $checkStmt = $conn->prepare("SELECT COUNT(*) FROM scilab_availability WHERE scilabName = ?");
            $checkStmt->bind_param("s", $newLabName);
            $checkStmt->execute();
            $checkStmt->bind_result($count);
            $checkStmt->fetch();
            $checkStmt->close();

            if ($count > 0) {
                echo "Lab name already exists.";
                exit;
            }

            $oldFolder = "../img/labimages/" . preg_replace("/[^a-zA-Z0-9\s\-_]/", "", $oldLabName);
            $newFolder = "../img/labimages/" . $safeLabName;
            if (file_exists($oldFolder)) {
                rename($oldFolder, $newFolder);
            }
        }

        $stmt = $conn->prepare("UPDATE scilab_availability SET scilabName = ?, location = ?, mainImagePath = ? WHERE scilabName = ?");
        $stmt->bind_param("ssss", $newLabName, $labLocation, $imagePath, $oldLabName);

        if ($stmt->execute()) {
            echo 'success';
        } else {
            echo 'Failed to update lab.';
        }

        $stmt->close();
        $conn->close();
        exit();
    }

    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'getGalleryImages' && isset($_POST['labName'])) {
            $labName = basename($_POST['labName']);
            $directory = "img/labimages/{$labName}/";
            $images = [];

            if (is_dir($directory)) {
                $files = scandir($directory);
                foreach ($files as $file) {
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        $images[] = $file;
                    }
                }
            }
            echo json_encode($images);
            exit;
        }
    }

    if (isset($_FILES['galleryImages']) && isset($_POST['labName'])) {
        $labName = basename($_POST['labName']);
        $uploadDir = "../img/labimages/{$labName}/";

        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        foreach ($_FILES['galleryImages']['tmp_name'] as $key => $tmp_name) {
            $fileName = basename($_FILES['galleryImages']['name'][$key]);
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) continue;

            $targetPath = $uploadDir . $fileName;
            move_uploaded_file($tmp_name, $targetPath);
        }

        echo json_encode(["status" => "success"]);
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'get_lab_images' && isset($_POST['lab'])) {
            $lab = $_POST['lab'];
            $folder = 'img/labimages/' . $lab;

            $images = [];
    
            if (is_dir('../' . $folder)) {
                foreach (scandir('../' . $folder) as $file) {
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                        $images[] = $folder . '/' . $file;

                    }
                }
            }
    
            echo json_encode($images);
            exit;
        }
    }
    if ($_POST['action'] === 'upload_gallery_images' && isset($_POST['labName'])) {
        $lab = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['labName']);
        $folder = "../img/labimages/$lab/";
    
        if (!file_exists($folder)) {
            mkdir($folder, 0777, true);
        }
    
        if (isset($_FILES['galleryImages']) && is_array($_FILES['galleryImages']['tmp_name'])) {
            foreach ($_FILES['galleryImages']['tmp_name'] as $i => $tmpPath) {
                $originalName = $_FILES['galleryImages']['name'][$i];
                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $uniqueName = $lab . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
                    $targetPath = $folder . $uniqueName;
    
                    if (is_uploaded_file($tmpPath)) {
                        if (move_uploaded_file($tmpPath, $targetPath)) {
                            error_log("Uploaded: $uniqueName");
                        } else {
                            error_log("Failed to move file to: $targetPath");
                        }
                    } else {
                        error_log("Not a valid uploaded file: $tmpPath");
                    }
                }
            }
        }
    
        echo 'success';
        exit;
    }    
    
    if ($_POST['action'] === 'delete_gallery_image' && isset($_POST['labName'], $_POST['fileName'])) {
        $lab = basename($_POST['labName']);
        $file = basename($_POST['fileName']);
        $path = "../img/labimages/$lab/$file";
    
        if (file_exists($path)) {
            unlink($path);
            echo 'deleted';
        } else {
            echo 'not found';
        }
    
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'update_scilab_color') {
        $name = $_POST['scilabName'];
        $color = $_POST['color'];

        $stmt = $conn->prepare("
            UPDATE scilab_availability
            SET color = ?
            WHERE scilabName = ?
        ");
        $stmt->bind_param("ss", $color, $name);

        echo $stmt->execute() ? 'success' : 'error';
        exit;
    }
?>
