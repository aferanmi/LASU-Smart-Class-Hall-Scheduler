<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDB();
requireAdminLogin();

$base = '../';
$is_admin = true;
$active_nav = 'courses';
$page_title = 'Manage Courses';

$error = '';
$success = '';
$editCourse = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrfCheck();
    $pdo->prepare("DELETE FROM courses WHERE id = ?")->execute([(int)$_POST['id']]);
    logActivity($pdo, 'Deleted course', 'Course #' . (int)$_POST['id']);
    header('Location: courses.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['add', 'update'])) {
    csrfCheck();
    $course_code = trim($_POST['course_code'] ?? '');
    $course_title = trim($_POST['course_title'] ?? '');
    $level = trim($_POST['level'] ?? '');
    $lecturer_id = $_POST['lecturer_id'] !== '' ? (int)$_POST['lecturer_id'] : null;

    if ($course_code === '' || $course_title === '') {
        $error = 'Course code and title are required.';
    } else {
        if ($_POST['action'] === 'add') {
            $pdo->prepare("INSERT INTO courses (course_code, course_title, level, lecturer_id) VALUES (?,?,?,?)")
                ->execute([$course_code, $course_title, $level, $lecturer_id]);
            logActivity($pdo, 'Added course', $course_code);
            $success = 'Course added successfully.';
        } else {
            $id = (int)$_POST['id'];
            $pdo->prepare("UPDATE courses SET course_code=?, course_title=?, level=?, lecturer_id=? WHERE id=?")
                ->execute([$course_code, $course_title, $level, $lecturer_id, $id]);
            logActivity($pdo, 'Updated course', $course_code);
            $success = 'Course updated successfully.';
        }
    }
}

if (isset($_GET['edit'])) {
    $editCourse = getCourse($pdo, (int)$_GET['edit']);
}

$courses = getAllCourses($pdo);
$lecturers = getAllLecturers($pdo);

require __DIR__ . '/../includes/header.php';
?>

<h1 class="page-title">Manage Courses</h1>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<div class="card">
  <div class="card-head"><h2><?= $editCourse ? 'Edit Course' : 'Add Course' ?></h2></div>
  <form method="post" action="courses.php">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="<?= $editCourse ? 'update' : 'add' ?>">
    <?php if ($editCourse): ?><input type="hidden" name="id" value="<?= (int)$editCourse['id'] ?>"><?php endif; ?>
    <div class="form-grid cols-2">
      <div class="field">
        <label>Course Code</label>
        <input type="text" name="course_code" required value="<?= e($editCourse['course_code'] ?? '') ?>" placeholder="e.g. CSC 101">
      </div>
      <div class="field">
        <label>Course Title</label>
        <input type="text" name="course_title" required value="<?= e($editCourse['course_title'] ?? '') ?>" placeholder="e.g. Introduction to Programming">
      </div>
      <div class="field">
        <label>Level</label>
        <input type="text" name="level" value="<?= e($editCourse['level'] ?? '') ?>" placeholder="e.g. 100">
      </div>
      <div class="field">
        <label>Lecturer</label>
        <select name="lecturer_id">
          <option value="">— Unassigned —</option>
          <?php foreach ($lecturers as $l): ?>
            <option value="<?= (int)$l['id'] ?>" <?= (($editCourse['lecturer_id'] ?? null) == $l['id']) ? 'selected' : '' ?>>
              <?= e($l['first_name'] . ' ' . $l['last_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div style="margin-top:16px;display:flex;gap:10px;">
      <button type="submit" class="btn btn-primary"><?= $editCourse ? 'Update Course' : 'Add Course' ?></button>
      <?php if ($editCourse): ?><a class="btn btn-outline" href="courses.php">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="card">
  <div class="card-head"><h2>All Courses</h2></div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Code</th><th>Title</th><th>Level</th><th>Lecturer</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($courses as $c): ?>
          <tr>
            <td><strong><?= e($c['course_code']) ?></strong></td>
            <td><?= e($c['course_title']) ?></td>
            <td><?= e($c['level']) ?></td>
            <td><?= e(trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''))) ?: '—' ?></td>
            <td>
              <div class="row-actions">
                <a class="btn btn-outline btn-sm" href="courses.php?edit=<?= (int)$c['id'] ?>">Edit</a>
                <form method="post" action="courses.php" class="confirm-delete" style="display:inline;">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
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
