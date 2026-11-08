<?php
// Data for booking_confirmation.html (owner only)
require_once __DIR__ . '/../includes/functions.php';
$user = api_require('player');

$b = db_one('SELECT b.id, b.reference, b.booking_date, b.start_hour, b.end_hour, b.total_price,
                    c.name AS court_name, v.name AS venue_name, v.address, v.city
             FROM bookings b JOIN courts c ON c.id = b.court_id JOIN venues v ON v.id = c.venue_id
             WHERE b.reference = ? AND b.user_id = ?', [$_GET['ref'] ?? '', $user['id']]);
if (!$b) { json_error('Booking not found.', 404); }
$b['time_text'] = hour_range((int)$b['start_hour'], (int)$b['end_hour']);
json_out(['booking' => $b]);
