<?php
include('../../credentials/db_connection.php');
include('../helperFiles/session_handler.php');
include('../helperFiles/variableDeclarations.php');
require_once __DIR__ . '/../helperFiles/input.php';

// Login, guest sign-up and password-reset requests are made from the login page, which carries the token
ral_require_csrf();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

if (isset($_POST["action"]) && $_POST["action"] === "loginUser") {
    $input_identity = $_POST['email'] ?? ''; // This field handles both email and username
    $password = $_POST['password'];

    $wait = ral_throttle_wait('login');
    if ($wait > 0) {
        echo ral_throttle_message($wait);
        exit();
    }

    if (!is_string($input_identity) || $input_identity === '') {
        ral_throttle_hit('login');
        echo "invalid_email";
        exit();
    }

    foreach (['username', $db_col_email] as $column) {
        $result = ral_query($conn, "SELECT * FROM {$db_table_accounts} WHERE {$column} = ? AND {$db_col_status} = 'active' LIMIT 1", 's', [$input_identity]);
        if ($user = $result->fetch_assoc()) {
            break;
        }
    }

    if ($user) {
        $hashedInput = md5($password);

        if ($hashedInput !== $user[$db_col_password]) {
            ral_throttle_hit('login');
            echo "invalid_password";
        } else {
            ral_throttle_clear('login');
            /* 
             * Authentication successful. 
             * Populate session data with employee details for subsequent operations.
             */
            session_regenerate_id(true);
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

        $conn->close();
        exit();
    }

    $result = ral_query($conn, "SELECT * FROM {$db_table_new_accounts} WHERE username = ? LIMIT 1", 's', [$input_identity]);
    $new_user = $result->fetch_assoc();
    if (!$new_user) {
        // Registration adds the email column on older databases.
        $emailColumn = $conn->query("SHOW COLUMNS FROM {$db_table_new_accounts} LIKE 'email'");
        if ($emailColumn->num_rows > 0) {
            $result = ral_query($conn, "SELECT * FROM {$db_table_new_accounts} WHERE email = ? LIMIT 1", 's', [$input_identity]);
            $new_user = $result->fetch_assoc();
        }
    }

    $student = null;
    if (!$new_user) {
        // Keep the directory email fallback for legacy student accounts and registration.
        $result = ral_query($conn, "
            SELECT s.*, d.studentEmail
            FROM student_directory d
            INNER JOIN student s ON s.LRN = d.LRN
            WHERE d.studentEmail = ?
            LIMIT 1
        ", 's', [$input_identity]);
        $student = $result->fetch_assoc();

        $result = ral_query($conn, "SELECT * FROM {$db_table_new_accounts} WHERE userID = ? LIMIT 1", 's', [$student['LRN'] ?? $input_identity]);
        $new_user = $result->fetch_assoc();

        if ($student && !$new_user) {
            ral_throttle_hit('login');
            $username_suggested = str_replace('@irc.pshs.edu.ph', '', $student['studentEmail']);
            echo json_encode([
                "status" => "prompt_create_account",
                "firstname" => $student['firstname'],
                "middlename" => $student['middlename'],
                "lastname" => $student['lastname'],
                "username" => $username_suggested,
                "email" => $student['studentEmail']
            ]);
            $conn->close();
            exit();
        }
    }

    if (!$new_user || md5($password) !== $new_user['password']) {
        ral_throttle_hit('login');
        echo $new_user ? "invalid_password" : "prompt_create_account";
        $conn->close();
        exit();
    }

    $directoryEntry = null;
    if (!$student) {
        $result = ral_query($conn, "
            SELECT s.*, d.studentEmail
            FROM student_directory d
            LEFT JOIN student s ON s.LRN = d.LRN
            WHERE d.LRN = ?
            LIMIT 1
        ", 's', [$new_user['userID']]);
        $directoryEntry = $result->fetch_assoc();
        if (isset($directoryEntry['LRN'])) {
            $student = $directoryEntry;
        }
    }

    ral_throttle_clear('login');
    session_regenerate_id(true);
    if ($student) {
        /*
         * Student authentication successful.
         * Establish the session state utilizing the verified student information.
         */
        $_SESSION[$session_employeeID] = $student['LRN'];
        $_SESSION[$session_email] = $student['studentEmail'];
        $_SESSION[$session_firstname] = $student[$db_col_firstname];
        $_SESSION[$session_middlename] = $student[$db_col_middlename];
        $_SESSION[$session_lastname] = $student[$db_col_lastname];
        $_SESSION[$session_role] = "student";
        $_SESSION[$session_username] = $student['firstname'] . ' ' . substr($student['middlename'], 0, 1) . '. ' . $student['lastname'];

        $_SESSION['student_lrn'] = $student['LRN'];

        echo "requester";
    } else {
        $resolvedEmail = trim($new_user['email'] ?? '');
        if ($resolvedEmail === '') {
            $resolvedEmail = filter_var($new_user['username'], FILTER_VALIDATE_EMAIL)
                ?: trim($directoryEntry['studentEmail'] ?? '');
        }

        $_SESSION[$session_role] = "guest";
        $_SESSION[$session_email] = $resolvedEmail;
        $_SESSION[$session_firstname] = $new_user['firstname'];
        $_SESSION[$session_middlename] = $new_user['middlename'];
        $_SESSION[$session_lastname] = $new_user['lastname'];
        $_SESSION[$session_username] = $new_user['firstname'] . ' ' . $new_user['lastname'] . ' (' . ($new_user['institution'] ?? 'PSHS-IRC') . ')';
        $_SESSION[$session_employeeID] = !empty($new_user['userID']) ? $new_user['userID'] : $new_user['username'];

        echo "guest";
    }

    $conn->close();
    exit();
}

if (isset($_POST["action"]) && $_POST["action"] === "guestLogin") {
    $wait = ral_throttle_wait('register');
    if ($wait > 0) {
        echo ral_throttle_message($wait);
        exit();
    }
    ral_throttle_hit('register', 5, 3600);
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
    
    $email = trim($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Please enter a valid email address.";
        exit();
    }

    // shortcut: self-migrating column so already-deployed DBs keep working; drop once every DB has it.
    $conn->query("ALTER TABLE {$db_table_new_accounts} ADD COLUMN IF NOT EXISTS email VARCHAR(255) DEFAULT NULL");

    $stmt = $conn->prepare("INSERT INTO {$db_table_new_accounts} (userID, firstname, middlename, lastname, username, password, institution, email) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $userID, $firstname, $middlename, $lastname, $username, $password, $institution, $email);
    
    if ($stmt->execute()) {
        session_regenerate_id(true);
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
    $wait = ral_throttle_wait('forgot');
    if ($wait > 0) {
        echo ral_throttle_message($wait);
        exit();
    }
    ral_throttle_hit('forgot', 3, 900);

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
