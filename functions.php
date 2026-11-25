<?php
/**
 * Shared helper functions used by both the student and admin pages.
 */

function fmtTime($time) {
    if (!$time) return '';
    return date('h:i A', strtotime($time));
}

function fmtDate($date) {
    if (!$date) return '';
    return date('M j, Y', strtotime($date));
}

/**
 * Rolling window: classes older than the Monday of LAST week are no longer
 * shown anywhere. Returns 'Y-m-d'.
 */
function scheduleLevels() {
    return ['100', '200', '300', '400'];
}

/** "100 Level" for a stored level, or a dash for older classes without one. */
function levelLabel($level) {
    return in_array($level, scheduleLevels(), true) ? $level . ' Level' : '—';
}

function scheduleWindowStart() {
    return (new DateTime('monday this week'))->modify('-7 day')->format('Y-m-d');
}

/**
 * SQL fragment + params that keep only classes that have NOT finished yet:
 * a later date, or today with an end time still in the future (Lagos time).
 * Use as: $sql .= " AND " . activeClassSql('hs')[0];  params = [1]
 */
function activeClassSql($alias = 'hs', $excludeCancelled = false) {
    $a = $alias ? $alias . '.' : '';
    $frag = "({$a}schedule_date > ? OR ({$a}schedule_date = ? AND {$a}end_time > ?))";
    if ($excludeCancelled) {
        $frag .= " AND {$a}status <> 'Cancelled'";
    }
    return [$frag, [date('Y-m-d'), date('Y-m-d'), date('H:i:s')]];
}

function statusBadgeClass($status) {
    $status = strtolower(trim($status ?? ''));
    if ($status === 'available') return 'badge badge-available';
    if ($status === 'in use') return 'badge badge-inuse';
    if ($status === 'maintenance') return 'badge badge-maintenance';
    return 'badge';
}

/**
 * Renders a hall's status as a badge, or a plain "Not set" marker when the
 * admin has not assigned a status yet. Never shows a hardcoded/demo value.
 */
function statusBadgeHtml($status) {
    $status = trim($status ?? '');
    if ($status === '') {
        return '<span class="meta">Not set</span>';
    }
    return '<span class="' . statusBadgeClass($status) . '">' . e($status) . '</span>';
}

/* ---------- CSRF protection ---------- */

function csrfToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

/**
 * Call at the top of every POST handler. Stops the request with a 403 if
 * the submitted token is missing or does not match the session's token.
 */
function csrfCheck() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $submitted = $_POST['csrf_token'] ?? '';
    if ($submitted === '' || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $submitted)) {
        http_response_code(403);
        die('Your form session expired or could not be verified. Please go back, refresh the page, and try again.');
    }
}

function priorityBadgeClass($priority) {
    $priority = strtolower(trim($priority));
    if ($priority === 'high') return 'badge badge-high';
    if ($priority === 'medium') return 'badge badge-medium';
    if ($priority === 'low') return 'badge badge-low';
    return 'badge badge-info';
}

function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/* ---------- Hall image upload ---------- */

define('HALL_IMAGE_MAX_BYTES', 2 * 1024 * 1024); // 2MB
define('HALL_IMAGE_DIR_FS', __DIR__ . '/../uploads/halls/');
define('HALL_IMAGE_DIR_REL', 'uploads/halls/');

/**
 * Validates an uploaded hall image (real image content inspected with
 * getimagesize — never just the filename/extension — plus an allowed
 * type and a 2MB size cap) and, if valid, moves it into uploads/halls/
 * under a new random server-generated filename. Returns
 * ['path' => 'uploads/halls/xxxx.jpg', 'error' => null] on success,
 * ['path' => null, 'error' => null] when nothing was uploaded, or
 * ['path' => null, 'error' => 'message'] on failure.
 */
