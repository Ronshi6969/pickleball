<?php
// Block out unavailable hours (maintenance, holidays) for one court or all courts
require_once __DIR__ . '/../includes/functions.php';
$user  = require_login('manager');
$venue = manager_venue((int)$user['id']);
$vid   = (int)$venue['id'];
$errors = [];

$courts = db_all('SELECT id, name FROM courts WHERE venue_id = ? ORDER BY name', [$vid]);
$courtIds = array_map('intval', array_column($courts, 'id'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'add') {
        $court = $_POST['court_id'] ?? '';
        $date  = $_POST['block_date'] ?? '';
        $start = (int)($_POST['start_hour'] ?? -1);
        $end   = (int)($_POST['end_hour'] ?? -1);
        $reason = trim($_POST['reason'] ?? '');
        $targets = $court === 'all' ? $courtIds : (in_array((int)$court, $courtIds, true) ? [(int)$court] : []);

        if (!$targets)                                  { $errors[] = 'Choose a court.'; }
        if (!valid_date($date) || $date < date('Y-m-d')) { $errors[] = 'Choose a date that is today or later.'; }
        if ($start < 0 || $end > 24 || $end <= $start)   { $errors[] = 'End time must be later than start time.'; }
        if (!$errors) {
            foreach ($targets as $cid) {
                db_run('INSERT INTO court_blocks (court_id, block_date, start_hour, end_hour, reason) VALUES (?, ?, ?, ?, ?)',
                       [$cid, $date, $start, $end, $reason !== '' ? $reason : null]);
            }
            flash('success', 'Hours blocked. Players can no longer book them.');
            redirect('manager/blocks.php');
        }
    } elseif (($_POST['action'] ?? '') === 'delete') {
        // join to courts so a manager can only delete blocks on their own courts
        db_run('DELETE cb FROM court_blocks AS cb JOIN courts AS c ON c.id = cb.court_id WHERE cb.id = ? AND c.venue_id = ?',
               [(int)($_POST['id'] ?? 0), $vid]);
        flash('success', 'Block removed.');
        redirect('manager/blocks.php');
    }
}

$blocks = db_all('SELECT cb.*, c.name AS court_name FROM court_blocks cb JOIN courts c ON c.id = cb.court_id
                  WHERE c.venue_id = ? AND cb.block_date >= ? ORDER BY cb.block_date, cb.start_hour, c.name', [$vid, date('Y-m-d')]);
foreach ($blocks as $i => $b) { $blocks[$i]['time_text'] = hour_range((int)$b['start_hour'], (int)$b['end_hour']); }

render('manager/blocks', [
    'pageTitle' => 'Blocked hours', 'navActive' => 'manager', 'mnav' => 'blocks',
    'user' => $user, 'venue' => $venue, 'errors' => $errors, 'courts' => $courts, 'blocks' => $blocks,
]);
