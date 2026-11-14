<?php
// Player cancels a court booking. Deleting its booking_slots rows frees the hours.
require_once __DIR__ . '/../includes/functions.php';
$user = require_login('player');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('dashboard.html'); }
csrf_check();

$id  = (int)($_POST['id'] ?? 0);
try {
    db_begin();
    // WITH (UPDLOCK, ROWLOCK) = SQL Server's version of MySQL's "FOR UPDATE"
    $b = db_one("SELECT b.*, c.name AS court_name, v.name AS venue_name
                 FROM bookings AS b WITH (UPDLOCK, ROWLOCK)
                 JOIN courts AS c ON c.id = b.court_id
                 JOIN venues AS v ON v.id = c.venue_id
                 WHERE b.id = ? AND b.user_id = ? AND b.status = 'confirmed'", [$id, $user['id']]);
    $startsAt = $b ? booking_start($b['booking_date'], (int)$b['start_hour']) : '';

    if (!$b || $startsAt <= date('Y-m-d H:i:s')) {
        db_rollback();
        flash('error', 'This booking cannot be cancelled.');
    } else {
        db_run('DELETE FROM booking_slots WHERE booking_id = ?', [$id]);
        db_run("UPDATE bookings SET status = 'cancelled' WHERE id = ?", [$id]);
        db_commit();
        flash('success', 'Your booking for ' . $b['court_name'] . ' at ' . $b['venue_name'] . ' (Ref ' . $b['reference'] . ') was cancelled.');
    }
} catch (SqlsrvException $ex) {
    db_rollback();
    flash('error', 'Something went wrong. Please try again.');
}
redirect('dashboard.html');
