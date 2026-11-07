<?php
// Player dashboard: upcoming court bookings + open plays, and past activity
require_once __DIR__ . '/includes/functions.php';
$user  = require_login('player');
$tab   = ($_GET['tab'] ?? 'upcoming') === 'history' ? 'history' : 'upcoming';
$uid   = (int)$user['id'];
$today = date('Y-m-d');

$bookingSql = "SELECT b.*, c.name AS court_name, v.name AS venue_name
               FROM bookings b JOIN courts c ON c.id = b.court_id JOIN venues v ON v.id = c.venue_id
               WHERE b.user_id = ? AND ";
$playSql = "SELECT r.id AS reg_id, r.slots, r.amount, r.status, o.id AS play_id, o.title, o.play_date,
                   o.start_time, o.end_time, v.name AS venue_name
            FROM open_play_registrations r JOIN open_plays o ON o.id = r.open_play_id JOIN venues v ON v.id = o.venue_id
            WHERE r.user_id = ? AND ";

if ($tab === 'upcoming') {
    $bookings = db_all($bookingSql . "b.status = 'confirmed' AND b.booking_date >= ? ORDER BY b.booking_date, b.start_hour", [$uid, $today]);
    $plays    = db_all($playSql . "r.status = 'joined' AND o.play_date >= ? ORDER BY o.play_date, o.start_time", [$uid, $today]);
} else {
    $bookings = db_all($bookingSql . "(b.status = 'cancelled' OR b.booking_date < ?) ORDER BY b.booking_date DESC", [$uid, $today]);
    $plays    = db_all($playSql . "(r.status = 'cancelled' OR o.play_date < ?) ORDER BY o.play_date DESC", [$uid, $today]);
}

// Extra display info computed here, so the view only prints it
$now = date('Y-m-d H:i:s');
foreach ($bookings as $i => $b) {
    $startsAt = booking_start($b['booking_date'], (int)$b['start_hour']);
    $bookings[$i]['cancellable'] = $b['status'] === 'confirmed' && $startsAt > $now;
    $bookings[$i]['time_text']   = hour_range((int)$b['start_hour'], (int)$b['end_hour']);
}

$mySports = db_col('SELECT s.name FROM user_sports us JOIN sports s ON s.id = us.sport_id WHERE us.user_id = ? ORDER BY s.name', [$uid]);

render('dashboard', [
    'pageTitle' => 'My plays',
    'navActive' => 'dash',
    'user'      => $user,
    'tab'       => $tab,
    'bookings'  => $bookings,
    'plays'     => $plays,
    'mySports'  => $mySports,
]);
