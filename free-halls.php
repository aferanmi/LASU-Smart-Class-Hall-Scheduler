<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDB();

$base = '';
$is_admin = false;
$active_nav = 'freehalls';
$page_title = 'Find a Free Hall';

$today = date('Y-m-d');
$maxDate = (new DateTime('today'))->modify('+60 day')->format('Y-m-d');

$searched = isset($_GET['date'], $_GET['start_time'], $_GET['end_time']);
$date = $_GET['date'] ?? $today;
$start = $_GET['start_time'] ?? '';
$end = $_GET['end_time'] ?? '';
$error = '';
$freeHalls = [];

if ($searched) {
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    $dateOk = $dt && $dt->format('Y-m-d') === $date;
    $timeOk = preg_match('/^\d{2}:\d{2}$/', $start) && preg_match('/^\d{2}:\d{2}$/', $end);

    if (!$dateOk || !$timeOk) {
        $error = 'Please choose a date, a start time and an end time.';
    } elseif ($date < $today || $date > $maxDate) {
        $error = 'Choose a date from today up to ' . fmtDate($maxDate) . '.';
    } elseif ($start >= $end) {
        $error = 'The end time must be after the start time.';
    } elseif ($date === $today && $end <= date('H:i')) {
        $error = 'That time has already passed today.';
    } else {
        $stmt = $pdo->prepare("SELECT DISTINCT hall_id FROM hall_schedule
            WHERE schedule_date = ? AND status <> 'Cancelled' AND start_time < ? AND end_time > ?");
        $stmt->execute([$date, $end . ':00', $start . ':00']);
        $busy = array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));
        foreach (getAllHalls($pdo, true) as $h) {          // stored status: only Maintenance blocks a hall
            if ($h['status'] === 'Maintenance' || isset($busy[$h['id']])) continue;
            $freeHalls[] = $h;
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Find a Free Hall</h1>

<div class="card">
  <form method="get" action="free-halls.php">
    <div class="form-grid cols-2">
      <div class="field">
        <label for="fh_date">Date</label>
        <input type="date" id="fh_date" name="date" required min="<?= e($today) ?>" max="<?= e($maxDate) ?>" value="<?= e($date) ?>">
      </div>
      <div class="field"></div>
      <div class="field">
        <label for="fh_start">From</label>
        <input type="time" id="fh_start" name="start_time" required value="<?= e($start) ?>">
      </div>
      <div class="field">
        <label for="fh_end">To</label>
        <input type="time" id="fh_end" name="end_time" required value="<?= e($end) ?>">
      </div>
    </div>
    <div style="margin-top:16px;"><button type="submit" class="btn btn-primary">Show free halls</button></div>
  </form>
</div>

<?php if ($error): ?>
  <div class="alert alert-error"><?= e($error) ?></div>
<?php elseif ($searched): ?>
  <div class="card">
    <div class="card-head"><h2>Free on <?= e(date('D, M j', strtotime($date))) ?>, <?= e(fmtTime($start)) ?> &ndash; <?= e(fmtTime($end)) ?></h2></div>
    <?php if (empty($freeHalls)): ?>
      <div class="empty-state">No hall is free for that time. Try a different time or date.</div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="data-table">
          <thead><tr><th>Hall</th><th>Building</th><th>Capacity</th></tr></thead>
          <tbody>
            <?php foreach ($freeHalls as $h): ?>
              <tr>
                <td><a href="hall-details.php?id=<?= (int)$h['id'] ?>"><strong><?= e($h['hall_name']) ?></strong></a></td>
                <td><?= e($h['building']) ?></td>
                <td><?= (int)$h['capacity'] ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
