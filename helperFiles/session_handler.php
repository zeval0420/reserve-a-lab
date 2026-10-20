<?php
    // Session cookie: not readable by scripts, not sent on cross-site POSTs, HTTPS-only when the site is served over HTTPS
    if (session_status() === PHP_SESSION_NONE) {
        $ral_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
        ini_set('session.use_strict_mode', '1');   // never adopt a session ID the server did not issue
        ini_set('session.use_only_cookies', '1');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $ral_https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    session_start();
    $timeout_duration = 28800; // 8 hours

    // Emailed approval links work without a login, so an expired session must not bounce those visitors to index.php
    $ral_link_flow = in_array(basename($_SERVER['PHP_SELF']), ['supervisor_approve.php', 'ajax_supervisor_action.php'], true);

    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration && $ral_link_flow) {
        session_unset();
        session_regenerate_id(true);
    } elseif (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
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

    /*
     * Attempt throttling, kept in the visitor's own session (no database table).
     * A bucket counts attempts inside a window; once it reaches $max the visitor is
     * locked out for 30 s, doubling with every further attempt up to 15 minutes.
     * Limit: it follows the session cookie, so it slows ordinary guessing and
     * scripted form abuse but a client that discards its cookie starts afresh.
     */
    if (!function_exists('ral_throttle_wait')) {
        /** Seconds the visitor must still wait for this bucket (0 = free to try). */
        function ral_throttle_wait($bucket)
        {
            $until = (int)($_SESSION['_throttle'][$bucket]['locked_until'] ?? 0);
            return max(0, $until - time());
        }

        /** The message to show a locked-out visitor. */
        function ral_throttle_message($seconds)
        {
            return 'Too many attempts. Please try again in ' . ($seconds >= 60 ? ceil($seconds / 60) . ' minute(s)' : $seconds . ' second(s)') . '.';
        }

        /** Record one attempt (a failed login, or any account/e-mail request). */
        function ral_throttle_hit($bucket, $max = 5, $window = 900)
        {
            $now = time();
            $state = $_SESSION['_throttle'][$bucket] ?? ['count' => 0, 'first' => $now, 'locked_until' => 0];
            if ($now - $state['first'] > $window && $state['locked_until'] <= $now) {
                $state = ['count' => 0, 'first' => $now, 'locked_until' => 0];
            }
            $state['count']++;
            if ($state['count'] >= $max) {
                $state['locked_until'] = $now + min(900, 30 * (2 ** ($state['count'] - $max)));
            }
            $_SESSION['_throttle'][$bucket] = $state;
        }

        /** Forget a bucket, e.g. after a successful login. */
        function ral_throttle_clear($bucket)
        {
            unset($_SESSION['_throttle'][$bucket]);
        }
    }
?>