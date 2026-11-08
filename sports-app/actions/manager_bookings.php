<?php
// Form handler for manager/bookings.html: the manager cancels a court rental at their venue.
require_once __DIR__ . '/../includes/functions.php';
$user  = require_login('manager');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('manager/bookings.html'); }
csrf_check();
$venue = manager_venue((int)$user['id']);
$vid   = (int)$venue['id'];
$show  = ($_POST['show'] ?? 'upcoming') === 'all' ? 'all' : 'upcoming';

$id  = (int)($_POST['id'] ?? 0);
$pdo = db();
try {
    $pdo->beginTransaction();
    $row = db_one("SELECT b.id FROM bookings AS b WITH (UPDLOCK, ROWLOCK) JOIN courts AS c ON c.id = b.court_id
                   WHERE b.id = ? AND c.venue_id = ? AND b.status = 'confirmed'", [$id, $vid]);
    if ($row) {
        db_run('DELETE FROM booking_slots WHERE booking_id = ?', [$id]);
        db_run("UPDATE bookings SET status = 'cancelled' WHERE id = ?", [$id]);
        $pdo->commit();
        flash('success', 'Booking cancelled. The hours are open again.');
    } else {
        $pdo->rollBack();
        flash('error', 'Booking not found.');
    }
} catch (PDOException $ex) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    flash('error', 'Something went wrong. Please try again.');
}
redirect('manager/bookings.html?show=' . $show);
