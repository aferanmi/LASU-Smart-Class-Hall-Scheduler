<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDB();

$base = '';
$is_admin = false;
$active_nav = 'halls';
$page_title = 'Class Halls';

$halls = getAllHalls($pdo);

require __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Class Halls</h1>

<div class="hall-grid">
  <?php foreach ($halls as $h): ?>
    <div class="hall-card">
      <?php if (hallImageExists($h['image_path'])): ?>
        <img class="hall-card-image" src="<?= hallImageUrl($base, $h['image_path']) ?>" alt="<?= e($h['hall_name']) ?>">
      <?php endif; ?>
      <div class="hall-card-head">
        <div style="display:flex; gap:10px; align-items:flex-start;">
          <div class="hall-icon">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 21V9l8-6 8 6v12M9 21v-6h6v6"/></svg>
          </div>
          <div>
            <h3><?= e($h['hall_name']) ?></h3>
            <div class="meta"><?= e($h['building']) ?> &middot; <?= (int)$h['capacity'] ?> capacity</div>
          </div>
        </div>
        <?= statusBadgeHtml($h['status']) ?>
      </div>

      <a class="btn btn-outline btn-sm btn-block" href="hall-details.php?id=<?= (int)$h['id'] ?>">View Details</a>
    </div>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
