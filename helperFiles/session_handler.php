<?php
    session_start();
    $timeout_duration = 28800; // 8 hours

    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
        session_unset();
        session_destroy();
        $_SESSION['session_expired'] = true;
        $redirect = '';
        if (basename($_SERVER['PHP_SELF']) !== 'index.php') {
            $redirect = '?redirect=' . urlencode(basename($_SERVER['REQUEST_URI']));
        }
        header("Location: index.php" . $redirect);
        exit();
    }
    $_SESSION['last_activity'] = time();
?>