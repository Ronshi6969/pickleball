<?php
// Creates a court booking. All-or-nothing inside one transaction.
require_once __DIR__ . '/includes/functions.php';
$user = require_login('player');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('venues.php'); }
csrf_check();

$pdo     = db();
$courtId = (int)($_POST['court_id'] ?? 0);
$date    = $_POST['date'] ?? '';
$start   = (int)($_POST['start_hour'] ?? -1);
$end     = (int)($_POST['end_hour'] ?? -1);
$back    = 'court.php?id=' . $courtId . '&date=' . urlencode($date);

try {
    $court = db_one('SELECT c.hourly_rate, v.opening_hour, v.closing_hour
                     FROM courts c JOIN venues v ON v.id = c.venue_id WHERE c.id = ? AND c.is_active = 1', [$courtId]);

    if (!$court)                                  { throw new RuntimeException('That court is not available.'); }
    if (!valid_date($date) || $date < date('Y-m-d')) { throw new RuntimeException('Choose a valid date that is not in the past.'); }
    if ($start >= $end || $start < (int)$court['opening_hour'] || $end > (int)$court['closing_hour']) {
        throw new RuntimeException('Choose hours within the venue opening times.');
    }

    $pdo->beginTransaction();

    // Re-check every hour right now (someone may have booked it while this page was open)
    $status = [];
    foreach (court_slots($courtId, $date) as $s) { $status[$s['hour']] = $s['status']; }
    for ($h = $start; $h < $end; $h++) {
        if (($status[$h] ?? '') !== 'open') {
            throw new RuntimeException(hour_label($h) . ' is no longer available. Pick another time.');
        }
    }

    $total = (float)$court['hourly_rate'] * ($end - $start);
    $ref   = 'BK-' . strtoupper(bin2hex(random_bytes(4)));

    $bookingId = db_insert('INSERT INTO bookings (reference, user_id, court_id, booking_date, start_hour, end_hour, total_price)
                            OUTPUT INSERTED.id VALUES (?, ?, ?, ?, ?, ?, ?)',
                           [$ref, $user['id'], $courtId, $date, $start, $end, $total]);

    for ($h = $start; $h < $end; $h++) {
        db_run('INSERT INTO booking_slots (booking_id, court_id, slot_date, slot_hour) VALUES (?, ?, ?, ?)',
               [$bookingId, $courtId, $date, $h]);
    }

    $pdo->commit();
    redirect('booking_confirmation.php?ref=' . urlencode($ref));

} catch (RuntimeException $ex) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    flash('error', $ex->getMessage());
    redirect($back);
} catch (PDOException $ex) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    // UNIQUE key violation: another player got the same hour a split second earlier
    flash('error', is_duplicate_key($ex)
        ? 'Someone just booked one of those hours. Pick another time.'
        : 'Something went wrong while booking. Please try again.');
    redirect($back);
}
