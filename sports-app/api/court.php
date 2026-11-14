<?php
// One court (data for court.html). The hourly slots come from availability.php.
require_once __DIR__ . '/../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$court = db_one('SELECT c.id, c.name, c.hourly_rate, v.name AS venue_name, v.address, v.city, v.photo, v.description,
                        v.opening_hour, v.closing_hour, s.name AS sport
                 FROM courts c JOIN venues v ON v.id = c.venue_id JOIN sports s ON s.id = c.sport_id
                 WHERE c.id = ? AND c.is_active = 1 AND c.sport_id = ?', [$id, pickleball_sport_id()]);
if (!$court) { json_error('Court not found.', 404); }
json_out(['court' => $court]);
