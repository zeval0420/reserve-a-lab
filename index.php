<?php
    // centralized db_connection and session_handler
    include('helperFiles/db_connection.php');
    include('helperFiles/session_handler.php');

    // Auto-redirect if session is valid
    $sessionRedirectScript = "";
    if (isset($_SESSION['role'])) {
        if ($_SESSION['role'] === 'admin') {
            header("Location: admin_home.php");
            exit();
        } elseif ($_SESSION['role'] === 'requester' || $_SESSION['role'] === 'teacher') {
            header("Location: requester_home.php");
            exit();
        }
    }
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Request-A-Lab Login</title>
        
        <?php include('helperFiles/headData.php'); ?>

        <style>
            input[type="password"]::-ms-reveal,
            input[type="password"]::-ms-clear,
            input[type="password"]::-webkit-contacts-auto-fill-button,
            input[type="password"]::-webkit-credentials-auto-fill-button {
                display: none !important;
                pointer-events: none;
                height: 0;
                width: 0;
                visibility: hidden;
            }

            body {
                display: flex;
                justify-content: center;
                align-items: center;
                box-sizing: border-box;
                min-height: 100vh;
                font-family: "Raleway", sans-serif;
                background-image: linear-gradient(rgba(0,0,0,0.45),rgba(0,0,0,0.45)),url(img/background.jpg);
                background-position: center;
                background-size: cover;
            }

            .system-name {
                position: absolute;
                top: 25%;
                left: 50%;
                transform: translate(-50%, -125%);
                padding: 20px;

                line-height: 1;
                font-size: 3rem;
                color: white;
                text-shadow: 0 1px 4px rgba(0, 0, 0, 0.6);
                font-weight: bold;
                text-align: center;
            }

            .system-name span:first-child {
                font-size: 2rem;
            }

            .system-name span:last-child {
                font-size: 5rem;
            }

            #banner img {
                height: 70px;
                margin-right: 20px;
            }
            #banner div {
                font-size: 20px;
            }

            @keyframes fadeInUp {
                0% {
                    opacity: 0;
                    transform: translateY(30px);
                }
                100% {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .login-box {
                animation: fadeInUp 0.8s ease forwards;

                box-sizing: border-box;
                border: 1px solid rgba(255, 255, 255, 0.25);
                border-radius: 25px;
                background: rgba(255, 255, 255, 0.1);
                backdrop-filter: blur(16px);
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
                padding: 40px;
                width: 100%;
                max-width: 620px;
                transition: box-shadow 0.4s ease;
            }

            .login-container input {
                /*width: 100%;*/
                padding: 1rem 1.5rem;
                margin: 1rem 0;
                border: none;
                border-radius: 24.5px;
                background: rgba(255, 255, 255, 0.2);
                backdrop-filter: blur(6px);
                color: white;
                font-size: 1.3rem;
                transition: all 0.3s ease;
                box-shadow: inset 0 0 4px rgba(255, 255, 255, 0.3);
            }

            .login-container input::placeholder {
                color: rgba(255, 255, 255, 0.7);
            }

            .login-container input:focus {
                outline: none;
                background: rgba(255, 255, 255, 0.25);
                box-shadow: 0 0 10px rgba(0, 100, 255, 0.4);
            }

            .login-container > button {
                width: 100%;
                padding: 0.9rem;
                margin-top: 1rem;
                background: rgba(0, 51, 102, 0.8);
                border: none;
                border-radius: 15px;
                font-weight: bold;
                font-size: 1.4rem;
                color: white;
                cursor: pointer;
                box-shadow: 0 4px 15px rgba(0, 51, 102, 0.3);
                backdrop-filter: blur(6px);
                transition: all 0.3s ease;
            }

            .login-container > button:hover {
                background: rgba(0, 51, 102, 1);
                transform: scale(1.02);
                box-shadow: 0 6px 20px rgba(0, 51, 102, 0.5);
            }

            .password-wrapper {
                position: relative;
            }

            .toggle-button {
                display: inline-flex;
                position: absolute;
                top: 50%;
                transform: translateY(-50%);
                left: unset;
                right: 12px;
                cursor: pointer;
            }

            .eye-icon {
                width: 20px;
                height: 20px;
            }

            #banner {
                margin-bottom: 30px;
            }

            .alert {
                backdrop-filter: blur(6px);
                background: rgba(255, 0, 0, 0.2);
                color: white;
                border: 1px solid rgba(255, 0, 0, 0.4);
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

            #chartab th{
                color: white;
            }

            .form-control {
                display: block;
                font-size: 15px;
                padding: 10px;
                border-radius: 8px;
                margin-bottom: 15px;
                height: 45px;
            }

            /* ===== Medium Screens ===== */
            @media (max-width: 992px) {
                .system-name {
                    margin-bottom: 35px;
                }

                .system-name span:first-child {
                    font-size: 1.67rem;
                }

                .system-name span:last-child {
                    font-size: 4.4rem;
                }

                .login-box {
                    max-width: 600px;
                }

                .login-container input {
                    font-size: 1.3rem;
                    padding: 1.1rem 1.5rem;
                }
            }

            /* ===== Small Screens ===== */
            @media (max-width: 768px) {
                body {
                    padding: 50px;
                }

                .system-name {
                    margin-bottom: 15px;
                }

                .system-name span:first-child {
                    font-size: 1.4rem;
                }

                .system-name span:last-child {
                    font-size: 2.6rem;
                }

                .login-box {
                    max-width: 100%;
                }

                #banner img {
                    display: block;
                    margin-right: 10px;
                    float: none;
                    height: 50px;
                }

                #banner div {
                    font-size: 13.5px;
                }

                .login-container input {
                    font-size: 1.1rem;
                    padding: 0.8rem 1.2rem;
                }

                .login-container > button {
                    font-size: 1.2rem;
                    padding: 0.9rem;
                }
            }
        </style>
    </head>

    <body>
        <div class="system-name">
            <span style="display: block;">WELCOME TO</span>
            <span style="display: block; font-weight: bold; margin-top: 0px;">RESERVE-A-LAB</span>
        </div>

        <div class="login-box">
            <div id="banner" style="margin-bottom: 30px; color: white; overflow: hidden;">
                <img src="img/logo.png" alt="PSHS Logo" style="float: left">
                <div style="line-height: 1.2; overflow: hidden;">
                    <div >Department of Science and Technology</div>
                    <div style="font-weight: bold;">ILOCOS REGION CAMPUS</div>
                    <div style="font-weight: bold;">PHILIPPINE SCIENCE HIGH SCHOOL</div>
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
                <input type="text" class="form-control" id="username" name="username" placeholder="Email or Username" required>

                <div class="password-wrapper">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    <div class="toggle-button">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="eye-icon">
                            <path d="M12 15a3 3 0 100-6 3 3 0 000 6z" />
                            <path fill-rule="evenodd" d="M1.323 11.447C2.811 6.976 7.028 3.75 12.001 3.75c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113-1.487 4.471-5.705 7.697-10.677 7.697-4.97 0-9.186-3.223-10.675-7.69a1.762 1.762 0 010-1.113zM17.25 12a5.25 5.25 0 11-10.5 0 5.25 5.25 0 0110.5 0z" clip-rule="evenodd" />
                        </svg>
                  </div>
                </div>
  
                <button type="submit" class="btn btn-primary">Log in</button>
            </form>
        </div>

        <script>
            $(document).ready(function () {
                // Animate login elements
                $(".login-container input[type='email'], .login-container button, .password-wrapper").each(function (i) {
                    const $el = $(this);
                    $el.css({
                        opacity: 0,
                        transform: "translateY(30px)"
                    });
                    setTimeout(function () {
                        $el.css({
                            transition: "all 0.6s ease",
                            opacity: 1,
                            transform: "translateY(0)"
                        });
                    }, 300 + i * 150);
                });

                // Eye icon SVGs
                const eyeIcons = {
                    open: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="eye-icon" style="width: 20px; height: 20px;"><path d="M12 15a3 3 0 100-6 3 3 0 000 6z" /><path fill-rule="evenodd" d="M1.323 11.447C2.811 6.976 7.028 3.75 12.001 3.75c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113-1.487 4.471-5.705 7.697-10.677 7.697-4.97 0-9.186-3.223-10.675-7.69a1.762 1.762 0 010-1.113zM17.25 12a5.25 5.25 0 11-10.5 0 5.25 5.25 0 0110.5 0z" clip-rule="evenodd" /></svg>`,
                    closed: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="eye-icon" style="width: 20px; height: 20px;"><path d="M3.53 2.47a.75.75 0 00-1.06 1.06l18 18a.75.75 0 101.06-1.06l-18-18zM22.676 12.553a11.249 11.249 0 01-2.631 4.31l-3.099-3.099a5.25 5.25 0 00-6.71-6.71L7.759 4.577a11.217 11.217 0 014.242-.827c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113z" /><path d="M15.75 12c0 .18-.013.357-.037.53l-4.244-4.243A3.75 3.75 0 0115.75 12zM12.53 15.713l-4.243-4.244a3.75 3.75 0 004.243 4.243z" /><path d="M6.75 12c0-.619.107-1.213.304-1.764l-3.1-3.1a11.25 11.25 0 00-2.63 4.31c-.12.362-.12.752 0 1.114 1.489 4.467 5.704 7.69 10.675 7.69 1.5 0 2.933-.294 4.242-.827l-2.477-2.477A5.25 5.25 0 016.75 12z" /></svg>`
                };

                // Add initial icon
                $(".toggle-button").html(eyeIcons.open);

                // Toggle functionality
                $(".toggle-button").on("click", function () {
                    const $button = $(this);
                    const $password = $("#password");
                    const isOpen = $button.hasClass("open");

                    $button.toggleClass("open");
                    $button.html(isOpen ? eyeIcons.open : eyeIcons.closed);
                    $password.attr("type", isOpen ? "password" : "text");
                });
            });
            function loginUser() {
                var email = $("#username").val();
                if (!email.match(/@.+/)) {
                    email = email + "@irc.pshs.edu.ph"
                }
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
                                Email or Username not found.
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
                        } else if (response === "requester" || response === "teacher") {
                            //alert("Login successful! Redirecting to Home...");
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