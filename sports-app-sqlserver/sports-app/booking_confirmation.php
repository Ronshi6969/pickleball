<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_login('player');

$b = db_one('SELECT b.*, c.name AS court_name, v.name AS venue_name, v.address, v.city
             FROM bookings b JOIN courts c ON c.id = b.court_id JOIN venues v ON v.id = c.venue_id
             WHERE b.reference = ? AND b.user_id = ?', [$_GET['ref'] ?? '', $user['id']]);
if (!$b) { http_response_code(404); exit('Booking not found.'); }

render('booking_confirmation', [
    'pageTitle' => 'Booking confirmed',
    'user'      => $user,
    'b'         => $b,
    'timeText'  => hour_range((int)$b['start_hour'], (int)$b['end_hour']),
]);
