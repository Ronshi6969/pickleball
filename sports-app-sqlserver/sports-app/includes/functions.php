<?php
// Shared helpers: database shortcuts, sessions, security, formatting, availability logic.
require_once __DIR__ . '/../config/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const SKILLS = ['beginner', 'intermediate', 'advanced'];

/* ---------- database shortcuts (all use prepared statements) ---------- */
// Each helper closes its cursor so no result set is left open (important for SQL Server transactions).
function db_all(string $sql, array $params = []): array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll();
    $st->closeCursor();
    return $rows;
}
function db_one(string $sql, array $params = []): ?array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $row = $st->fetch();
    $st->closeCursor();
    return $row ?: null;
}
function db_val(string $sql, array $params = [])
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $val = $st->fetchColumn();
    $st->closeCursor();
    return $val;
}
function db_col(string $sql, array $params = []): array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $vals = $st->fetchAll(PDO::FETCH_COLUMN);
    $st->closeCursor();
    return $vals;
}
function db_run(string $sql, array $params = []): int
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $n = $st->rowCount();
    $st->closeCursor();
    return $n;
}
// INSERT ... OUTPUT INSERTED.id VALUES (...)  -> returns the new IDENTITY value
function db_insert(string $sql, array $params = []): int
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $id = (int)$st->fetchColumn();
    $st->closeCursor();
    return $id;
}
// SQL Server error 2627 / 2601 = UNIQUE constraint violation (SQLSTATE 23000)
function is_duplicate_key(PDOException $e): bool
{
    return (string)$e->getCode() === '23000' || in_array((int)($e->errorInfo[1] ?? 0), [2601, 2627], true);
}
// Escape characters that have a special meaning inside a T-SQL LIKE pattern
function like_escape(string $s): string
{
    return str_replace(['[', '%', '_'], ['[[]', '[%]', '[_]'], $s);
}

/* ---------- output / routing ---------- */
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }   // stops XSS
function url(string $p = ''): string { return BASE_URL . '/' . ltrim($p, '/'); }
function redirect(string $p): void { header('Location: ' . url($p)); exit; }

// Controllers (PHP files in the root and /manager) call render() to show a view from /views.
// Views contain HTML only (plus echo / if / foreach).
function render(string $view, array $data = []): void
{
    $data['user']      = $data['user'] ?? current_user();
    $data['flashes']   = pull_flashes();
    $data['pageTitle'] = $data['pageTitle'] ?? 'Play Together';
    $data['navActive'] = $data['navActive'] ?? '';
    $data['today']     = date('Y-m-d');
    extract($data, EXTR_SKIP);
    require __DIR__ . '/../views/layout/header.php';
    require __DIR__ . '/../views/' . $view . '.php';
    require __DIR__ . '/../views/layout/footer.php';
}

/* ---------- flash messages ---------- */
function flash(string $type, string $msg): void { $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg]; }
function pull_flashes(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

/* ---------- CSRF protection for every POST form ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419);
        exit('Invalid form token. Go back, refresh the page and try again.');
    }
}

/* ---------- authentication + RBAC ---------- */
function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['user_id'])) {
            $user = db_one('SELECT id, name, email, role, avatar, skill_level FROM users WHERE id = ?', [$_SESSION['user_id']]);
        }
    }
    return $user;
}

// Call at the top of a protected page. $role = 'player' | 'manager' | null (any).
function require_login(?string $role = null): array
{
    $u = current_user();
    if (!$u) {
        flash('error', 'Please log in first.');
        header('Location: ' . url('login.php?next=' . urlencode($_SERVER['REQUEST_URI'])));
        exit;
    }
    if ($role !== null && $u['role'] !== $role) {
        http_response_code(403);
        exit('403 - Your account type cannot open this page.');
    }
    return $u;
}

function manager_venue(int $userId): array
{
    $v = db_one('SELECT * FROM venues WHERE manager_id = ?', [$userId]);
    if (!$v) { exit('No venue is linked to this account.'); }
    return $v;
}

/* ---------- files ---------- */
function upload_image(array $file, string $folder): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) { return null; }
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('Image upload failed. Maximum size is 2 MB.');
    }
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);   // real type, not the file name
    if (!isset($allowed[$mime])) { throw new RuntimeException('Only JPG, PNG or WEBP images are allowed.'); }
    $dir = __DIR__ . '/../uploads/' . $folder;
    if (!is_dir($dir)) { mkdir($dir, 0775, true); }
    $name = bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        throw new RuntimeException('Could not save the image.');
    }
    return 'uploads/' . $folder . '/' . $name;
}

// <img> if a photo exists, otherwise a colored letter tile
function media(?string $path, string $alt, string $class = 'thumb'): string
{
    if ($path) {
        return '<img class="' . e($class) . '" src="' . e(url($path)) . '" alt="' . e($alt) . '" loading="lazy">';
    }
    return '<div class="' . e($class) . ' ph" role="img" aria-label="' . e($alt) . '">'
         . e(mb_strtoupper(mb_substr($alt, 0, 1))) . '</div>';
}

