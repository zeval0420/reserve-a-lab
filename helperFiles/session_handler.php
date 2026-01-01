<?php
    session_start();
    $timeout_duration = 28800; // 8 hours

    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
        session_unset();
        session_destroy();
        $_SESSION['session_expired'] = true;
        header("Location: index.php");
        exit();
    }

    $_SESSION['last_activity'] = time();
?>