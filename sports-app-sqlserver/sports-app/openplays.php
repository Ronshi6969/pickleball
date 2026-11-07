<?php
// Upcoming open plays grouped by date, with slots left, roster and join/leave
require_once __DIR__ . '/includes/functions.php';
$user = current_user();

$date  = $_GET['date'] ?? '';
$venue = (int)($_GET['venue'] ?? 0);
$sport = (int)($_GET['sport'] ?? 0);
if ($date !== '' && !valid_date($date)) { $date = ''; }

$plays   = fetch_open_plays(['date' => $date, 'venue_id' => $venue, 'sport_id' => $sport]);
$ids     = array_map('intval', array_column($plays, 'id'));
$rosters = fetch_rosters($ids);
$mine    = ($user && $user['role'] === 'player') ? my_registrations((int)$user['id'], $ids) : [];

// Work out everything the view needs to print, grouped by date
$now = date('Y-m-d H:i:s');
$byDate = [];
foreach ($plays as $p) {
    $max  = (int)$p['max_slots'];
    $left = max(0, (int)$p['slots_left']);
    $p['left']    = $left;
    $p['pct']     = $max > 0 ? (int)round(($max - $left) / $max * 100) : 0;
    $p['roster']  = $rosters[$p['id']] ?? [];
    $p['myslots'] = $mine[$p['id']] ?? 0;
    $p['started'] = ($p['play_date'] . ' ' . hms($p['start_time'])) <= $now;
    $byDate[$p['play_date']][] = $p;
}

render('openplays', [
    'pageTitle' => 'Open play',
    'navActive' => 'open',
    'user'      => $user,
    'byDate'    => $byDate,
    'venues'    => db_all('SELECT id, name FROM venues ORDER BY name'),
    'sports'    => db_all('SELECT id, name FROM sports ORDER BY name'),
    'date' => $date, 'venue' => $venue, 'sport' => $sport,
]);
