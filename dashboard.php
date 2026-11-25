<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDB();
requireAdminLogin();

$base = '../';
$is_admin = true;
$active_nav = 'dashboard';
$page_title = 'Admin Dashboard';

$totalHalls = countHalls($pdo);
$todaysClasses = countTodaysClasses($pdo);
$totalBookings = countBookings($pdo);
$totalResources = countResourceItems($pdo);

$todaysSchedule = getTodaysClasses($pdo);
$halls = getAllHalls($pdo);
$recentBookings = getRecentBookings($pdo, 5);

require __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
  <div class="greeting-row">
    <div class="greeting">
      <h1 class="page-title" style="margin:0;">Welcome back, Admin!</h1>
      <p class="sub" style="margin-top:4px;">Here's an overview of the scheduling system.</p>
    </div>
    <div class="date-pill">📅 <?= date('M j, Y') ?></div>
  </div>
</div>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-icon c-blue">
      <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 21V9l8-6 8 6v12M9 21v-6h6v6"/></svg>
    </div>
    <div class="stat-value"><?= $totalHalls ?></div>
    <div class="stat-label">Total Halls</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon c-green">
      <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>
    </div>
    <div class="stat-value"><?= $todaysClasses ?></div>
    <div class="stat-label">Today's Classes</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon c-purple">
      <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4M2 12a10 10 0 1 0 20 0 10 10 0 0 0-20 0z"/></svg>
    </div>
    <div class="stat-value"><?= $totalBookings ?></div>
    <div class="stat-label">Total Bookings</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon c-orange">
      <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7h-9m9 5h-9m9 5h-9M5 7h.01M5 12h.01M5 17h.01"/></svg>
    </div>
    <div class="stat-value"><?= $totalResources ?></div>
    <div class="stat-label">Total Resources</div>
  </div>
</div>

<div class="card">
  <div class="card-head">
    <h2>Today's Schedule</h2>
    <a class="view-all" href="schedules.php">View All &rsaquo;</a>
  </div>
  <?php if (empty($todaysSchedule)): ?>
    <div class="empty-state">No classes today.</div>
  <?php else: foreach ($todaysSchedule as $cls): ?>
    <div class="class-row<?= classRowCancelledClass($cls) ?>">
      <div class="class-time"><?= fmtTime($cls['start_time']) ?> &ndash; <?= fmtTime($cls['end_time']) ?></div>
      <div class="class-info">
        <div class="code"><?= e($cls['course_code']) ?> <?= classBadgesHtml($cls) ?></div>
        <div class="title"><?= e($cls['course_title']) ?></div>
        <div class="loc">📍 <?= e($cls['hall_name']) ?></div>
      </div>
    </div>
  <?php endforeach; endif; ?>
</div>

<div class="card">
  <div class="card-head"><h2>Hall Availability</h2></div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Hall</th><th>Building</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($halls as $h): ?>
          <tr>
            <td><?= e($h['hall_name']) ?></td>
            <td><?= e($h['building']) ?></td>
            <td><?= statusBadgeHtml($h['status']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-head"><h2>Recent Bookings</h2></div>
  <?php if (empty($recentBookings)): ?>
    <div class="empty-state">No bookings yet.</div>
  <?php else: foreach ($recentBookings as $b): ?>
    <div class="class-row">
      <div class="class-info">
        <div class="code"><?= e($b['course_code']) ?> &middot; <?= e($b['hall_name']) ?></div>
        <div class="title"><?= $b['schedule_date'] ? e(date('D, M j, Y', strtotime($b['schedule_date']))) : e($b['day_of_week']) ?>, <?= fmtTime($b['start_time']) ?>&ndash;<?= fmtTime($b['end_time']) ?></div>
      </div>
    </div>
  <?php endforeach; endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
