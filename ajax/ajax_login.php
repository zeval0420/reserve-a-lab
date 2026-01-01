<?php
include('../helperFiles/db_connection.php');
include('../helperFiles/session_handler.php');
include('../helperFiles/variableDeclarations.php');

if (isset($_POST["action"]) && $_POST["action"] === "loginUser") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // 1️⃣ Try EMPLOYEE / ADMIN LOGIN FIRST
    $stmt = $conn->prepare("SELECT * FROM {$db_table_accounts} WHERE {$db_col_email} = ? AND {$db_col_status} = 'active'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $hashedInput = md5($password);

        if ($hashedInput !== $user[$db_col_password]) {
            echo "invalid_password";
        } else {
            // SESSION DATA
            $_SESSION[$session_employeeID] = $user[$db_col_employeeID];
            $_SESSION[$session_email] = $user[$db_col_email];
            $_SESSION[$session_firstname] = $user[$db_col_firstname];
            $_SESSION[$session_middlename] = $user[$db_col_middlename];
            $_SESSION[$session_lastname] = $user[$db_col_lastname];

            $middlename = trim($user[$db_col_middlename]);
            $middleInitial = '';

            if (!empty($middlename)) {
                $words = preg_split('/\s+/', $middlename);
                foreach ($words as $word) {
                    $middleInitial .= strtoupper($word[0]);
                }
                $middleInitial .= '.';
            }

            $_SESSION[$session_username] = $user[$db_col_firstname] . ' ' . $middleInitial . ' ' . $user[$db_col_lastname];

            $position = $user[$db_col_position];
            $adminRoles = $accepted_admin_roles;

            if (in_array($position, $adminRoles)) {
                $_SESSION[$session_role] = "admin";
                echo "admin";
            } else {
                $_SESSION[$session_role] = "requestor";
                echo "requestor";
            }
        }

        $stmt->close();
        $conn->close();
        exit();
    }

    $stmt->close(); // close first query before starting another

    // 2️⃣ TRY STUDENT LOGIN
    $stmt = $conn->prepare("
        SELECT s.*, d.studentEmail 
        FROM student_directory d 
        INNER JOIN student s ON s.LRN = d.LRN 
        WHERE d.studentEmail = ?
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $studentResult = $stmt->get_result();

    if ($studentResult->num_rows === 0) {
        echo "invalid_email";
    } else {
        $student = $studentResult->fetch_assoc();

        // SESSION DATA
        $_SESSION[$session_email] = $student['studentEmail'];
        $_SESSION[$session_firstname] = $student[$db_col_firstname];
        $_SESSION[$session_middlename] = $student[$db_col_middlename];
        $_SESSION[$session_lastname] = $student[$db_col_lastname];
        $_SESSION[$session_role] = "student";        
        $_SESSION[$session_username] = $student['firstname'] . ' ' . substr($student['middlename'], 0, 1) . '. ' . $student['lastname'];

        // $_SESSION['student_id'] = $student['id'];
        $_SESSION['student_lrn'] = $student['LRN'];

        echo "requestor";
    }

    $stmt->close();
    $conn->close();
}
?>