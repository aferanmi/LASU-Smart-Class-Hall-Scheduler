<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDB();
requireAdminLogin();

$base = '../';
$is_admin = true;
$active_nav = 'lecturers';
$page_title = 'Manage Lecturers';

$error = '';
$success = '';
$editLecturer = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrfCheck();
    $pdo->prepare("DELETE FROM lecturers WHERE id = ?")->execute([(int)$_POST['id']]);
    logActivity($pdo, 'Deleted lecturer', 'Lecturer #' . (int)$_POST['id']);
    header('Location: lecturers.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['add', 'update'])) {
    csrfCheck();
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $department = trim($_POST['department'] ?? '');

    if ($first_name === '' || $last_name === '') {
        $error = 'First name and last name are required.';
    } else {
        if ($_POST['action'] === 'add') {
            $pdo->prepare("INSERT INTO lecturers (first_name, last_name, department) VALUES (?,?,?)")
                ->execute([$first_name, $last_name, $department]);
            logActivity($pdo, 'Added lecturer', $first_name . ' ' . $last_name);
            $success = 'Lecturer added successfully.';
        } else {
            $id = (int)$_POST['id'];
            $pdo->prepare("UPDATE lecturers SET first_name=?, last_name=?, department=? WHERE id=?")
                ->execute([$first_name, $last_name, $department, $id]);
            logActivity($pdo, 'Updated lecturer', $first_name . ' ' . $last_name);
            $success = 'Lecturer updated successfully.';
        }
    }
}

if (isset($_GET['edit'])) {
    $editLecturer = getLecturer($pdo, (int)$_GET['edit']);
}

$lecturers = getAllLecturers($pdo);

require __DIR__ . '/../includes/header.php';
?>

<h1 class="page-title">Manage Lecturers</h1>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<div class="card">
  <div class="card-head"><h2><?= $editLecturer ? 'Edit Lecturer' : 'Add Lecturer' ?></h2></div>
  <form method="post" action="lecturers.php">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="<?= $editLecturer ? 'update' : 'add' ?>">
    <?php if ($editLecturer): ?><input type="hidden" name="id" value="<?= (int)$editLecturer['id'] ?>"><?php endif; ?>
    <div class="form-grid cols-2">
      <div class="field">
        <label>First Name</label>
        <input type="text" name="first_name" required value="<?= e($editLecturer['first_name'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Last Name</label>
        <input type="text" name="last_name" required value="<?= e($editLecturer['last_name'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Department</label>
        <input type="text" name="department" value="<?= e($editLecturer['department'] ?? '') ?>">
      </div>
    </div>
    <div style="margin-top:16px;display:flex;gap:10px;">
      <button type="submit" class="btn btn-primary"><?= $editLecturer ? 'Update Lecturer' : 'Add Lecturer' ?></button>
      <?php if ($editLecturer): ?><a class="btn btn-outline" href="lecturers.php">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="card">
  <div class="card-head"><h2>All Lecturers</h2></div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Name</th><th>Department</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($lecturers as $l): ?>
          <tr>
            <td><?= e($l['first_name'] . ' ' . $l['last_name']) ?></td>
            <td><?= e($l['department']) ?></td>
            <td>
              <div class="row-actions">
                <a class="btn btn-outline btn-sm" href="lecturers.php?edit=<?= (int)$l['id'] ?>">Edit</a>
                <form method="post" action="lecturers.php" class="confirm-delete" style="display:inline;">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
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
