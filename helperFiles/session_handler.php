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

    /*
     * Access-control helpers for the ajax/ endpoints. An endpoint calls these right
     * after including this file; the checks are not applied automatically, because
     * ordinary pages also include this file.
     */
    if (!function_exists('ral_deny')) {
        /** End the request with a JSON error and an HTTP status. */
        function ral_deny($httpStatus, $message, $status = 'error')
        {
            http_response_code($httpStatus);
            header('Content-Type: application/json');
            echo json_encode(['status' => $status, 'success' => false, 'message' => $message]);
            exit();
        }

        function ral_is_logged_in()
        {
            return !empty($_SESSION['role']) && !empty($_SESSION['email']);
        }

        /** 401 unless a user is logged in. */
        function ral_require_login()
        {
            if (!ral_is_logged_in()) {
                ral_deny(401, 'Please log in again.', 'session_expired');
            }
        }

        /** 401 unless logged in, 403 unless the user's role is one of $roles. */
        function ral_require_role(array $roles)
        {
            ral_require_login();
            if (!in_array($_SESSION['role'], $roles, true)) {
                ral_deny(403, 'You do not have permission to do that.');
            }
        }

        /**
         * 403 unless a state-changing request carries the session's CSRF token, as the
         * X-CSRF-Token header (added by headData.php for jQuery and fetch) or a
         * csrf_token field. GET, HEAD and OPTIONS requests are not checked, so they
         * must never change data.
         */
        function ral_require_csrf()
        {
            if (in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD', 'OPTIONS'], true)) {
                return;
            }
            $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
            $expected = $_SESSION['csrf_token'] ?? '';
            if (!is_string($sent) || $expected === '' || !hash_equals($expected, $sent)) {
                ral_deny(403, 'Invalid or missing security token. Please refresh the page and try again.');
            }
        }
    }
?>