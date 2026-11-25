<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDB();

$base = '';
$is_admin = false;
$active_nav = 'announcements';
$page_title = 'Announcements';

$announcements = getPublishedAnnouncements($pdo);

require __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Announcements</h1>

<?php if (empty($announcements)): ?>
  <div class="card"><div class="empty-state">No announcements right now.</div></div>
<?php else: foreach ($announcements as $a): ?>
  <div class="card">
    <div class="card-head">
      <h2><?= e($a['title']) ?></h2>
      <span class="<?= priorityBadgeClass($a['priority']) ?>"><?= e($a['priority']) ?></span>
    </div>
    <p style="font-size:14px;color:var(--text-muted);margin:0 0 8px;"><?= nl2br(e($a['content'])) ?></p>
    <div class="meta" style="font-size:12px;color:var(--text-muted);"><?= fmtDate($a['created_at']) ?></div>
  </div>
<?php endforeach; endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
