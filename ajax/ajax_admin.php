<?php
    // centralized db_connection and session_handler
    include('../../scilab/helperFiles/db_connection.php');
    include('../helperFiles/session_handler.php');
    include('../helperFiles/variableDeclarations.php');
    // Admin-only endpoint: logged in with the admin role, and a valid CSRF token on POST
    ral_require_role(['admin']);
    ral_require_csrf();
    // Downsizes uploaded lab images and keeps their WebP mirror in sync (never stops an upload)
    require_once __DIR__ . '/../helperFiles/image_optimizer.php';
    // Content checks for uploads and safe lab folder / file names
    require_once __DIR__ . '/../helperFiles/upload_security.php';

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

        $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM $db_table_requests WHERE $db_col_statusPersonnel = 'pending' AND sy = ?");
        $countStmt->bind_param("s", $currentSY);
        $countStmt->execute();
        $pendingCount = $countStmt->get_result()->fetch_assoc()['total'];
        $countStmt->close();

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
            SELECT fr.id, fr.scilabName, fr.gradeLevel, fr.sections AS sections, fr.subject, fr.subjectTopic,
                fr.inclusiveDate, fr.inclusiveTime, fr.dateRequested, fr.teacherInCharge,
                CONCAT_WS(' ', COALESCE(a.firstname, st.firstname), COALESCE(a.middlename, st.middlename), COALESCE(a.lastname, st.lastname)) AS requesterName
            FROM scilab_form_requests fr
            LEFT JOIN accounts a ON a.employeeID = fr.requesterEmployeeID
            LEFT JOIN student st ON fr.requesterEmployeeID = st.LRN
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
                'teacher' => $row['teacherInCharge'] ?? '',
                'flag' => (!empty($row['dateRequested']) && !empty($row['inclusiveDate']) && (strtotime($row['inclusiveDate']) - strtotime($row['dateRequested'])) < 3 * 86400) ? 1 : 0
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

        $safeLabName = ral_lab_folder_name($labName);
        if ($safeLabName === '') {
            echo "Laboratory name must contain letters or numbers.";
            exit;
        }

        $upload = ral_validate_image_upload($_FILES['lab_image'] ?? [], ['image/jpeg' => 'jpg']);
        if (!$upload['ok']) {
            echo $upload['error'];
            exit;
        }

        $fileName = $safeLabName . '.jpg';
        $targetPath = "../img/labimages/" . $fileName;

        if (!move_uploaded_file($_FILES["lab_image"]["tmp_name"], $targetPath)) {
            echo "Failed to move uploaded image.";
            exit;
        }
        ral_img_process_upload($targetPath);

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
                mkdir($galleryFolder, 0755, true);
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

        $safeLabName = ral_lab_folder_name($newLabName);
        if ($safeLabName === '') {
            echo 'Laboratory name must contain letters or numbers.';
            exit;
        }
        $imagePath = "img/labimages/" . $safeLabName . ".jpg";
        $fullImagePath = "../" . $imagePath;

        if (isset($_FILES['lab_image']) && $_FILES['lab_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload = ral_validate_image_upload($_FILES['lab_image'], ['image/jpeg' => 'jpg']);
            if (!$upload['ok']) {
                echo $upload['error'];
                exit;
            }

            if (!move_uploaded_file($_FILES['lab_image']['tmp_name'], $fullImagePath)) {
                echo 'Failed to move uploaded image.';
                exit;
            }
            ral_img_process_upload($fullImagePath);
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

            $oldFolder = "../img/labimages/" . ral_lab_folder_name($oldLabName);
            $newFolder = "../img/labimages/" . $safeLabName;
            if (file_exists($oldFolder)) {
                if (rename($oldFolder, $newFolder)) {
                    ral_img_rename_mirror_dir($oldFolder, $newFolder);
                }
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
            $labName = ral_lab_folder_name($_POST['labName']);
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

    // Gallery upload (admin_home.php, action upload_gallery_images). Files keep their own names in
    // img/labimages/<lab>/; a name already taken gets a numbered suffix instead of overwriting.
    if (isset($_POST['action']) && $_POST['action'] === 'upload_gallery_images' && isset($_POST['labName'])) {
        $labName = ral_lab_folder_name($_POST['labName']);
        $labsRoot = "../img/labimages/";
        $uploadDir = $labsRoot . $labName . "/";

        // Only into the gallery folder of a laboratory that exists.
        if ($labName === '' || !is_dir($uploadDir) || !ral_path_within($uploadDir, $labsRoot)) {
            echo json_encode(["status" => "error", "message" => "Unknown laboratory."]);
            exit;
        }

        $saved = 0;
        $rejected = [];
        if (isset($_FILES['galleryImages']) && is_array($_FILES['galleryImages']['tmp_name'])) {
            foreach ($_FILES['galleryImages']['tmp_name'] as $key => $tmp_name) {
                $file = [
                    'name'     => $_FILES['galleryImages']['name'][$key] ?? '',
                    'tmp_name' => $tmp_name,
                    'error'    => $_FILES['galleryImages']['error'][$key] ?? UPLOAD_ERR_NO_FILE,
                ];
                if ($file['error'] === UPLOAD_ERR_NO_FILE) continue;

                $upload = ral_validate_image_upload($file);
                if (!$upload['ok']) {
                    $rejected[] = ral_safe_file_base($file['name']) . ': ' . $upload['error'];
                    continue;
                }

                // Stored name: the cleaned original name, the extension from the real image type.
                $base = ral_safe_file_base($file['name']);
                if ($base === '') $base = 'image';
                $targetPath = ral_unique_upload_path($uploadDir, $base, $upload['ext']);

                if (move_uploaded_file($tmp_name, $targetPath)) {
                    chmod($targetPath, 0644);
                    ral_img_process_upload($targetPath);
                    $saved++;
                }
            }
        }

        echo json_encode(["status" => $saved > 0 || !$rejected ? "success" : "error", "saved" => $saved, "rejected" => $rejected]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'get_lab_images' && isset($_POST['lab'])) {
            $lab = ral_lab_folder_name($_POST['lab']);
            $folder = 'img/labimages/' . $lab;

            $images = [];
    
            if ($lab !== '' && is_dir('../' . $folder)) {
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
    if ($_POST['action'] === 'delete_gallery_image' && isset($_POST['labName'], $_POST['fileName'])) {
        $lab = ral_lab_folder_name($_POST['labName']);
        $file = basename(str_replace(chr(92), '/', $_POST['fileName']));
        $labDir = "../img/labimages/$lab/";
        $path = $labDir . $file;
        $isImage = in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);

        // Only an image file that really sits in that laboratory's gallery folder.
        if ($lab !== '' && $isImage && is_file($path) && ral_path_within($path, $labDir)) {
            unlink($path);
            ral_img_delete_mirror($path);
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
