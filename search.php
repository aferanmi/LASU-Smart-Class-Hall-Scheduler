<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDB();

$base = '';
$is_admin = false;
$active_nav = 'search';
$page_title = 'Search';

$q = trim($_GET['q'] ?? '');

$halls = [];
$courses = [];
$lecturers = [];

if ($q !== '') {
    $like = '%' . $q . '%';

    $stmt = $pdo->prepare("SELECT * FROM halls WHERE hall_name LIKE ? OR building LIKE ? OR facilities LIKE ? ORDER BY hall_name");
    $stmt->execute([$like, $like, $like]);
    $inUseNow = hallIdsInUseNow($pdo);
    $halls = array_map(function ($h) use ($inUseNow) { return applyAutoStatus($h, $inUseNow); }, $stmt->fetchAll());

    $stmt = $pdo->prepare("SELECT c.*, l.first_name, l.last_name FROM courses c LEFT JOIN lecturers l ON c.lecturer_id = l.id WHERE course_code LIKE ? OR course_title LIKE ? ORDER BY course_code");
    $stmt->execute([$like, $like]);
    $courses = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM lecturers WHERE first_name LIKE ? OR last_name LIKE ? OR department LIKE ? ORDER BY first_name");
    $stmt->execute([$like, $like, $like]);
    $lecturers = $stmt->fetchAll();
} else {
    $halls = getAllHalls($pdo);
    $courses = getAllCourses($pdo);
    $lecturers = getAllLecturers($pdo);
}

require __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Search</h1>

<form method="get" class="search-input-wrap">
  <span class="search-icon">
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
  </span>
  <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search halls, courses, lecturers…">
</form>

<div class="tabs">
  <button type="button" class="tab-btn active" data-tab="halls">Halls</button>
  <button type="button" class="tab-btn" data-tab="courses">Courses</button>
  <button type="button" class="tab-btn" data-tab="lecturers">Lecturers</button>
</div>

<div class="tab-panel card" id="tab-halls">
  <?php if (empty($halls)): ?>
    <div class="empty-state">No halls found.</div>
  <?php else: foreach ($halls as $h): ?>
    <a class="class-row" href="hall-details.php?id=<?= (int)$h['id'] ?>">
      <div class="class-info">
        <div class="code"><?= e($h['hall_name']) ?></div>
        <div class="title"><?= e($h['building']) ?> &middot; <?= (int)$h['capacity'] ?> capacity</div>
      </div>
      <?= statusBadgeHtml($h['status']) ?>
    </a>
  <?php endforeach; endif; ?>
</div>

<div class="tab-panel card" id="tab-courses" style="display:none;">
  <?php if (empty($courses)): ?>
    <div class="empty-state">No courses found.</div>
  <?php else: foreach ($courses as $c): ?>
    <div class="class-row">
      <div class="class-info">
        <div class="code"><?= e($c['course_code']) ?></div>
        <div class="title"><?= e($c['course_title']) ?></div>
        <div class="loc"><?= e(trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''))) ?: 'Unassigned' ?></div>
      </div>
    </div>
  <?php endforeach; endif; ?>
</div>

<div class="tab-panel card" id="tab-lecturers" style="display:none;">
  <?php if (empty($lecturers)): ?>
    <div class="empty-state">No lecturers found.</div>
  <?php else: foreach ($lecturers as $l): ?>
    <div class="class-row">
      <div class="class-info">
        <div class="code"><?= e($l['first_name'] . ' ' . $l['last_name']) ?></div>
        <div class="title"><?= e($l['department']) ?></div>
      </div>
    </div>
  <?php endforeach; endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
