<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDB();

$base = '';
$is_admin = false;
$active_nav = 'schedule';
$page_title = 'My Schedule';

// Classes are tied to real dates, so the student picks a date inside a week
// (Mon-Sat). ?date=YYYY-MM-DD selects it; default is today (Sunday -> Monday).
$today = date('Y-m-d');
$selected = $_GET['date'] ?? $today;
$dt = DateTime::createFromFormat('Y-m-d', $selected);
if (!$dt || $dt->format('Y-m-d') !== $selected) {
    $dt = new DateTime($today);
}
// Only this week and LAST week can be viewed. Anything older is clamped.
if ($selected < scheduleWindowStart()) {
    $dt = new DateTime($today);
}
if ($dt->format('N') === '7') {          // Sunday has no tab -> go to Monday
    $dt->modify('+1 day');
}
$selected = $dt->format('Y-m-d');

$weekStart = (clone $dt)->modify('monday this week');
if ($dt->format('N') === '1') { $weekStart = clone $dt; }
$weekDays = [];
for ($i = 0; $i < 6; $i++) {
    $d = (clone $weekStart)->modify("+$i day");
    $weekDays[] = $d;
}
$prevWeek = (clone $weekStart)->modify('-7 day')->format('Y-m-d');
$nextWeek = (clone $weekStart)->modify('+7 day')->format('Y-m-d');
$thisWeekStart = new DateTime('monday this week');
$thisWeekStartStr = $thisWeekStart->format('Y-m-d');
$weekStartStr = $weekStart->format('Y-m-d');
$isPastWeek = $weekStartStr < $thisWeekStartStr;
$canGoPrev = $weekStartStr > scheduleWindowStart();   // only one week back
$rows = getFullSchedule($pdo, $selected, null, $selected === $today);   // today: finished classes drop off


require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <div class="greeting-row">
    <div class="greeting"><h1 class="page-title" style="margin:0;">My Schedule</h1></div>
    <div class="date-pill">📅 <?= date('M j, Y') ?></div>
  </div>
</div>

<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;font-weight:700;">
    <?php if ($canGoPrev): ?><a href="?date=<?= e($prevWeek) ?>">&lsaquo; Previous week</a><?php else: ?><span style="opacity:.35;">&lsaquo; Previous week</span><?php endif; ?>
    <span><?= e($weekDays[0]->format('M j')) ?> &ndash; <?= e($weekDays[5]->format('M j, Y')) ?></span>
    <a href="?date=<?= e($nextWeek) ?>">Next week &rsaquo;</a>
  </div>
  <div class="tabs">
    <?php foreach ($weekDays as $d): $ds = $d->format('Y-m-d'); ?>
      <?php if ($ds < $today && !$isPastWeek): ?>
        <span class="tab-btn disabled" title="This day has passed"><?= e($d->format('D j')) ?></span>
      <?php else: ?>
        <a class="tab-btn <?= $ds === $selected ? 'active' : '' ?>" href="?date=<?= e($ds) ?>"><?= e($d->format('D j')) ?></a>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
  <?php if ($isPastWeek): ?>
    <div class="alert" style="margin-top:10px;font-weight:700;background:var(--border);">Last week &mdash; a record of the classes that were held. This week's days that have passed are no longer listed.</div>
  <?php endif; ?>

  <?php if (empty($rows)): ?>
    <div class="empty-state">No classes scheduled for <?= e($dt->format('l, M j, Y')) ?>.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Time</th><th>Level</th><th>Course Code</th><th>Course Title</th><th>Hall</th><th>Lecturer</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
          <tr class="<?= trim(classRowCancelledClass($r)) ?>">
            <td><?= fmtTime($r['start_time']) ?> &ndash; <?= fmtTime($r['end_time']) ?></td>
            <td><?= e(levelLabel($r['level'] ?? '')) ?></td>
            <td><strong><?= e($r['course_code']) ?></strong> <?= classBadgesHtml($r) ?></td>
            <td><?= e($r['course_title']) ?></td>
            <td><a href="hall-details.php?id=<?= (int)$r['hall_id'] ?>"><?= e($r['hall_name']) ?></a></td>
            <td><?= e(trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''))) ?: '—' ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
