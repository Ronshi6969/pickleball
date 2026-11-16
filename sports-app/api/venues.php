<?php
// Pickleball court catalog with search: court/venue name, location (data for venues.html)
require_once __DIR__ . '/../includes/functions.php';

$q   = trim($_GET['q'] ?? '');
$loc = trim($_GET['location'] ?? '');
$pid = pickleball_sport_id();

$sql = "SELECT c.id, c.name, c.hourly_rate, v.name AS venue_name, v.address, v.city, v.photo,
               v.opening_hour, v.closing_hour, s.name AS sport
        FROM courts c
        JOIN venues v ON v.id = c.venue_id
        JOIN sports s ON s.id = c.sport_id
        WHERE c.is_active = 1 AND c.sport_id = ?";
$params = [$pid];
if ($q !== '')   { $like = '%' . like_escape($q) . '%';   $sql .= ' AND (c.name LIKE ? OR v.name LIKE ?)';     array_push($params, $like, $like); }
if ($loc !== '') { $like = '%' . like_escape($loc) . '%'; $sql .= ' AND (v.city LIKE ? OR v.address LIKE ?)'; array_push($params, $like, $like); }
$sql .= ' ORDER BY v.city, c.name';

$locations = db_all(
    "SELECT v.city, v.name, COUNT(c.id) AS court_count
     FROM venues v
     LEFT JOIN courts c ON c.venue_id = v.id AND c.is_active = 1 AND c.sport_id = ?
     GROUP BY v.city, v.name
     ORDER BY v.city",
    [$pid]
);

json_out([
    'courts'    => db_all($sql, $params),
    'locations' => $locations,
]);
