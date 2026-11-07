<?php
// Manager view of court rentals at their venue, with cancel
require_once __DIR__ . '/../includes/functions.php';
$user  = require_login('manager');
$venue = manager_venue((int)$user['id']);
$vid   = (int)$venue['id'];
$show  = ($_GET['show'] ?? 'upcoming') === 'all' ? 'all' : 'upcoming';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id  = (int)($_POST['id'] ?? 0);
    $pdo = db();
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
    redirect('manager/bookings.php?show=' . $show);
}

$sql = "SELECT b.*, c.name AS court_name, u.name AS player, u.email
        FROM bookings b JOIN courts c ON c.id = b.court_id JOIN users u ON u.id = b.user_id
        WHERE c.venue_id = ?";
$params = [$vid];
if ($show === 'upcoming') {
    $sql .= " AND b.status = 'confirmed' AND b.booking_date >= ?";
    $params[] = date('Y-m-d');
}
$sql .= ' ORDER BY b.booking_date ' . ($show === 'upcoming' ? 'ASC' : 'DESC') . ', b.start_hour';
$rows = db_all($sql, $params);

$now = date('Y-m-d H:i:s');
foreach ($rows as $i => $b) {
    $rows[$i]['future']    = $b['status'] === 'confirmed' && booking_start($b['booking_date'], (int)$b['start_hour']) > $now;
    $rows[$i]['time_text'] = hour_range((int)$b['start_hour'], (int)$b['end_hour']);
}

render('manager/bookings', [
    'pageTitle' => 'Court rentals', 'navActive' => 'manager', 'mnav' => 'bookings',
    'user' => $user, 'show' => $show, 'rows' => $rows,
]);
