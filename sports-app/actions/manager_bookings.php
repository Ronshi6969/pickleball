<?php
// Form handler for manager/bookings.html: manager creates or cancels a court rental at their venue.
require_once __DIR__ . '/../includes/functions.php';
$user  = require_login('manager');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('manager/bookings.html'); }
csrf_check();
$venue  = manager_venue((int)$user['id']);
$vid    = (int)$venue['id'];
$pid    = pickleball_sport_id();
$show   = ($_POST['show'] ?? 'upcoming') === 'all' ? 'all' : 'upcoming';
$action = $_POST['action'] ?? 'cancel';

if ($action === 'create') {
    $courtId  = (int)($_POST['court_id'] ?? 0);
    $playerId = (int)($_POST['user_id'] ?? 0);
    $date     = trim($_POST['booking_date'] ?? '');
    $start    = (int)($_POST['start_hour'] ?? -1);
    $end      = (int)($_POST['end_hour'] ?? -1);

    try {
        $court = db_one('SELECT id, name, hourly_rate FROM courts WHERE id = ? AND venue_id = ? AND sport_id = ? AND is_active = 1', [$courtId, $vid, $pid]);
        if (!$court) { throw new RuntimeException('Select a valid active court at your venue.'); }
        $player = db_one("SELECT id, name FROM users WHERE id = ? AND role = 'player'", [$playerId]);
        if (!$player) { throw new RuntimeException('Select a valid player.'); }
        if (!valid_date($date) || $date < date('Y-m-d')) { throw new RuntimeException('Choose a valid date that is not in the past.'); }
        if ($start >= $end || $start < (int)$venue['opening_hour'] || $end > (int)$venue['closing_hour']) {
            throw new RuntimeException('Choose hours within your venue opening times.');
        }

        db_begin();
        $status = [];
        foreach (court_slots($courtId, $date) as $s) { $status[$s['hour']] = $s['status']; }
        for ($h = $start; $h < $end; $h++) {
            if (($status[$h] ?? '') !== 'open') {
                throw new RuntimeException(hour_label($h) . ' is not available on that date.');
            }
        }

        $total = (float)$court['hourly_rate'] * ($end - $start);
        $ref   = 'BK-' . strtoupper(bin2hex(random_bytes(4)));

        $bookingId = db_insert('INSERT INTO bookings (reference, user_id, court_id, booking_date, start_hour, end_hour, total_price)
                                OUTPUT INSERTED.id VALUES (?, ?, ?, ?, ?, ?, ?)',
                               [$ref, $playerId, $courtId, $date, $start, $end, $total]);

        for ($h = $start; $h < $end; $h++) {
            db_run('INSERT INTO booking_slots (booking_id, court_id, slot_date, slot_hour) VALUES (?, ?, ?, ?)',
                   [$bookingId, $courtId, $date, $h]);
        }

        db_commit();
        flash('success', "Court rental {$ref} created for {$player['name']}.");
        redirect('manager/bookings.html?show=' . $show);
    } catch (SqlsrvException $ex) {
        db_rollback();
        flash('error', is_duplicate_key($ex)
            ? 'One of those hours was just booked. Pick another time.'
            : 'Could not create the court rental. Please try again.');
    } catch (RuntimeException $ex) {
        db_rollback();
        flash('error', $ex->getMessage());
    }
    old_input(['court_id' => $courtId, 'user_id' => $playerId, 'booking_date' => $date, 'start_hour' => $start, 'end_hour' => $end]);
    redirect('manager/bookings.html?show=' . $show);
}

$id = (int)($_POST['id'] ?? 0);
try {
    db_begin();
    $row = db_one("SELECT b.id, b.reference, c.name AS court_name FROM bookings AS b WITH (UPDLOCK, ROWLOCK)
                   JOIN courts AS c ON c.id = b.court_id
                   WHERE b.id = ? AND c.venue_id = ? AND b.status = 'confirmed'", [$id, $vid]);
    if ($row) {
        db_run('DELETE FROM booking_slots WHERE booking_id = ?', [$id]);
        db_run("UPDATE bookings SET status = 'cancelled' WHERE id = ?", [$id]);
        db_commit();
        flash('success', 'Booking for ' . $row['court_name'] . ' (Ref ' . $row['reference'] . ') was cancelled. The hours are open again.');
    } else {
        db_rollback();
        flash('error', 'Booking not found.');
    }
} catch (SqlsrvException $ex) {
    db_rollback();
    flash('error', 'Something went wrong. Please try again.');
}
redirect('manager/bookings.html?show=' . $show);
