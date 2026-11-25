<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDB();
requireAdminLogin();

$base = '../';
$is_admin = true;
$active_nav = 'schedules';
$page_title = 'Manage Schedules';

$error = '';
$success = '';
$editRow = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrfCheck();
    $pdo->prepare("DELETE FROM hall_schedule WHERE id = ?")->execute([(int)$_POST['id']]);
    logActivity($pdo, 'Deleted schedule', 'Schedule #' . (int)$_POST['id']);
    header('Location: schedules.php');
    exit;
}

// Delete every class older than the visible window (before last week's Monday).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'clear_old') {
    csrfCheck();
    $stmt = $pdo->prepare("DELETE FROM hall_schedule WHERE schedule_date IS NOT NULL AND schedule_date < ?");
    $stmt->execute([scheduleWindowStart()]);
    logActivity($pdo, 'Cleared old schedules', $stmt->rowCount() . ' removed');
    $success = $stmt->rowCount() . ' old class(es) deleted.';
}

// Cancel a class (students see it marked Cancelled) or restore it.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_cancel') {
    csrfCheck();
    $sid = (int)($_POST['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM hall_schedule WHERE id = ?");
    $stmt->execute([$sid]);
    $row = $stmt->fetch();
    if ($row) {
        if ($row['status'] === 'Cancelled') {
            $conflict = findScheduleConflict($pdo, $row['hall_id'], $row['lecturer_id'], $row['schedule_date'], $row['start_time'], $row['end_time'], $sid);
            if ($conflict) {
                $error = 'Cannot restore this class: ' . $conflict;
            } else {
                $pdo->prepare("UPDATE hall_schedule SET status = 'Active' WHERE id = ?")->execute([$sid]);
                logActivity($pdo, 'Restored class', 'Schedule #' . $sid);
                $success = 'Class restored.';
            }
        } else {
            $pdo->prepare("UPDATE hall_schedule SET status = 'Cancelled' WHERE id = ?")->execute([$sid]);
            logActivity($pdo, 'Cancelled class', 'Schedule #' . $sid);
            $success = 'Class cancelled. Students will see it marked as Cancelled.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['add', 'update'])) {
    csrfCheck();
    $hall_id = (int)($_POST['hall_id'] ?? 0);
    $course_id = (int)($_POST['course_id'] ?? 0);
    $lecturer_id = $_POST['lecturer_id'] !== '' ? (int)$_POST['lecturer_id'] : null;
    $start = $_POST['start_time'] ?? '';
    $end = $_POST['end_time'] ?? '';
    $date = trim($_POST['schedule_date'] ?? '');
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    $dateValid = $dt && $dt->format('Y-m-d') === $date;
    $day = $dateValid ? $dt->format('l') : '';   // weekday is worked out from the date
    $level = $_POST['level'] ?? '';
    $repeatUntil = trim($_POST['repeat_until'] ?? '');
    $checkConflicts = isset($_POST['check_conflicts']);
    $editId = $_POST['id'] ?? null;

    if (!$hall_id || !$course_id || !$dateValid || !$start || !$end || !in_array($level, scheduleLevels(), true)) {
        $error = 'Hall, course, level, date, start time and end time are required.';
    } elseif ($date < date('Y-m-d')) {
        $error = 'You cannot schedule a class on a date that has already passed. Pick today or a future date.';
    } elseif ($start >= $end) {
        $error = 'End time must be after start time.';
    } elseif ($_POST['action'] === 'add') {
        // Every date this booking covers: the chosen date, plus every week after
        // it up to "repeat until" (optional, max 26 weeks).
        $dates = [$date];
        if ($repeatUntil !== '') {
            $ru = DateTime::createFromFormat('Y-m-d', $repeatUntil);
            if (!$ru || $ru->format('Y-m-d') !== $repeatUntil || $repeatUntil <= $date) {
                $error = 'The "repeat until" date must come after the first class date.';
            } else {
                $next = (clone $dt)->modify('+7 day');
                while ($next <= $ru && count($dates) < 27) {
                    $dates[] = $next->format('Y-m-d');
                    $next->modify('+7 day');
                }
            }
        }
        if (!$error && $checkConflicts) {
            foreach ($dates as $d) {
                $c = findScheduleConflict($pdo, $hall_id, $lecturer_id, $d, $start, $end, null);
                if ($c) { $error = 'Scheduling conflict: ' . $c . ' Nothing was saved.'; break; }
            }
        }
        if (!$error) {
            $ins = $pdo->prepare("INSERT INTO hall_schedule (hall_id, course_id, lecturer_id, day_of_week, start_time, end_time, schedule_date, level) VALUES (?,?,?,?,?,?,?,?)");
            $pdo->beginTransaction();
            foreach ($dates as $d) {
                $ins->execute([$hall_id, $course_id, $lecturer_id, date('l', strtotime($d)), $start, $end, $d, $level]);
            }
            $pdo->commit();
            logActivity($pdo, 'Added schedule', count($dates) . ' class(es) from ' . fmtDate($date));
            $success = count($dates) > 1
                ? count($dates) . ' weekly classes added (every ' . $day . ' until ' . fmtDate(end($dates)) . ').'
                : 'Schedule added successfully.';
        }
    } else {
        $conflict = $checkConflicts
            ? findScheduleConflict($pdo, $hall_id, $lecturer_id, $date, $start, $end, $editId)
            : null;
        if ($conflict) {
            $error = 'Scheduling conflict: ' . $conflict;
        } else {
            // If the hall changed, remember where the class was before ("Moved from ...").
            $stmt = $pdo->prepare("SELECT hs.hall_id, hs.moved_from, h.hall_name FROM hall_schedule hs JOIN halls h ON hs.hall_id = h.id WHERE hs.id = ?");
            $stmt->execute([(int)$editId]);
            $old = $stmt->fetch();
            $movedFrom = $old['moved_from'] ?? null;
            if ($old && (int)$old['hall_id'] !== $hall_id) {
                $h = getHall($pdo, $hall_id, true);
                $origin = $old['moved_from'] ?: $old['hall_name'];
                $movedFrom = ($h && $h['hall_name'] === $origin) ? null : $origin;
            }
            $pdo->prepare("UPDATE hall_schedule SET hall_id=?, course_id=?, lecturer_id=?, day_of_week=?, start_time=?, end_time=?, schedule_date=?, moved_from=?, level=? WHERE id=?")
                ->execute([$hall_id, $course_id, $lecturer_id, $day, $start, $end, $date, $movedFrom, $level, (int)$editId]);
            logActivity($pdo, 'Updated schedule', 'Schedule #' . (int)$editId . ' on ' . fmtDate($date));
            $success = 'Schedule updated successfully.' . ($movedFrom ? ' Students will see it marked as moved.' : '');
        }
    }
}

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM hall_schedule WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editRow = $stmt->fetch();
}

$halls = getAllHalls($pdo);
$courses = getAllCourses($pdo);
$lecturers = getAllLecturers($pdo);
$schedule = getFullSchedule($pdo, null, scheduleWindowStart());   // this week, upcoming, and last week only

require __DIR__ . '/../includes/header.php';
?>

<h1 class="page-title">Manage Schedules</h1>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<?php if (empty($halls) || empty($courses)): ?>
  <div class="alert alert-error">
    <?php if (empty($halls) && empty($courses)): ?>
      You need at least one hall and one course before you can create a schedule.
      Go to <a href="halls.php">Manage Halls</a> and <a href="courses.php">Manage Courses</a> to add them.
    <?php elseif (empty($halls)): ?>
      You need at least one hall before you can create a schedule. Go to <a href="halls.php">Manage Halls</a> to add one.
    <?php else: ?>
      You need at least one course before you can create a schedule. Go to <a href="courses.php">Manage Courses</a> to add one.
    <?php endif; ?>
  </div>
<?php endif; ?>

<div class="card">
  <div class="card-head"><h2><?= $editRow ? 'Edit Schedule' : 'Create Schedule' ?></h2></div>
  <form method="post" action="schedules.php">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="<?= $editRow ? 'update' : 'add' ?>">
    <?php if ($editRow): ?><input type="hidden" name="id" value="<?= (int)$editRow['id'] ?>"><?php endif; ?>
    <div class="form-grid cols-2">
      <div class="field">
        <label>Hall</label>
        <select name="hall_id" required>
          <option value="">— Select Hall —</option>
          <?php foreach ($halls as $h): ?>
            <option value="<?= (int)$h['id'] ?>" <?= (($editRow['hall_id'] ?? null) == $h['id']) ? 'selected' : '' ?>><?= e($h['hall_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Level</label>
        <select name="level" required>
          <option value="">— Select Level —</option>
          <?php foreach (scheduleLevels() as $lv): ?>
            <option value="<?= e($lv) ?>" <?= (($editRow['level'] ?? '') === $lv) ? 'selected' : '' ?>><?= e($lv) ?> Level</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Course</label>
        <select name="course_id" required>
          <option value="">— Select Course —</option>
          <?php foreach ($courses as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= (($editRow['course_id'] ?? null) == $c['id']) ? 'selected' : '' ?>><?= e($c['course_code'] . ' — ' . $c['course_title']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Lecturer</label>
        <select name="lecturer_id">
          <option value="">— Unassigned —</option>
          <?php foreach ($lecturers as $l): ?>
            <option value="<?= (int)$l['id'] ?>" <?= (($editRow['lecturer_id'] ?? null) == $l['id']) ? 'selected' : '' ?>><?= e($l['first_name'] . ' ' . $l['last_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Date</label>
        <input type="date" name="schedule_date" required min="<?= date('Y-m-d') ?>" value="<?= e($editRow['schedule_date'] ?? '') ?>">
        <p class="hall-image-hint">Today or a future date only. The class happens on this date and does not repeat.</p>
      </div>
      <div class="field">
        <label>Start Time</label>
        <input type="time" name="start_time" required value="<?= e($editRow['start_time'] ?? '') ?>">
      </div>
      <div class="field">
        <label>End Time</label>
        <input type="time" name="end_time" required value="<?= e($editRow['end_time'] ?? '') ?>">
      </div>
    </div>
    <?php if (!$editRow): ?>
      <div class="field" style="margin-top:14px;max-width:340px;">
        <label>Repeat weekly until <span style="font-weight:600;opacity:.7;">(optional)</span></label>
        <input type="date" name="repeat_until" min="<?= date('Y-m-d') ?>">
        <p class="hall-image-hint">Leave empty for a one-time class. Otherwise the same class is booked every week on the same day until this date (up to 26 weeks), each checked for conflicts.</p>
      </div>
    <?php endif; ?>
    <div class="field-check" style="margin-top:14px;">
      <input type="checkbox" id="check_conflicts" name="check_conflicts" checked>
      <label for="check_conflicts" style="margin:0;">Check for Conflicts</label>
    </div>
    <div style="margin-top:16px;display:flex;gap:10px;">
      <button type="submit" class="btn btn-primary"><?= $editRow ? 'Update Schedule' : 'Add Schedule' ?></button>
      <?php if ($editRow): ?><a class="btn btn-outline" href="schedules.php">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="card">
  <div class="card-head">
    <h2>All Schedules</h2>
    <form method="post" action="schedules.php" class="confirm-delete" data-confirm="Delete every class older than last week? Students cannot see these any more. This cannot be undone." style="display:inline;">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="clear_old">
      <button type="submit" class="btn btn-outline btn-sm">Delete old classes</button>
    </form>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Course</th><th>Level</th><th>Hall</th><th>Lecturer</th><th>Date</th><th>Time</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($schedule as $s): ?>
          <tr>
            <td><strong><?= e($s['course_code']) ?></strong></td>
            <td><?= e(levelLabel($s['level'] ?? '')) ?></td>
            <td><?= e($s['hall_name']) ?></td>
            <td><?= e(trim(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? ''))) ?: '—' ?></td>
            <td><?= $s['schedule_date'] ? e(date('D', strtotime($s['schedule_date'])) . ', ' . fmtDate($s['schedule_date'])) : '<span style="color:#b42318;font-weight:700;">No date &mdash; edit to set</span>' ?></td>
            <td><?= fmtTime($s['start_time']) ?>&ndash;<?= fmtTime($s['end_time']) ?></td>
            <td><?= classBadgesHtml($s) ?: '<span class="badge badge-available">Active</span>' ?></td>
            <td>
              <div class="row-actions">
                <?php if ($s['schedule_date'] && $s['schedule_date'] < date('Y-m-d')): ?>
                  <span style="font-weight:700;opacity:.7;">Past</span>
                <?php else: ?>
                  <a class="btn btn-outline btn-sm" href="schedules.php?edit=<?= (int)$s['id'] ?>">Edit</a>
                  <form method="post" action="schedules.php" style="display:inline;">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="toggle_cancel">
                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                    <button type="submit" class="btn btn-outline btn-sm"><?= $s['status'] === 'Cancelled' ? 'Restore' : 'Cancel class' ?></button>
                  </form>
                <?php endif; ?>
                <form method="post" action="schedules.php" class="confirm-delete" style="display:inline;">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
