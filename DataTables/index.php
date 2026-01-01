<?php
    // server credentials
    $servername = "localhost";
    $username   = "root";
    $password   = "";
    $database   = "dbadmin";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $database);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Start session
    session_start();
    $timeout_duration = 21600; // 6 hours

    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
        session_unset();
        session_destroy();
        $_SESSION['session_expired'] = true;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    $_SESSION['last_activity'] = time();

    // Auto-redirect if session is valid
    $sessionRedirectScript = "";
    if (isset($_SESSION['role'])) {
        if ($_SESSION['role'] === 'admin') {
            header("Location: admin_home.php");
            exit();
        } elseif ($_SESSION['role'] === 'requestor') {
            header("Location: requester_home.php");
            exit();
        }
    }
?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>PSHS IRC Login</title>

        <link rel="stylesheet" href="bootstrap3.3.7/css/bootstrap.min.css">
        <link rel="stylesheet" href="datatables/datatables.min.css">
        <script src="jQuery-3.3.1/jquery-3.3.1.min.js"></script>
        <script src="datatables/datatables.min.js"></script>
        <script src="bootstrap3.3.7/js/bootstrap.min.js"></script>
        <link href="https://fonts.googleapis.com/css2?family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

        <style>
            body {
                display: flex;
                box-sizing: border-box;
                min-height: 100vh;
                font-family: "Raleway", sans-serif;
                background-image: linear-gradient(rgba(0,0,0,0.45),rgba(0,0,0,0.45)),url(img/background.jpg);
                background-position: center;
                background-size: cover;
            }

            .system-name {
                position: absolute;
                top: 25%; /* Halfway between top and the middle (which is 50%) */
                left: 50%;
                transform: translate(-50%, -125%);
                padding: 20px;

                line-height: 1;
                font-size: 3rem;
                color: white;
                text-shadow: 0 1px 4px rgba(0, 0, 0, 0.6); /* glow effect */
                font-weight: bold;
                text-align: center;
            }

            .login-box {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);

                box-sizing: border-box;
                border: 1px solid rgba(255, 255, 255, 0.3);
                border-radius: 50px;
                background-color: rgba(255, 255, 255, 0.15);
                backdrop-filter: blur(10px);
                box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.25);
                padding: 30px 40px;
                max-width: 620px;
                width: 100%;
                
            }

            #banner {
                margin-bottom: 30px;
            }

            .alert {
                margin-bottom: 25px;
                border-radius: 10px;
                position: relative; 
                font-size: 1.5rem
            }

            .close {
                background: none;
                border: none;
                font-size: 2.2rem;
                color: #842029;
                opacity: 0.7;
                padding: 0 10px;
                cursor: pointer;
            }

            .close:hover {
                opacity: 1;
            }

            .login-container input[type="email"],
            .login-container input[type="password"] {
                width: 100%;
                padding: 0.75rem 1rem;
                margin: 1rem 0;
                border: none;
                border-radius: 8px;
                background: rgba(255, 255, 255, 0.85);
                font-size: 1.5rem;
                outline: none;
                transition: box-shadow 0.3s ease;
            }

            .login-container input[type="email"]:focus,
            .login-container input[type="password"]:focus {
                box-shadow: 0 0 5px rgba(0, 100, 255, 0.5);
            }

            /* Button Styling */
            .login-container button {
                width: 100%;
                padding: 0.75rem;
                margin-top: 1rem;
                background: #003366; /* Pisay blue tone */
                color: white;
                border: none;
                border-radius: 8px;
                font-weight: bold;
                font-size: 1.5rem;
                cursor: pointer;
                transition: background 0.3s ease;
            }

            .login-container button:hover {
                background: #002244;
            }

            #chartab th{
                color: white;
            }

            .form-control {
                font-size: 15px;
                padding: 10px;
                border-radius: 8px;
                margin-bottom: 15px;
                height: 45px;
            }
        </style>
    </head>

    <body>

        <div class="system-name">
            <span style="display: block; font-size: 2rem;">WELCOME TO</span>
            <span style="display: block; font-size: 5rem; font-weight: bold; margin-top: 0px;">RESERVE-A-LAB</span>
        </div>


        <div class="login-box">
            <div id="banner" style="margin-bottom: 30px; color: white; overflow: hidden;">
                <img src="img/pshsLogo.png" alt="PSHS Logo" style="height: 70px; float: left; margin-right: 20px;">
                <div style="line-height: 1.2; overflow: hidden;">
                    <div style="font-size: 20px;">Department of Science and Technology</div>
                    <div style="font-size: 20px; font-weight: bold;">PHILIPPINE SCIENCE HIGH SCHOOL</div>
                    <div style="font-size: 20px; font-weight: bold;">ILOCOS REGION CAMPUS</div>
                </div>
            </div>


            <?php if (isset($_SESSION['session_expired'])): ?>
                <div class="alert alert-danger alert-dismissible fade in" role="alert">
                    Your session has timed out. Please log in again.
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['session_expired']); ?>
            <?php endif; ?>


            <form id="practiceForm" class="login-container" onsubmit="loginUser(); return false;">
                <input type="email" class="form-control" id="username" name="username" placeholder="Email" required>
                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                <button type="submit" class="btn btn-primary">Log in</button>
            </form>
        </div>

        <script>
            function loginUser() {
                var email = $("#username").val();
                var password = $("#password").val();

                $.ajax({
                    url: 'ajax/ajax_login.php',
                    type: 'POST',
                    data: {
                        action: 'loginUser',
                        email: email,
                        password: password
                    },
                    success: function (response) {
                        $(".alert").remove();
                        if (response === "invalid_email") {
                            var alertBox = `
                            <div class="alert alert-danger alert-dismissible fade in" role="alert">
                                Email not found.
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        `;
                        $("#banner").after(alertBox);
                        } else if (response === "invalid_password") {
                            var alertBox = `
                            <div class="alert alert-danger alert-dismissible fade in" role="alert">
                                Incorrect password.
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        `;
                        $("#banner").after(alertBox);
                        } else if (response === "admin") {
                            //alert("Login successful! Redirecting to Admin Home...");
                            window.location.href = "admin_home.php";
                        } else if (response === "requestor") {
                            //alert("Login successful! Redirecting to Requestor Home...");
                            window.location.href = "requester_home.php";
                        } else {
                            alert("Unexpected response: " + response);
                        }
                    },
                    error: function () {
                        alert("An error occurred. Please try again.");
                    }
                });
            }
        </script>
    </body>
</html>