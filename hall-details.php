<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDB();

$base = '';
$is_admin = false;
$active_nav = 'halls';

$id = (int)($_GET['id'] ?? 0);
$hall = getHall($pdo, $id);

if (!$hall) {
    header('Location: halls.php');
    exit;
}

$page_title = $hall['hall_name'];
$schedule = getScheduleForHall($pdo, $id);
$resourceItems = parseHallResourceItems($hall['facilities'] ?? '');

require __DIR__ . '/includes/header.php';
?>

<a href="halls.php" class="view-all" style="display:inline-block;margin-bottom:12px;">&lsaquo; Back to Halls</a>

<div class="card hall-main-card">
  <div class="hall-details-head">
    <div>
      <h1 class="page-title" style="margin-bottom:2px;"><?= e($hall['hall_name']) ?></h1>
      <div class="meta"><?= e($hall['building']) ?> &middot; Room <?= e($hall['hall_number']) ?></div>
    </div>
    <?= statusBadgeHtml($hall['status']) ?>
  </div>

  <div class="hall-main-grid">
    <div class="hall-photo-area">
      <?php if (hallImageExists($hall['image_path'])): ?>
        <img class="hall-photo" src="<?= hallImageUrl($base, $hall['image_path']) ?>" alt="<?= e($hall['hall_name']) ?>">
      <?php else: ?>
        <div class="hall-photo hall-photo-placeholder" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 21V9l8-6 8 6v12M9 21v-6h6v6"/></svg>
          <span>No photo uploaded yet</span>
        </div>
      <?php endif; ?>
    </div>

    <div class="hall-info-panel">
      <h2 class="hall-info-title">Hall Information</h2>

      <div class="hall-info-row">
        <span class="hall-info-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg></span>
        <span class="hall-info-label">Capacity</span>
        <span class="hall-info-value"><?= (int)$hall['capacity'] ?></span>
      </div>

      <div class="hall-info-row">
        <span class="hall-info-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg></span>
        <span class="hall-info-label">Location</span>
        <span class="hall-info-value"><?= e($hall['building']) ?></span>
      </div>

      <div class="hall-info-row">
        <span class="hall-info-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg></span>
        <span class="hall-info-label">Current status</span>
        <span class="hall-info-value"><?= statusBadgeHtml($hall['status']) ?></span>
      </div>

      <?php if (!empty($hall['description'])): ?>
        <p class="hall-info-desc"><?= e($hall['description']) ?></p>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-head"><h2>Resources</h2></div>
  <?php if (empty($resourceItems)): ?>
    <div class="empty-state">No resources have been assigned to this hall yet.</div>
  <?php else: ?>
    <div class="resource-icon-grid">
      <?php foreach ($resourceItems as $item): ?>
        <div class="resource-icon-card">
          <?= resourceIconSvg($item) ?>
          <span><?= e($item) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-head"><h2>Upcoming Schedule</h2></div>
  <?php if (empty($schedule)): ?>
    <div class="empty-state">No upcoming classes in this hall.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Date</th><th>Time</th><th>Level</th><th>Course</th><th>Lecturer</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($schedule as $s): ?>
            <?php $lecturerName = trim(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? '')); ?>
            <tr class="<?= trim(classRowCancelledClass($s)) ?>">
              <td><?= e(date('D', strtotime($s['schedule_date'])) . ', ' . fmtDate($s['schedule_date'])) ?></td>
              <td><?= fmtTime($s['start_time']) ?> &ndash; <?= fmtTime($s['end_time']) ?></td>
              <td><?= e(levelLabel($s['level'] ?? '')) ?></td>
              <td><?= e($s['course_code']) ?></td>
              <td><?= $lecturerName !== '' ? e($lecturerName) : '&mdash;' ?></td>
              <td><?= classBadgesHtml($s) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
