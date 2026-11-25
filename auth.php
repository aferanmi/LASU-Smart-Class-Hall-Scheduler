<?php
/**
 * Include this at the top of every admin page (after database.php).
 * Redirects to the login page if there is no valid admin session.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireAdminLogin() {
    if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: login.php');
        exit;
    }
    // First login with the starting password: the admin must choose a new one
    // before using anything else.
    if (!empty($_SESSION['must_change_password'])
        && !in_array(basename($_SERVER['SCRIPT_NAME'] ?? ''), ['change-password.php', 'logout.php'], true)) {
        header('Location: change-password.php');
        exit;
    }
}

function isAdminLoggedIn() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return !empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}
