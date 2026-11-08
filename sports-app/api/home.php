<?php
// Data for index.html
require_once __DIR__ . '/../includes/functions.php';
json_out([
    'featured' => db_all("SELECT TOP 3 c.id, c.name, c.hourly_rate, v.name AS venue_name, v.city, v.photo, s.name AS sport
                          FROM courts c JOIN venues v ON v.id = c.venue_id JOIN sports s ON s.id = c.sport_id
                          WHERE c.is_active = 1 ORDER BY c.id"),
    'plays'    => fetch_open_plays([], 3),
]);
