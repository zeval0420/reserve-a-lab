<?php
/**
 * Authentication helpers
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function isLoggedIn(): bool {
    return isset($_SESSION['sf_user_id']) && !empty($_SESSION['sf_user_id']);
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    return [
        'id'           => $_SESSION['sf_user_id'],
        'username'     => $_SESSION['sf_username'],
        'role'         => $_SESSION['sf_role'],
        'display_name' => $_SESSION['sf_display_name'],
    ];
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function login(string $username, string $password): bool {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM sf_users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['sf_user_id']      = $user['id'];
        $_SESSION['sf_username']     = $user['username'];
        $_SESSION['sf_role']         = $user['role'];
        $_SESSION['sf_display_name'] = $user['display_name'];

        // Update last login
        $db->prepare("UPDATE sf_users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
        return true;
    }
    return false;
}

function logout(): void {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}