function processHallImageUpload(array $file) {
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['path' => null, 'error' => null];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['path' => null, 'error' => 'The image upload failed. Please try again.'];
    }
    if ($file['size'] > HALL_IMAGE_MAX_BYTES) {
        return ['path' => null, 'error' => 'That image is larger than 2 MB. Please choose a smaller file.'];
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        return ['path' => null, 'error' => 'The image upload could not be verified. Please try again.'];
    }

    // Inspect the actual file content — never trust the filename/extension.
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return ['path' => null, 'error' => 'That file is not a valid image.'];
    }

    $allowed = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_WEBP => 'webp',
    ];
    $type = $imageInfo[2];
    if (!isset($allowed[$type])) {
        return ['path' => null, 'error' => 'Only JPG, PNG or WebP images are allowed.'];
    }
    $ext = $allowed[$type];

    if (!is_dir(HALL_IMAGE_DIR_FS)) {
        @mkdir(HALL_IMAGE_DIR_FS, 0755, true);
    }

    $filename = 'hall_' . bin2hex(random_bytes(12)) . '.' . $ext;
    $destination = HALL_IMAGE_DIR_FS . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['path' => null, 'error' => 'The image could not be saved on the server. Please try again.'];
    }
    @chmod($destination, 0644);

    return ['path' => HALL_IMAGE_DIR_REL . $filename, 'error' => null];
}

/**
 * Deletes a previously stored hall image file from disk, if present.
 * Only ever touches a plain filename inside the hall uploads folder,
 * so a tampered/unexpected stored value can't delete anything else.
 */
function deleteHallImageFile($relativePath) {
    if (!$relativePath) return;
    $filename = basename($relativePath);
    $fullPath = HALL_IMAGE_DIR_FS . $filename;
    if (is_file($fullPath)) {
        @unlink($fullPath);
    }
}

/**
 * Public URL for a hall's image (already HTML-escaped, ready to echo
 * straight into an src attribute), or null if the hall has none.
 */
function hallImageUrl($base, $imagePath) {
    if (!$imagePath) return null;
    return $base . e($imagePath);
}

/**
 * True only if the hall actually has an image file sitting on disk right
 * now. The database can hold an image_path pointing at a file that was
 * never fully uploaded or was removed outside the app — checking this
 * before rendering an <img> tag means a hall with no real file on disk
 * always shows the clean placeholder instead of a broken image icon.
 */
function hallImageExists($imagePath) {
    if (!$imagePath) return false;
    return is_file(HALL_IMAGE_DIR_FS . basename($imagePath));
}

/**
 * Picks a reasonable icon (as inline SVG) for a free-text resource item
 * based on simple keyword matching — e.g. "Projector x2" gets a projector
 * icon, "Whiteboard" gets a board icon. Falls back to a generic equipment
 * icon when nothing matches. Purely cosmetic; never affects the text shown.
 */
function resourceIconSvg($itemText) {
    $t = strtolower($itemText);
    if (strpos($t, 'projector') !== false || strpos($t, 'screen') !== false) {
        return '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="2" y="7" width="20" height="10" rx="2"/><circle cx="17" cy="12" r="2"/><path d="M6 17v2M10 17v2"/></svg>';
    }
    if (strpos($t, 'speaker') !== false || strpos($t, 'audio') !== false || strpos($t, 'address system') !== false || strpos($t, 'pa system') !== false || strpos($t, 'microphone') !== false || strpos($t, 'mic') !== false) {
        return '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M11 5 6 9H2v6h4l5 4V5z"/><path d="M19 8a6 6 0 010 8"/></svg>';
    }
    if (strpos($t, 'whiteboard') !== false || strpos($t, 'board') !== false) {
        return '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="4" width="18" height="12" rx="1"/><path d="M8 20h8M12 16v4"/></svg>';
    }
    if (strpos($t, 'air condition') !== false || strpos($t, 'a/c') !== false || preg_match('/\bac\b/', $t) || strpos($t, 'fan') !== false) {
        return '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 2v20M2 12h20M5 5l14 14M19 5L5 19"/></svg>';
    }
    if (strpos($t, 'computer') !== false || strpos($t, 'desktop') !== false || strpos($t, 'pc') !== false || strpos($t, 'laptop') !== false) {
        return '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="4" width="18" height="12" rx="1"/><path d="M8 20h8M12 16v4"/><path d="M7 8h2M7 11h2"/></svg>';
    }
    if (strpos($t, 'wifi') !== false || strpos($t, 'internet') !== false) {
        return '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M5 13a10 10 0 0114 0M8.5 16.5a5 5 0 017 0"/><circle cx="12" cy="20" r="1" fill="currentColor" stroke="none"/></svg>';
    }
    // Generic fallback: a simple equipment/box icon.
    return '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>';
}

