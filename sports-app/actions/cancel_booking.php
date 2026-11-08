<?php
// Player cancels a court booking. Deleting its booking_slots rows frees the hours.
require_once __DIR__ . '/../includes/functions.php';
$user = require_login('player');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('dashboard.html'); }
csrf_check();

$pdo = db();
$id  = (int)($_POST['id'] ?? 0);
try {
    $pdo->beginTransaction();
    // WITH (UPDLOCK, ROWLOCK) = SQL Server's version of MySQL's "FOR UPDATE"
    $b = db_one("SELECT * FROM bookings WITH (UPDLOCK, ROWLOCK) WHERE id = ? AND user_id = ? AND status = 'confirmed'", [$id, $user['id']]);
    $startsAt = $b ? booking_start($b['booking_date'], (int)$b['start_hour']) : '';

    if (!$b || $startsAt <= date('Y-m-d H:i:s')) {
        $pdo->rollBack();
        flash('error', 'This booking cannot be cancelled.');
    } else {
        db_run('DELETE FROM booking_slots WHERE booking_id = ?', [$id]);
        db_run("UPDATE bookings SET status = 'cancelled' WHERE id = ?", [$id]);
        $pdo->commit();
        flash('success', 'Booking ' . $b['reference'] . ' was cancelled.');
    }
} catch (PDOException $ex) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    flash('error', 'Something went wrong. Please try again.');
}
redirect('dashboard.html');
