    </main>
  </div>

  <?php if (!$is_admin): ?>
  <nav class="bottom-nav">
    <a href="<?= $base ?>index.php" class="bottom-link <?= $active_nav === 'dashboard' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" width="22" height="22"><path d="M4 11l8-7 8 7v9a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1z" /></svg>
      <span>Dashboard</span>
    </a>
    <a href="<?= $base ?>schedule.php" class="bottom-link <?= $active_nav === 'schedule' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" width="22" height="22"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>
      <span>Schedule</span>
    </a>
    <a href="<?= $base ?>halls.php" class="bottom-link <?= $active_nav === 'halls' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" width="22" height="22"><path d="M4 21V9l8-6 8 6v12M9 21v-6h6v6"/></svg>
      <span>Halls</span>
    </a>
    <button type="button" class="bottom-link more-btn" id="moreBtn">
      <svg viewBox="0 0 24 24" width="22" height="22"><circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/></svg>
      <span>More</span>
    </button>
  </nav>

  <div class="more-sheet" id="moreSheet">
    <a href="<?= $base ?>availability.php">Hall Availability</a>
    <a href="<?= $base ?>free-halls.php">Find a Free Hall</a>
    <a href="<?= $base ?>search.php">Search</a>
    <a href="<?= $base ?>announcements.php">Announcements</a>
  </div>
  <?php endif; ?>

</div>

<script src="<?= $base ?>assets/js/script.js"></script>
</body>
</html>
