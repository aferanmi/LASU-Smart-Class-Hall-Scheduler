<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDB();
requireAdminLogin();

$base = '../';
$is_admin = true;
$active_nav = 'activity';
$page_title = 'Activity Log';

$entries = $pdo->query("SELECT * FROM admin_log ORDER BY id DESC LIMIT 200")->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<h1 class="page-title">Activity Log</h1>

<div class="card">
  <div class="card-head"><h2>Latest 200 admin actions</h2></div>
  <?php if (empty($entries)): ?>
    <div class="empty-state">No activity recorded yet.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>When</th><th>Admin</th><th>Action</th><th>Details</th></tr></thead>
        <tbody>
          <?php foreach ($entries as $en): ?>
            <tr>
              <td><?= e(date('M j, Y g:i A', strtotime($en['created_at']))) ?></td>
              <td><?= e($en['admin_username']) ?></td>
              <td><strong><?= e($en['action']) ?></strong></td>
              <td><?= e($en['details']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
