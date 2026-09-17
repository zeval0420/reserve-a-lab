<?php
require_once __DIR__ . '/../../src/bootstrap.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();

    $username = (string) ($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if (Auth::attempt($username, $password)) {
        $return = $_GET['return'] ?? '/admin/index.php';
        // Only ever redirect to a local admin path -- never follow an
        // absolute/external URL from the query string.
        if (!is_string($return) || !str_starts_with($return, '/admin/')) {
            $return = '/admin/index.php';
        }
        header('Location: ' . $return);
        exit;
    }

    $error = 'Incorrect username or password.';
}

if (Auth::check()) {
    header('Location: /admin/index.php');
    exit;
}

$pageTitle = 'Sign in';
require __DIR__ . '/includes/header.php';
?>
<div class="card" style="max-width:360px;margin:60px auto 0;">
    <h1>Sign in</h1>
    <p class="subtitle">Competition admin access</p>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <?= Csrf::field() ?>
        <div class="field">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required autofocus>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;">Sign in</button>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
