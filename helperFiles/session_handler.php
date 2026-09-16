<?php
    session_start();
    $timeout_duration = 28800; // 8 hours

    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
        session_unset();
        session_destroy();
        $_SESSION['session_expired'] = true;

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' ||
                  (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

        if ($isAjax) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'session_expired']);
            exit();
        }

        $redirect = '';
        if (basename($_SERVER['PHP_SELF']) !== 'index.php') {
            $redirect = '?redirect=' . urlencode(basename($_SERVER['REQUEST_URI']));
        }
        header("Location: index.php" . $redirect);
        exit();
    }
    $_SESSION['last_activity'] = time();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
?>