<?php
include('../helperFiles/db_connection.php');
include('../helperFiles/session_handler.php');
include('../helperFiles/variableDeclarations.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

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
            } elseif ($position === 'Teacher') {
                $_SESSION[$session_role] = "teacher";
                echo "teacher";
            } else {
                $_SESSION[$session_role] = "requester";
                echo "requester";
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

        echo "requester";
    }

    $stmt->close();
    $conn->close();
}

if (isset($_POST["action"]) && $_POST["action"] === "forgotPassword") {
    $email = $_POST['email'];
    
    // Check accounts table
    $stmt = $conn->prepare("SELECT firstname, lastname, email, password FROM accounts WHERE email = ? AND status = 'active'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    $stmt->close();

    if (!$user) {
        // Check student_directory
        $stmt = $conn->prepare("SELECT s.firstname, s.lastname, d.studentEmail as email FROM student_directory d JOIN student s ON d.LRN = s.LRN WHERE d.studentEmail = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();
        $stmt->close();
    }

    if ($user) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $email_smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $email_smtp_user;
            $mail->Password = $email_smtp_password;
            $mail->SMTPSecure = $email_smtp_secure;
            $mail->Port = $email_smtp_port;

            $mail->setFrom($email_sender, $email_sender_name);
            $mail->addAddress($user['email']);

            $mail->isHTML(true);
            $mail->Subject = "Password Reset Request";
            $timestamp = time();
            // Generate stateless token: md5(email + current_password_hash + secret_salt + timestamp)
            $token = md5($user['email'] . $user['password'] . 'SciLabSecretSalt2025' . $timestamp);
            $link = "http://" . $_SERVER['HTTP_HOST'] . "/reserve-a-lab/reset_password.php?email=" . urlencode($user['email']) . "&token=" . $token . "&ts=" . $timestamp;
            $mail->Body = "Hello " . $user['firstname'] . ",<br><br>You requested a password reset. Click the link below to proceed:<br><a href='$link'>$link</a><br><br>If you did not request this, please ignore this email.";

            $mail->send();
            echo "success";
        } catch (Exception $e) {
            echo "Mailer Error: " . $mail->ErrorInfo;
        }
    } else {
        echo "Email not found.";
    }
    exit();
}
?>