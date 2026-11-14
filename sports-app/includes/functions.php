<?php
// Shared helpers: database shortcuts, sessions, security, formatting, availability logic.
require_once __DIR__ . '/../config/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const SKILLS = ['beginner', 'intermediate', 'advanced'];

function pickleball_sport_id(): int
{
    static $id = null;
    if ($id === null) {
        $id = (int)(db_val("SELECT TOP 1 id FROM sports WHERE name = 'Pickleball'") ?: db_val("SELECT TOP 1 id FROM sports ORDER BY id") ?: 1);
    }
    return $id;
}

/* ---------- database shortcuts (procedural sqlsrv_* with parameterized queries) ---------- */
function db_in_tx(?bool $set = null): bool
{
    static $inTx = false;
    if ($set !== null) { $inTx = $set; }
    return $inTx;
}
function db_begin(): void
{
    if (!sqlsrv_begin_transaction(db())) {
        throw new SqlsrvException('Could not begin transaction.', sqlsrv_errors() ?: []);
    }
    db_in_tx(true);
}
function db_commit(): void
{
    sqlsrv_commit(db());
    db_in_tx(false);
}
function db_rollback(): void
{
    if (db_in_tx()) {
        sqlsrv_rollback(db());
        db_in_tx(false);
    }
}
function db_query(string $sql, array $params = [])
{
    $stmt = sqlsrv_query(db(), $sql, $params);
    if ($stmt === false) {
        throw new SqlsrvException('Database query failed.', sqlsrv_errors() ?: []);
    }
    return $stmt;
}
function db_all(string $sql, array $params = []): array
{
    $stmt = db_query($sql, $params);
    $rows = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $rows[] = $row;
    }
    sqlsrv_free_stmt($stmt);
    return $rows;
}
function db_one(string $sql, array $params = []): ?array
{
    $stmt = db_query($sql, $params);
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($stmt);
    return $row ?: null;
}
function db_val(string $sql, array $params = [])
{
    $stmt = db_query($sql, $params);
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
    sqlsrv_free_stmt($stmt);
    return $row ? $row[0] : false;
}
function db_col(string $sql, array $params = []): array
{
    $stmt = db_query($sql, $params);
    $vals = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC)) {
        $vals[] = $row[0];
    }
    sqlsrv_free_stmt($stmt);
    return $vals;
}
function db_run(string $sql, array $params = []): int
{
    $stmt = db_query($sql, $params);
    $n = sqlsrv_rows_affected($stmt);
    sqlsrv_free_stmt($stmt);
    return $n === false ? 0 : $n;
}
// INSERT ... OUTPUT INSERTED.id VALUES (...)  -> returns the new IDENTITY value
function db_insert(string $sql, array $params = []): int
{
    $stmt = db_query($sql, $params);
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
    sqlsrv_free_stmt($stmt);
    return $row ? (int)$row[0] : 0;
}
// SQL Server error 2627 / 2601 = UNIQUE constraint violation (SQLSTATE 23000)
function is_duplicate_key(Throwable $e): bool
{
    if ($e instanceof SqlsrvException) {
        foreach ($e->errors as $err) {
            if ((string)($err['SQLSTATE'] ?? '') === '23000' || in_array((int)($err['code'] ?? 0), [2601, 2627], true)) {
                return true;
            }
        }
    }
    return false;
}
// Escape characters that have a special meaning inside a T-SQL LIKE pattern
function like_escape(string $s): string
{
    return str_replace(['[', '%', '_'], ['[[]', '[%]', '[_]'], $s);
}

/* ---------- output / routing ---------- */
// The pages are plain .html files. PHP never prints HTML: it either redirects (actions/) or returns JSON (api/).
function url(string $p = ''): string { return BASE_URL . '/' . ltrim($p, '/'); }
function redirect(string $p): void { header('Location: ' . url($p)); exit; }

// JSON response used by every file in api/
function json_out($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
function json_error(string $msg, int $status = 400): void { json_out(['error' => $msg], $status); }

/* ---------- flash messages + remembered form input (shown by the .html page through api/session.php) ---------- */
function flash(string $type, string $msg): void { $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg]; }
function pull_flashes(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }
function old_input(array $data): void { unset($data['csrf'], $data['password'], $data['password2']); $_SESSION['old'] = $data; }
function pull_old(): array { $o = $_SESSION['old'] ?? []; unset($_SESSION['old']); return $o; }

/* ---------- CSRF protection for every POST form (the token is put into the form by assets/js/common.js) ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf'];
}
function csrf_check(): void
{
    $expected = $_SESSION['csrf'] ?? '';
    // an empty token must never match (hash_equals('', '') is true)
    if ($expected === '' || !hash_equals($expected, (string)($_POST['csrf'] ?? ''))) {
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

// Call at the top of a form handler in actions/. $role = 'player' | 'manager' | null (any).
function require_login(?string $role = null): array
{
    $u = current_user();
    if (!$u) {
        flash('error', 'Please log in first.');
        redirect('login.html');
    }
    if ($role !== null && $u['role'] !== $role) {
        http_response_code(403);
        exit('403 - Your account type cannot do this.');
    }
    return $u;
}

// Same check for a JSON endpoint in api/: answers 401 (not logged in) or 403 (wrong role) as JSON.
function api_require(?string $role = null): array
{
    $u = current_user();
    if (!$u) { json_error('Please log in first.', 401); }
    if ($role !== null && $u['role'] !== $role) { json_error('Your account type cannot open this page.', 403); }
    return $u;
}

function manager_venue(int $userId): array
{
    $v = db_one('SELECT * FROM venues WHERE manager_id = ?', [$userId]);
    if (!$v) { json_error('No venue is linked to this account.', 404); }
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
            WHERE (o.play_date > ? OR (o.play_date = ? AND o.end_time > ?))
              AND o.sport_id = ?";
    $params = [date('Y-m-d'), date('Y-m-d'), date('H:i:s'), pickleball_sport_id()];
    if (!empty($f['date']))     { $sql .= ' AND o.play_date = ?'; $params[] = $f['date']; }
    if (!empty($f['venue_id'])) { $sql .= ' AND o.venue_id = ?';  $params[] = (int)$f['venue_id']; }
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
