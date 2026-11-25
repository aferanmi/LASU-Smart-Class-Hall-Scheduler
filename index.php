<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDB();

$base = '';
$is_admin = false;
$active_nav = 'dashboard';
$page_title = 'Dashboard';

$totalClasses = countTotalClasses($pdo);
$todaysClasses = countTodaysClasses($pdo);
$availableHalls = countHalls($pdo, 'Available');

$upcoming = array_slice(getTodaysClasses($pdo), 0, 3);
if (empty($upcoming)) {
    $upcoming = array_slice(getFullSchedule($pdo, null, date('Y-m-d'), true), 0, 3);
}

$hour = (int)date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <div class="greeting-row">
    <div class="greeting">
      <p class="eyebrow"><?= e($greeting) ?>,</p>
      <h1>Student!</h1>
      <p class="sub">Here's what's happening today.</p>
    </div>
    <div class="date-pill">📅 <?= date('M j, Y') ?></div>
  </div>
</div>

<div class="stat-grid stat-grid-3">
  <div class="stat-card">
    <div class="stat-icon c-blue">
      <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>
    </div>
    <div class="stat-value"><?= $totalClasses ?></div>
    <div class="stat-label">Total Classes</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon c-green">
      <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/></svg>
    </div>
    <div class="stat-value"><?= $todaysClasses ?></div>
    <div class="stat-label">Today's Classes</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon c-purple">
      <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 21V9l8-6 8 6v12M9 21v-6h6v6"/></svg>
    </div>
    <div class="stat-value"><?= $availableHalls ?></div>
    <div class="stat-label">Available Halls</div>
  </div>
</div>

<div class="card">
  <div class="card-head">
    <h2>📅 Upcoming Classes</h2>
    <a class="view-all" href="schedule.php">View All &rsaquo;</a>
  </div>
  <?php if (empty($upcoming)): ?>
    <div class="empty-state">No classes scheduled yet.</div>
  <?php else: foreach ($upcoming as $cls): ?>
    <a class="class-row<?= classRowCancelledClass($cls) ?>" href="hall-details.php?id=<?= (int)$cls['hall_id'] ?>">
      <div class="class-time"><?= fmtTime($cls['start_time']) ?> &ndash; <?= fmtTime($cls['end_time']) ?></div>
      <div class="class-info">
        <div class="code"><?= e($cls['course_code']) ?> &middot; <?= e(date('D, M j', strtotime($cls['schedule_date']))) ?><?= !empty($cls['level']) ? ' &middot; ' . e($cls['level']) . 'L' : '' ?> <?= classBadgesHtml($cls) ?></div>
        <div class="title"><?= e($cls['course_title']) ?></div>
        <div class="loc">📍 <?= e($cls['hall_name']) ?></div>
      </div>
    </a>
  <?php endforeach; endif; ?>
</div>

<div class="card">
  <div class="card-head"><h2>⚡ Quick Links</h2></div>
  <div class="quicklink-grid">
    <a class="quicklink" href="schedule.php">View Full Schedule &rsaquo;</a>
    <a class="quicklink" href="availability.php">Check Hall Availability &rsaquo;</a>
    <a class="quicklink" href="halls.php">Class Halls &rsaquo;</a>
    <a class="quicklink" href="announcements.php">Announcements &rsaquo;</a>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
