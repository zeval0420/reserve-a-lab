<?php
include('../../scilab/helperFiles/db_connection.php');
include('../helperFiles/session_handler.php');
include('../helperFiles/variableDeclarations.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

if (isset($_POST["action"]) && $_POST["action"] === "loginUser") {
    $input_identity = $_POST['email']; // This field handles both email and username
    $password = $_POST['password'];

    // Detect input type
    $is_email = (strpos($input_identity, '@') !== false);
    $email_query = $is_email ? $input_identity : $input_identity . '@irc.pshs.edu.ph';

    /* 
     * Initial login attempt: Check if the user exists in the employee/admin accounts table.
     * We limit the search to users with an 'active' status.
     */
    $stmt = $conn->prepare("SELECT * FROM {$db_table_accounts} WHERE {$db_col_email} = ? AND {$db_col_status} = 'active'");
    $stmt->bind_param("s", $email_query);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $hashedInput = md5($password);

        if ($hashedInput !== $user[$db_col_password]) {
            echo "invalid_password";
        } else {
            /* 
             * Authentication successful. 
             * Populate session data with employee details for subsequent operations.
             */
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

    $stmt->close();

    /*
     * Secondary login attempt: Fallback to checking the student directory.
     * Maps the entered email to the internal student records via LRN.
     */
    $stmt = $conn->prepare("
        SELECT s.*, d.studentEmail 
        FROM student_directory d 
        INNER JOIN student s ON s.LRN = d.LRN 
        WHERE d.studentEmail = ?
    ");
    $stmt->bind_param("s", $email_query);
    $stmt->execute();
    $studentResult = $stmt->get_result();

    if ($studentResult->num_rows > 0) {
        $student = $studentResult->fetch_assoc();

        /*
         * Check if the student has registered an account in scilab_new_accounts.
         */
        $stmt_new = $conn->prepare("SELECT * FROM {$db_table_new_accounts} WHERE username = ? OR username = ? OR userID = ?");
        $stmt_new->bind_param("sss", $input_identity, $email_query, $student['LRN']);
        $stmt_new->execute();
        $newAccountsResult = $stmt_new->get_result();

        if ($newAccountsResult->num_rows === 0) {
            $username_suggested = str_replace('@irc.pshs.edu.ph', '', $student['studentEmail']);
            echo json_encode([
                "status" => "prompt_create_account",
                "firstname" => $student['firstname'],
                "middlename" => $student['middlename'],
                "lastname" => $student['lastname'],
                "username" => $username_suggested,
                "email" => $student['studentEmail'],
                "studentid" => $student['LRN']
            ]);
            $stmt_new->close();
            $stmt->close();
            $conn->close();
            exit();
        }

        $new_user = $newAccountsResult->fetch_assoc();
        $stmt_new->close();

        // Verify using MD5 to match login system
        $hashedInput = md5($password);
        if ($hashedInput !== $new_user['password']) {
            echo "invalid_password";
            $stmt->close();
            $conn->close();
            exit();
        }

        /*
         * Student authentication successful.
         * Establish the session state utilizing the verified student information.
         */
        $_SESSION[$session_email] = $student['studentEmail'];
        $_SESSION[$session_firstname] = $student[$db_col_firstname];
        $_SESSION[$session_middlename] = $student[$db_col_middlename];
        $_SESSION[$session_lastname] = $student[$db_col_lastname];
        $_SESSION[$session_role] = "student";
        $_SESSION[$session_username] = $student['firstname'] . ' ' . substr($student['middlename'], 0, 1) . '. ' . $student['lastname'];

        $_SESSION['student_lrn'] = $student['LRN'];

        echo "requester";
        $stmt->close();
        $conn->close();
        exit();
    }
    
    $stmt->close();
    
    /*
     * Tertiary login attempt: Fallback to checking the scilab_new_accounts table.
     */
    $stmt = $conn->prepare("SELECT * FROM {$db_table_new_accounts} WHERE username = ?");
    $stmt->bind_param("s", $input_identity);
    $stmt->execute();
    $newAccountsResult = $stmt->get_result();

    if ($newAccountsResult->num_rows > 0) {
        $new_user = $newAccountsResult->fetch_assoc();
        $hashedInput = md5($password);

        if ($hashedInput !== $new_user['password']) {
            echo "invalid_password";
        } else {
            $_SESSION[$session_role] = "guest";
            $_SESSION[$session_email] = strpos($new_user['username'], '@') !== false ? $new_user['username'] : ''; 
            $_SESSION[$session_firstname] = $new_user['firstname'];
            $_SESSION[$session_middlename] = $new_user['middlename'];
            $_SESSION[$session_lastname] = $new_user['lastname'];
            
            $_SESSION[$session_username] = $new_user['firstname'] . ' ' . $new_user['lastname'] . ' (' . $new_user['institution'] . ')';
            $_SESSION[$session_employeeID] = !empty($new_user['userID']) ? $new_user['userID'] : 'Guest';
            
            echo "guest";
        }
        $stmt->close();
        $conn->close();
        exit();
    }

    $stmt->close();
    $conn->close();

    // If none of the attempts succeed
    echo "prompt_create_account";
}

if (isset($_POST["action"]) && $_POST["action"] === "guestLogin") {
    $email = $_POST['email']; // Note: mapped from reg-email
    $username = $_POST['username'];
    $password = md5($_POST['password']);
    $firstname = $_POST['firstname'];
    $middlename = isset($_POST['middlename']) ? $_POST['middlename'] : '';
    $lastname = $_POST['lastname'];
    $institution = $_POST['institution'];
    $studentid = isset($_POST['studentid']) ? $_POST['studentid'] : '';

    // Check if username already exists in scilab_new_accounts
    $stmt = $conn->prepare("SELECT id FROM {$db_table_new_accounts} WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        echo "Username already exists.";
        $stmt->close();
        exit();
    }
    $stmt->close();
    
    // Insert into scilab_new_accounts
    $userID = !empty($studentid) ? $studentid : ('GUEST-' . time());
    
    $stmt = $conn->prepare("INSERT INTO {$db_table_new_accounts} (userID, firstname, middlename, lastname, username, password, institution) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $userID, $firstname, $middlename, $lastname, $username, $password, $institution);
    
    if ($stmt->execute()) {
        $_SESSION[$session_role] = "guest";
        $_SESSION[$session_email] = $email;
        $_SESSION[$session_firstname] = $firstname;
        $_SESSION[$session_middlename] = $middlename;
        $_SESSION[$session_lastname] = $lastname;

        $_SESSION[$session_username] = $firstname . ' ' . $lastname . ' (' . $institution . ')';
        $_SESSION[$session_employeeID] = $userID;

        echo "success";
    } else {
        echo "Error creating account.";
    }
    $stmt->close();
    exit();
}

if (isset($_POST["action"]) && $_POST["action"] === "forgotPassword") {
    $email = $_POST['email'];

    /* Locate the account in the administrative/employee accounts table. */
    $stmt = $conn->prepare("SELECT firstname, lastname, email, password FROM accounts WHERE email = ? AND status = 'active'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    $stmt->close();

    if (!$user) {
        /* If no employee account was found, search within the underlying student database. */
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

            /* Construct a stateless validation token by hashing key credentials along with the timestamp. */
            $token = md5($user['email'] . $user['password'] . 'SciLabSecretSalt2025' . $timestamp);
            $link = "http://" . $_SERVER['HTTP_HOST'] . "/" . $active_server . "/reset_password.php?email=" . urlencode($user['email']) . "&token=" . $token . "&ts=" . $timestamp;
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