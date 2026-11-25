<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

getDB();
requireAdminLogin();

// Resources are now managed directly inside Manage Halls (as a field on
// each hall), not as a separate page — send anyone who still has this
// page bookmarked or linked to the right place.
header('Location: halls.php');
exit;
