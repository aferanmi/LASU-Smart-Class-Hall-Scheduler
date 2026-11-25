<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Security headers — sent before any HTML output.
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self'; img-src 'self' data:; base-uri 'self'; form-action 'self'");

$pdo = getDB();

if (isAdminLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $lockLeft = loginLockMinutesLeft($pdo);
    if ($lockLeft > 0) {
        $error = 'Too many wrong attempts. Please try again in ' . $lockLeft . ' minute' . ($lockLeft === 1 ? '' : 's') . '.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            clearLoginFailures($pdo);
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['must_change_password'] = !empty($admin['must_change_password']);
            logActivity($pdo, 'Logged in', '');
            header('Location: ' . ($_SESSION['must_change_password'] ? 'change-password.php' : 'dashboard.php'));
            exit;
        } else {
            recordLoginFailure($pdo);
            $left = LOGIN_MAX_ATTEMPTS - (int)loginRecentFailures($pdo)['c'];
            $error = $left > 0
                ? 'Invalid username or password. ' . $left . ' attempt' . ($left === 1 ? '' : 's') . ' left before a ' . LOGIN_LOCK_MINUTES . '-minute lock.'
                : 'Too many wrong attempts. Please try again in ' . LOGIN_LOCK_MINUTES . ' minutes.';
        }
    }
}

$base = '../';
$is_admin = true;
$page_title = 'Admin Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login · LASU Smart Class Hall Scheduler</title>
<link rel="stylesheet" href="<?= $base ?>assets/css/style.css">
</head>
<body class="student-body">

<header class="topbar">
  <div class="topbar-left">
    <div class="logo-badge" aria-hidden="true">
      <img src="<?= $base ?>assets/img/lasu-logo.png" alt="LASU logo" width="34" height="35">
    </div>
    <div class="topbar-titles">
      <span class="topbar-title">LASU Smart Class Hall Scheduler</span>
      <span class="topbar-subtitle">Smart Class Hall Scheduling &amp; Resource Management System</span>
    </div>
  </div>
</header>

<div class="login-wrap">
  <div class="login-card">
    <div class="logo-badge" style="margin:0 auto;width:56px;">
      <img src="<?= $base ?>assets/img/lasu-logo.png" alt="LASU logo" width="56" height="58">
    </div>
    <h1>Administrator Login</h1>
    <p class="subtitle">Sign in to manage halls, courses, schedules and more.</p>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form class="login-form" method="post" action="login.php">
      <?= csrfField() ?>
      <div class="field">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required autocomplete="username" value="<?= e($_POST['username'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">
      </div>
      <button type="submit" class="btn btn-primary btn-block">Login</button>
    </form>

    <a class="back-home" href="../index.php">&lsaquo; Back to Home</a>
  </div>
</div>

</body>
</html>
