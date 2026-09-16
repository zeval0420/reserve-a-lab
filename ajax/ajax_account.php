<?php
include('../../scilab/helperFiles/db_connection.php');
include('../helperFiles/session_handler.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!is_string($csrfToken) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Invalid or missing security token. Please refresh the page and try again.']);
        exit;
    }

    $employeeID = $_SESSION['employeeID'] ?? null;
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';

    if (!$employeeID) {
        echo "Session expired.";
        exit;
    }

    // Verify current password
    $stmt = $conn->prepare("SELECT password FROM accounts WHERE employeeID = ?");
    $stmt->bind_param("s", $employeeID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Verify using MD5 to match login system
        if (md5($currentPassword) === $row['password']) {
            $newHash = md5($newPassword);
            $update = $conn->prepare("UPDATE accounts SET password = ? WHERE employeeID = ?");
            $update->bind_param("ss", $newHash, $employeeID);
            if ($update->execute()) {
                echo "success";
            } else {
                echo "Database error.";
            }
        } else {
            echo "Incorrect current password.";
        }
    } else {
        echo "User not found.";
    }
    exit;
}
?>