<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDB();
requireAdminLogin();

$base = '../';
$is_admin = true;
$active_nav = '';
$page_title = 'Change Password';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();

    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $username = $_SESSION['admin_username'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($current_password, $admin['password_hash'])) {
        $error = 'Your current password is incorrect.';
    } elseif (strlen($new_password) < 8) {
        $error = 'Your new password must be at least 8 characters long.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New password and confirmation do not match.';
    } elseif (password_verify($new_password, $admin['password_hash'])) {
        $error = 'Your new password must be different from your current password.';
    } else {
        $newHash = password_hash($new_password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE admin_users SET password_hash = ?, must_change_password = 0 WHERE id = ?")
            ->execute([$newHash, $admin['id']]);
        unset($_SESSION['must_change_password']);
        logActivity($pdo, 'Changed password', '');
        $success = 'Your password has been changed successfully.';
    }
}

require __DIR__ . '/../includes/header.php';
?>

<h1 class="page-title">Change Password</h1>

<?php if (!empty($_SESSION['must_change_password'])): ?>
  <div class="alert alert-error">For security, please choose a new password before you continue. You are still using the starting password.</div>
<?php endif; ?>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<div class="card" style="max-width:420px;">
  <div class="card-head"><h2>Update Your Admin Password</h2></div>
  <form method="post" action="change-password.php">
    <?= csrfField() ?>
    <div class="field">
      <label>Current Password</label>
      <input type="password" name="current_password" required autocomplete="current-password">
    </div>
    <div class="field" style="margin-top:14px;">
      <label>New Password</label>
      <input type="password" name="new_password" required autocomplete="new-password" minlength="8">
    </div>
    <div class="field" style="margin-top:14px;">
      <label>Confirm New Password</label>
      <input type="password" name="confirm_password" required autocomplete="new-password" minlength="8">
    </div>
    <div style="margin-top:16px;">
      <button type="submit" class="btn btn-primary">Change Password</button>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
