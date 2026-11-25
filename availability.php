<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDB();

$base = '';
$is_admin = false;
$active_nav = 'availability';
$page_title = 'Hall Availability';

$halls = getAllHalls($pdo);
$allDays = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];

// Dots reflect THIS week only (Mon-Sun, real dates) — a class on a past or
// future date no longer marks that weekday busy forever.
$weekStart = new DateTime('monday this week');
if (date('N') === '1') { $weekStart = new DateTime('today'); }
$weekEnd = (clone $weekStart)->modify('+6 day');
$busyDaysByHall = [];
[$frag, $fragParams] = activeClassSql('', true);
$stmt = $pdo->prepare("SELECT hall_id, schedule_date FROM hall_schedule WHERE schedule_date BETWEEN ? AND ? AND " . $frag);
$stmt->execute(array_merge([$weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d')], $fragParams));
foreach ($stmt as $row) {
    $busyDaysByHall[$row['hall_id']][] = date('D', strtotime($row['schedule_date']));
}

require __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Hall Availability</h1>
<p style="margin:0 0 10px;font-weight:600;">This week: <?= e($weekStart->format('M j')) ?> &ndash; <?= e($weekEnd->format('M j, Y')) ?></p>

<div class="legend">
  <span><span class="dot available"></span> Available</span>
  <span><span class="dot inuse"></span> In Use</span>
  <span><span class="dot maintenance"></span> Maintenance</span>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Hall Name</th><th>Status</th><th>Available Days</th></tr></thead>
      <tbody>
        <?php foreach ($halls as $h): $busy = $busyDaysByHall[$h['id']] ?? []; ?>
          <tr>
            <td><a href="hall-details.php?id=<?= (int)$h['id'] ?>"><?= e($h['hall_name']) ?></a></td>
            <td><?= statusBadgeHtml($h['status']) ?></td>
            <td>
              <?php foreach ($allDays as $d):
                  $isBusy = in_array($d, $busy);
                  $dotClass = $h['status'] === 'Maintenance' ? 'maintenance' : ($isBusy ? 'inuse' : 'available');
              ?>
                <span class="dot <?= $dotClass ?>" title="<?= $d ?>" style="margin-right:4px;"></span>
              <?php endforeach; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
