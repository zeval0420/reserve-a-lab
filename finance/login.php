<?php
require_once 'includes/auth.php';
if (isLoggedIn()) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (login(trim($_POST['username'] ?? ''), $_POST['password'] ?? '')) {
        header('Location: index.php');
        exit;
    }
    $error = 'Invalid username or password.';
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — SchoolFinance</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.2.0/remixicon.min.css">
<link rel="stylesheet" href="assets/css/main.css">
<script src="assets/js/app.js" defer></script>
<style>
  body { display: flex; align-items: center; justify-content: center; min-height: 100vh; }
  .login-card {
    background: var(--surface-card);
    border: 1px solid var(--surface-border);
    border-radius: var(--radius-xl);
    padding: 40px;
    width: 100%;
    max-width: 380px;
    box-shadow: var(--shadow-xl);
    animation: slideUp .4s both;
  }
  .login-logo {
    width: 52px; height: 52px;
    background: var(--blue-600);
    border-radius: var(--radius-md);
    display: flex; align-items: center; justify-content: center;
    font-size: 26px; color: white;
    margin: 0 auto 20px;
    box-shadow: var(--shadow-blue);
  }
  .login-title { text-align: center; font-size: 22px; font-weight: 800; letter-spacing: -.5px; margin-bottom: 4px; }
  .login-sub   { text-align: center; font-size: 13px; color: var(--text-secondary); margin-bottom: 28px; }
  .error-msg {
    background: var(--red-50); color: var(--red-500);
    border: 1px solid #FECACA; border-radius: var(--radius-sm);
    padding: 10px 14px; font-size: 13px; margin-bottom: 16px;
    display: flex; align-items: center; gap: 8px;
  }
  .login-footer { text-align: center; font-size: 12px; color: var(--text-muted); margin-top: 20px; }
  .hint-box {
    background: var(--blue-50); border: 1px solid var(--blue-200);
    border-radius: var(--radius-sm); padding: 10px 14px;
    font-size: 12px; color: var(--blue-700); margin-bottom: 16px;
    line-height: 1.6;
  }
</style>
</head>
<body>
<div class="login-card">
  <div class="login-logo"><i class="ri-bank-line"></i></div>
  <h1 class="login-title">SchoolFinance</h1>
  <p class="login-sub">Finance Management System</p>

  <div class="hint-box">
    <strong>Demo credentials:</strong><br>
    Username: <code>treasurer</code> or <code>auditor</code><br>
    Password: <code>password</code>
  </div>

  <?php if ($error): ?>
  <div class="error-msg"><i class="ri-error-warning-line"></i><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="login.php">
    <div class="form-group">
      <label class="form-label">Username</label>
      <input type="text" name="username" class="form-control" placeholder="Enter username"
             value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" autofocus required>
    </div>
    <div class="form-group">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" placeholder="Enter password" required>
    </div>
    <button type="submit" class="btn btn-primary w-full" style="width:100%;justify-content:center;margin-top:4px">
      <i class="ri-login-circle-line"></i> Sign In
    </button>
  </form>

  <div class="login-footer">
    <a href="public_dashboard.php">View Public Dashboard</a> · No login required
  </div>
</div>
</body>
</html>