/* ---------- formatting ---------- */
function hour_label(int $h): string { return date('g:i A', mktime($h, 0, 0, 1, 1, 2000)); }
function time_label(string $t): string { return date('g:i A', strtotime($t)); }
function date_label(string $d): string { return date('D, M j, Y', strtotime($d)); }
function money($n): string { return '₱' . number_format((float)$n, 2); }
function hms(string $t): string { return substr($t, 0, 8); }   // "07:00:00.0000000" -> "07:00:00"
function hm(string $t): string  { return substr($t, 0, 5); }   // "07:00:00" -> "07:00"
function valid_date(string $d): bool
{
    $x = DateTime::createFromFormat('Y-m-d', $d);
    return $x && $x->format('Y-m-d') === $d;
}
// "10 AM to 12 PM" style text for a block of hours (24 wraps to 12:00 AM)
function hour_range(int $start, int $end): string { return hour_label($start) . ' to ' . hour_label($end % 24); }
// Has a booking that starts on $date at $startHour already begun?
function booking_start(string $date, int $startHour): string { return $date . sprintf(' %02d:00:00', $startHour); }

/* ---------- availability: one status per hour of a court's day ---------- */
// status: open | booked | blocked | past
function court_slots(int $courtId, string $date): array
{
    $hours = db_one('SELECT v.opening_hour, v.closing_hour FROM courts c
                     JOIN venues v ON v.id = c.venue_id WHERE c.id = ? AND c.is_active = 1', [$courtId]);
    if (!$hours) { return []; }

    $booked = array_map('intval', db_col('SELECT slot_hour FROM booking_slots WHERE court_id = ? AND slot_date = ?', [$courtId, $date]));
    $blocks = db_all('SELECT start_hour, end_hour FROM court_blocks WHERE court_id = ? AND block_date = ?', [$courtId, $date]);

    $today = date('Y-m-d');
    $nowHour = (int)date('G');
    $slots = [];
    for ($h = (int)$hours['opening_hour']; $h < (int)$hours['closing_hour']; $h++) {
        $status = 'open';
        if ($date < $today || ($date === $today && $h <= $nowHour)) {
            $status = 'past';
        } elseif (in_array($h, $booked, true)) {
            $status = 'booked';
        } else {
            foreach ($blocks as $b) {
                if ($h >= (int)$b['start_hour'] && $h < (int)$b['end_hour']) { $status = 'blocked'; break; }
            }
        }
        $slots[] = ['hour' => $h, 'label' => hour_label($h), 'status' => $status];
    }
    return $slots;
}

/* ---------- open play queries ---------- */
// Upcoming open plays. slots_left is computed, never stored.
// "Now" comes from PHP (Asia/Manila) so it does not depend on the SQL Server clock/time zone.
function fetch_open_plays(array $f = [], ?int $limit = null): array
{
    $top = $limit ? 'TOP ' . (int)$limit . ' ' : '';     // T-SQL has TOP instead of LIMIT
    $sql = "SELECT {$top}o.*, v.name AS venue_name, v.city, s.name AS sport,
                   o.max_slots - COALESCE((SELECT SUM(r.slots) FROM open_play_registrations r
                                           WHERE r.open_play_id = o.id AND r.status = 'joined'), 0) AS slots_left
            FROM open_plays o
            JOIN venues v ON v.id = o.venue_id
            JOIN sports s ON s.id = o.sport_id
            WHERE (o.play_date > ? OR (o.play_date = ? AND o.end_time > ?))";
    $params = [date('Y-m-d'), date('Y-m-d'), date('H:i:s')];
    if (!empty($f['date']))     { $sql .= ' AND o.play_date = ?'; $params[] = $f['date']; }
    if (!empty($f['venue_id'])) { $sql .= ' AND o.venue_id = ?';  $params[] = (int)$f['venue_id']; }
    if (!empty($f['sport_id'])) { $sql .= ' AND o.sport_id = ?';  $params[] = (int)$f['sport_id']; }
    $sql .= ' ORDER BY o.play_date, o.start_time';
    return db_all($sql, $params);
}

// Names of registered players for many open plays in ONE query
function fetch_rosters(array $playIds): array
{
    if (!$playIds) { return []; }
    $marks = implode(',', array_fill(0, count($playIds), '?'));
    $rows = db_all("SELECT r.open_play_id, r.user_id, r.slots, u.name
                    FROM open_play_registrations r JOIN users u ON u.id = r.user_id
                    WHERE r.status = 'joined' AND r.open_play_id IN ($marks)
                    ORDER BY r.created_at", $playIds);
    $out = [];
    foreach ($rows as $row) { $out[$row['open_play_id']][] = $row; }
    return $out;
}

// Which of these open plays has the logged-in user joined? [play_id => slots]
function my_registrations(int $userId, array $playIds): array
{
    if (!$playIds) { return []; }
    $marks = implode(',', array_fill(0, count($playIds), '?'));
    $rows = db_all("SELECT open_play_id, slots FROM open_play_registrations
                    WHERE status = 'joined' AND user_id = ? AND open_play_id IN ($marks)",
                   array_merge([$userId], $playIds));
    $out = [];
    foreach ($rows as $row) { $out[$row['open_play_id']] = (int)$row['slots']; }
    return $out;
}
