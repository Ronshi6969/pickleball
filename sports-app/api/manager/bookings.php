<?php
// Court rentals at this venue + active courts and players for creating a court rental (manager/bookings.html)
require_once __DIR__ . '/../../includes/functions.php';
$user  = api_require('manager');
$venue = manager_venue((int)$user['id']);
$vid   = (int)$venue['id'];
$pid   = pickleball_sport_id();
$show  = ($_GET['show'] ?? 'upcoming') === 'all' ? 'all' : 'upcoming';

$sql = "SELECT b.id, b.reference, b.booking_date, b.start_hour, b.end_hour, b.total_price, b.status,
               c.name AS court_name, u.name AS player, u.email
        FROM bookings b JOIN courts c ON c.id = b.court_id JOIN users u ON u.id = b.user_id
        WHERE c.venue_id = ? AND c.sport_id = ?";
$params = [$vid, $pid];
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

json_out([
    'show'         => $show,
    'rows'         => $rows,
    'courts'       => db_all('SELECT id, name, hourly_rate FROM courts WHERE venue_id = ? AND sport_id = ? AND is_active = 1 ORDER BY name', [$vid, $pid]),
    'players'      => db_all("SELECT id, name, email FROM users WHERE role = 'player' ORDER BY name"),
    'opening_hour' => (int)$venue['opening_hour'],
    'closing_hour' => (int)$venue['closing_hour'],
]);