/**
 * Total number of individual resource items across all halls, counted
 * from each hall's own comma-separated Resources text.
 */
function countResourceItems(PDO $pdo) {
    $total = 0;
    $stmt = $pdo->query("SELECT facilities FROM halls");
    foreach ($stmt->fetchAll() as $row) {
        $total += count(parseHallResourceItems($row['facilities'] ?? ''));
    }
    return $total;
}

/**
 * Splits a hall's comma-separated Resources text into a clean list of
 * individual items, e.g. "Projector x2, Whiteboard, AC x3" becomes
 * ['Projector x2', 'Whiteboard', 'AC x3']. Empty entries are dropped.
 */
function parseHallResourceItems($text) {
    if (!$text) return [];
    $items = array_map('trim', explode(',', $text));
    return array_values(array_filter($items, function ($i) { return $i !== ''; }));
}

/* ---------- Data getters (used across student + admin pages) ---------- */

/**
 * Hall status is automatic: "In Use" while a class is running right now,
 * otherwise "Available" — unless the admin put the hall under Maintenance.
 * The stored value is kept in $hall['status_raw'] (used by the admin form).
 */
function hallIdsInUseNow(PDO $pdo) {
    $stmt = $pdo->prepare("SELECT DISTINCT hall_id FROM hall_schedule
        WHERE schedule_date = ? AND start_time <= ? AND end_time > ? AND status <> 'Cancelled'");
    $stmt->execute([date('Y-m-d'), date('H:i:s'), date('H:i:s')]);
    return array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));
}

function applyAutoStatus(array $hall, array $inUse) {
    $hall['status_raw'] = $hall['status'];
    if ($hall['status'] === 'Maintenance') {
        return $hall;
    }
    $hall['status'] = isset($inUse[$hall['id']]) ? 'In Use' : 'Available';
    return $hall;
}

function getAllHalls(PDO $pdo, $raw = false) {
    $halls = $pdo->query("SELECT * FROM halls ORDER BY hall_name")->fetchAll();
    if ($raw) return $halls;
    $inUse = hallIdsInUseNow($pdo);
    return array_map(function ($h) use ($inUse) { return applyAutoStatus($h, $inUse); }, $halls);
}

function getHall(PDO $pdo, $id, $raw = false) {
    $stmt = $pdo->prepare("SELECT * FROM halls WHERE id = ?");
    $stmt->execute([$id]);
    $hall = $stmt->fetch();
    if (!$hall || $raw) return $hall;
    return applyAutoStatus($hall, hallIdsInUseNow($pdo));
}

function getAllLecturers(PDO $pdo) {
    return $pdo->query("SELECT * FROM lecturers ORDER BY first_name")->fetchAll();
}

