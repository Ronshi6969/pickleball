<?php
// Form handler for manager/blocks.html: block out unavailable hours (maintenance, holidays).
require_once __DIR__ . '/../includes/functions.php';
$user  = require_login('manager');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('manager/blocks.html'); }
csrf_check();
$venue = manager_venue((int)$user['id']);
$vid   = (int)$venue['id'];

$courtIds = array_map('intval', db_col('SELECT id FROM courts WHERE venue_id = ?', [$vid]));
$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $court  = $_POST['court_id'] ?? '';
    $date   = $_POST['block_date'] ?? '';
    $start  = (int)($_POST['start_hour'] ?? -1);
    $end    = (int)($_POST['end_hour'] ?? -1);
    $reason = trim($_POST['reason'] ?? '');
    $targets = $court === 'all' ? $courtIds : (in_array((int)$court, $courtIds, true) ? [(int)$court] : []);

    $errors = [];
    if (!$targets)                                  { $errors[] = 'Choose a court.'; }
    if (!valid_date($date) || $date < date('Y-m-d')) { $errors[] = 'Choose a date that is today or later.'; }
    if ($start < 0 || $end > 24 || $end <= $start)   { $errors[] = 'End time must be later than start time.'; }
    if (!$errors) {
        foreach ($targets as $cid) {
            db_run('INSERT INTO court_blocks (court_id, block_date, start_hour, end_hour, reason) VALUES (?, ?, ?, ?, ?)',
                   [$cid, $date, $start, $end, $reason !== '' ? $reason : null]);
        }
        flash('success', 'Hours blocked. Players can no longer book them.');
    } else {
        foreach ($errors as $er) { flash('error', $er); }
        old_input(['court_id' => $court, 'block_date' => $date, 'start_hour' => $start, 'end_hour' => $end, 'reason' => $reason]);
    }
} elseif ($action === 'delete') {
    // join to courts so a manager can only delete blocks on their own courts
    db_run('DELETE cb FROM court_blocks AS cb JOIN courts AS c ON c.id = cb.court_id WHERE cb.id = ? AND c.venue_id = ?',
           [(int)($_POST['id'] ?? 0), $vid]);
    flash('success', 'Block removed.');
}
redirect('manager/blocks.html');
