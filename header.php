<?php
/**
 * Shared header include.
 * Expects the including page to set, BEFORE including this file:
 *   $base        - relative path prefix to the site root ('' for root pages, '../' for /admin pages)
 *   $page_title  - <title> text
 *   $active_nav  - key of the current nav item, used to mark it active
 *   $is_admin    - true on admin/* pages, false on student pages
 * Optional:
 *   $admin_name  - display name when $is_admin is true and logged in
 */
// Security headers — sent before any HTML output on every page.
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self'; img-src 'self' data:; base-uri 'self'; form-action 'self'");

$base = $base ?? '';
$is_admin = $is_admin ?? false;
$active_nav = $active_nav ?? '';
$page_title = $page_title ?? 'LASU Smart Class Hall Scheduler';

$studentNav = [
    'dashboard'     => ['index.php', 'Dashboard'],
    'schedule'      => ['schedule.php', 'Schedule'],
    'halls'         => ['halls.php', 'Class Halls'],
    'availability'  => ['availability.php', 'Hall Availability'],
    'freehalls'     => ['free-halls.php', 'Find a Free Hall'],
    'search'        => ['search.php', 'Search'],
    'announcements' => ['announcements.php', 'Announcements'],
];

$adminNav = [
    'dashboard'     => ['dashboard.php', 'Dashboard'],
    'schedules'     => ['schedules.php', 'Class Schedule'],
    'halls'         => ['halls.php', 'Halls'],
    'courses'       => ['courses.php', 'Courses'],
    'lecturers'     => ['lecturers.php', 'Lecturers'],
    'announcements' => ['announcements.php', 'Announcements'],
    'activity'      => ['activity-log.php', 'Activity Log'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($page_title) ?> · LASU Smart Class Hall Scheduler</title>
<link rel="stylesheet" href="<?= $base ?>assets/css/style.css">
</head>
<body class="<?= $is_admin ? 'admin-body' : 'student-body' ?>">

<div class="app-shell">

  <header class="topbar">
    <div class="topbar-left">
      <div class="logo-badge" aria-hidden="true">
        <img src="<?= $base ?>assets/img/lasu-logo.png" alt="LASU logo" width="34" height="35">
      </div>
      <div class="topbar-titles">
        <span class="topbar-title">LASU Smart Class Hall Scheduler</span>
        <span class="topbar-subtitle">Smart Class Hall Scheduling &amp; Resource Management System</span>
      </div>
    </div>
    <div class="topbar-right">
      <button class="icon-btn hamburger-btn" id="hamburgerBtn" aria-label="Open menu">
        <svg viewBox="0 0 24 24" width="24" height="24"><path d="M3 6h18M3 12h18M3 18h18" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg>
      </button>
      <div class="profile-wrap">
        <button class="icon-btn profile-btn" id="profileBtn" aria-label="Profile menu">
          <svg viewBox="0 0 24 24" width="22" height="22"><circle cx="12" cy="8" r="4" fill="#fff"/><path d="M4 20c0-4.4 3.6-7 8-7s8 2.6 8 7" fill="#fff"/></svg>
          <span class="profile-label"><?= $is_admin && isAdminLoggedIn() ? 'Admin' : 'Student' ?></span>
        </button>
        <div class="profile-dropdown" id="profileDropdown">
          <?php if ($is_admin && isAdminLoggedIn()): ?>
            <a href="<?= $base ?>admin/dashboard.php">Admin Dashboard</a>
            <a href="<?= $base ?>admin/change-password.php">Change Password</a>
            <a href="<?= $base ?>index.php">Student View</a>
            <a href="<?= $base ?>admin/logout.php">Logout</a>
          <?php else: ?>
            <a href="<?= $base ?>index.php">Student View</a>
            <a href="<?= $base ?>admin/login.php">Admin Login</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </header>

  <div class="app-body">
    <nav class="sidebar" id="sidebar">
      <div class="sidebar-links">
        <?php
        $nav = $is_admin ? $adminNav : $studentNav;
        foreach ($nav as $key => $item):
            $href = $is_admin ? $item[0] : $base . $item[0];
            $activeClass = ($active_nav === $key) ? 'active' : '';
        ?>
          <a class="sidebar-link <?= $activeClass ?>" href="<?= e($href) ?>"><?= e($item[1]) ?></a>
        <?php endforeach; ?>
        <?php if ($is_admin): ?>
          <a class="sidebar-link sidebar-logout" href="logout.php">Logout</a>
        <?php endif; ?>
      </div>
    </nav>

    <main class="main-content">