function getLecturer(PDO $pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM lecturers WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getAllCourses(PDO $pdo) {
    return $pdo->query("
        SELECT c.*, l.first_name, l.last_name
        FROM courses c
        LEFT JOIN lecturers l ON c.lecturer_id = l.id
        ORDER BY c.course_code
    ")->fetchAll();
}

function getCourse(PDO $pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Schedule rows. Classes belong to a specific calendar date (schedule_date),
 * so they do NOT repeat every week.
 *   $date     = 'YYYY-MM-DD' -> only classes on that exact date
 *   $fromDate = 'YYYY-MM-DD' -> only classes on/after that date
 *   $hideEnded = true -> also drop classes that have already finished
 * With neither, every dated class is returned (admin view).
 */
function getFullSchedule(PDO $pdo, $date = null, $fromDate = null, $hideEnded = false) {
    $sql = "
        SELECT hs.*, h.hall_name, h.building, c.course_code, c.course_title,
               l.first_name, l.last_name
        FROM hall_schedule hs
        JOIN halls h ON hs.hall_id = h.id
        JOIN courses c ON hs.course_id = c.id
        LEFT JOIN lecturers l ON hs.lecturer_id = l.id
        WHERE 1=1
    ";
    $params = [];
    if ($date) {
        $sql .= " AND hs.schedule_date = ?";
        $params[] = $date;
    }
    if ($fromDate) {
        $sql .= " AND hs.schedule_date >= ?";
        $params[] = $fromDate;
    }
    if ($hideEnded) {
        [$frag, $p] = activeClassSql('hs');
        $sql .= " AND " . $frag;
        $params = array_merge($params, $p);
    }
    $sql .= " ORDER BY hs.schedule_date IS NULL, hs.schedule_date, hs.start_time";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** Upcoming (today or later) classes for one hall, soonest first. */
function getScheduleForHall(PDO $pdo, $hallId) {
    $stmt = $pdo->prepare("
        SELECT hs.*, c.course_code, c.course_title, l.first_name, l.last_name
        FROM hall_schedule hs
        JOIN courses c ON hs.course_id = c.id
        LEFT JOIN lecturers l ON hs.lecturer_id = l.id
        WHERE hs.hall_id = ? AND " . activeClassSql('hs')[0] . "
        ORDER BY hs.schedule_date, hs.start_time
    ");
    $stmt->execute(array_merge([$hallId], activeClassSql('hs')[1]));
    return $stmt->fetchAll();
}

function getTodaysClasses(PDO $pdo) {
    return getFullSchedule($pdo, date('Y-m-d'), null, true);
}

function getResourcesForHall(PDO $pdo, $hallId) {
    $stmt = $pdo->prepare("SELECT * FROM resources WHERE hall_id = ?");
    $stmt->execute([$hallId]);
    return $stmt->fetchAll();
}

function getAllResources(PDO $pdo) {
    return $pdo->query("
        SELECT r.*, h.hall_name
        FROM resources r
        LEFT JOIN halls h ON r.hall_id = h.id
        ORDER BY r.resource_name
    ")->fetchAll();
}

/** Time frames an admin can choose for an announcement. */
function announcementDurations() {
    return [
        '1d'  => ['1 day',    '+1 day'],
        '1w'  => ['1 week',   '+1 week'],
        '2w'  => ['2 weeks',  '+2 weeks'],
        '3w'  => ['3 weeks',  '+3 weeks'],
        '1m'  => ['1 month',  '+1 month'],
    ];
}

/** Expiry timestamp ('Y-m-d H:i:s', Lagos time) for a duration key, or null if invalid. */
function announcementExpiry($key) {
    $d = announcementDurations();
    if (!isset($d[$key])) return null;
    return (new DateTime('now'))->modify($d[$key][1])->format('Y-m-d H:i:s');
}

/** Published announcements that have not expired yet (old ones with no expiry still show). */
function getPublishedAnnouncements(PDO $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM announcements
        WHERE is_published = 1 AND (expires_at IS NULL OR expires_at > ?)
        ORDER BY created_at DESC");
    $stmt->execute([date('Y-m-d H:i:s')]);
    return $stmt->fetchAll();
}

function getAllAnnouncements(PDO $pdo) {
    return $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC")->fetchAll();
}

/* ---------- Dashboard stat counters ---------- */

function countHalls(PDO $pdo, $status = null) {
    if ($status) {
        $n = 0;
        foreach (getAllHalls($pdo) as $h) {
            if ($h['status'] === $status) $n++;
        }
        return $n;
    }
    return (int)$pdo->query("SELECT COUNT(*) AS c FROM halls")->fetch()['c'];
}

function countTodaysClasses(PDO $pdo) {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM hall_schedule WHERE schedule_date = ? AND end_time > ? AND status <> 'Cancelled'");
    $stmt->execute([date('Y-m-d'), date('H:i:s')]);
    return (int)$stmt->fetch()['c'];
}

function countTotalClasses(PDO $pdo) {
    // Upcoming classes only (today or later) — past dates are not "total" any more.
    [$frag, $p] = activeClassSql('', true);
    $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM hall_schedule WHERE " . $frag);
    $stmt->execute($p);
    return (int)$stmt->fetch()['c'];
}

function countResources(PDO $pdo) {
    return (int)$pdo->query("SELECT COUNT(*) AS c FROM resources")->fetch()['c'];
}

function countBookings(PDO $pdo) {
    // Active bookings only: classes still to happen (or in progress today).
    [$frag, $p] = activeClassSql('', true);
    $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM hall_schedule WHERE " . $frag);
    $stmt->execute($p);
    return (int)$stmt->fetch()['c'];
}

/**
 * The most recently created schedule entries (true "recent bookings" —
 * ordered by when the admin created them, not by time of day).
 */
function getRecentBookings(PDO $pdo, $limit = 5) {
    $limit = (int)$limit;
    [$frag, $p] = activeClassSql('hs', true);
    $stmt = $pdo->prepare("
        SELECT hs.*, h.hall_name, h.building, c.course_code, c.course_title,
               l.first_name, l.last_name
        FROM hall_schedule hs
        JOIN halls h ON hs.hall_id = h.id
        JOIN courses c ON hs.course_id = c.id
        LEFT JOIN lecturers l ON hs.lecturer_id = l.id
        WHERE $frag
        ORDER BY hs.created_at DESC, hs.id DESC
        LIMIT $limit
    ");
    $stmt->execute($p);
    return $stmt->fetchAll();
}

/* ---------- Conflict detection ---------- */

/**
 * Returns a conflict message string if the hall or lecturer is already
 * booked on the given date/time window, or null if there is no conflict.
 * $excludeId lets an edit-in-progress ignore its own existing row.
 */
function findScheduleConflict(PDO $pdo, $hallId, $lecturerId, $date, $start, $end, $excludeId = null) {
    $day = fmtDate($date);
    // Hall conflict: same hall, same day, overlapping time range.
    $sql = "SELECT hs.*, c.course_code FROM hall_schedule hs
            JOIN courses c ON hs.course_id = c.id
            WHERE hs.hall_id = ? AND hs.schedule_date = ? AND hs.status <> 'Cancelled'
            AND hs.start_time < ? AND hs.end_time > ?";
    $params = [$hallId, $date, $end, $start];
    if ($excludeId) {
        $sql .= " AND hs.id != ?";
        $params[] = $excludeId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    if ($row) {
        return "This hall is already booked for {$row['course_code']} on {$day} from " . fmtTime($row['start_time']) . " to " . fmtTime($row['end_time']) . ".";
    }

    // Lecturer conflict: same lecturer, same day, overlapping time range, different hall.
    if ($lecturerId) {
        $sql = "SELECT hs.*, c.course_code, h.hall_name FROM hall_schedule hs
                JOIN courses c ON hs.course_id = c.id
                JOIN halls h ON hs.hall_id = h.id
                WHERE hs.lecturer_id = ? AND hs.schedule_date = ? AND hs.status <> 'Cancelled'
                AND hs.start_time < ? AND hs.end_time > ?";
        $params = [$lecturerId, $date, $end, $start];
        if ($excludeId) {
            $sql .= " AND hs.id != ?";
            $params[] = $excludeId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        if ($row) {
            return "This lecturer is already teaching {$row['course_code']} in {$row['hall_name']} on {$day} from " . fmtTime($row['start_time']) . " to " . fmtTime($row['end_time']) . ".";
        }
    }

    return null;
}


/* ---------- Class badges (Now / Starts soon / Cancelled / Moved) ---------- */

/** Extra badge HTML for a schedule row: Cancelled, Moved from X, Now, Starts in N min. */
function classBadgesHtml(array $row) {
    $out = '';
    if (($row['status'] ?? '') === 'Cancelled') {
        return '<span class="badge badge-maintenance">Cancelled</span>';
    }
    if (!empty($row['moved_from'])) {
        $out .= '<span class="badge badge-inuse">Moved from ' . e($row['moved_from']) . '</span> ';
    }
    if (($row['schedule_date'] ?? '') === date('Y-m-d')) {
        $now = date('H:i:s');
        if ($row['start_time'] <= $now && $row['end_time'] > $now) {
            $out .= '<span class="badge badge-now">Now</span>';
        } elseif ($row['start_time'] > $now) {
            $mins = (int)ceil((strtotime($row['start_time']) - strtotime($now)) / 60);
            if ($mins <= 60) {
                $out .= '<span class="badge badge-soon">Starts in ' . $mins . ' min</span>';
            }
        }
    }
    return trim($out);
}

/** Add a CSS class to a row's text when the class is cancelled. */
function classRowCancelledClass(array $row) {
    return (($row['status'] ?? '') === 'Cancelled') ? ' class-cancelled' : '';
}


/* ---------- Admin activity log ---------- */

function logActivity(PDO $pdo, $action, $details = '') {
    try {
        $user = $_SESSION['admin_username'] ?? 'admin';
        $pdo->prepare("INSERT INTO admin_log (admin_username, action, details, created_at) VALUES (?,?,?,?)")
            ->execute([$user, mb_substr($action, 0, 60), mb_substr($details, 0, 255), date('Y-m-d H:i:s')]);
    } catch (Throwable $e) {
        // Logging must never break the admin action itself.
    }
}


/* ---------- Login lockout (5 wrong tries -> 15 minute wait) ---------- */

define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCK_MINUTES', 15);

function loginClientIp() {
    return substr($_SERVER['REMOTE_ADDR'] ?? 'unknown', 0, 64);
}

function loginRecentFailures(PDO $pdo) {
    $since = (new DateTime('now'))->modify('-' . LOGIN_LOCK_MINUTES . ' minutes')->format('Y-m-d H:i:s');
    $stmt = $pdo->prepare("SELECT COUNT(*) AS c, MIN(attempted_at) AS first FROM login_attempts WHERE ip_address = ? AND attempted_at > ?");
    $stmt->execute([loginClientIp(), $since]);
    return $stmt->fetch();
}

/** Minutes left on a lockout, or 0 if the visitor may try. */
function loginLockMinutesLeft(PDO $pdo) {
    $r = loginRecentFailures($pdo);
    if ((int)$r['c'] < LOGIN_MAX_ATTEMPTS) return 0;
    $unlock = strtotime($r['first']) + LOGIN_LOCK_MINUTES * 60;
    return max(1, (int)ceil(($unlock - time()) / 60));
}

function recordLoginFailure(PDO $pdo) {
    $pdo->prepare("INSERT INTO login_attempts (ip_address, attempted_at) VALUES (?,?)")
        ->execute([loginClientIp(), date('Y-m-d H:i:s')]);
    // Housekeeping: forget attempts older than a day.
    $pdo->prepare("DELETE FROM login_attempts WHERE attempted_at < ?")
        ->execute([(new DateTime('now'))->modify('-1 day')->format('Y-m-d H:i:s')]);
}

function clearLoginFailures(PDO $pdo) {
    $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?")->execute([loginClientIp()]);
}
