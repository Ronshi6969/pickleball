<?php
// One court: interactive date picker + hourly slot grid + booking summary
require_once __DIR__ . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$court = db_one('SELECT c.*, v.name AS venue_name, v.address, v.city, v.photo, v.description,
                        v.opening_hour, v.closing_hour, s.name AS sport
                 FROM courts c JOIN venues v ON v.id = c.venue_id JOIN sports s ON s.id = c.sport_id
                 WHERE c.id = ? AND c.is_active = 1', [$id]);
if (!$court) { http_response_code(404); exit('Court not found.'); }

$user = current_user();
$canBook = $user && $user['role'] === 'player';
$date = $_GET['date'] ?? date('Y-m-d');
if (!valid_date($date) || $date < date('Y-m-d')) { $date = date('Y-m-d'); }

render('court', [
    'pageTitle' => $court['name'],
    'navActive' => 'courts',
    'user'      => $user,
    'court'     => $court,
    'canBook'   => $canBook,
    'date'      => $date,
    'closeText' => (int)$court['closing_hour'] === 24 ? '12:00 AM' : hour_label((int)$court['closing_hour']),
]);
