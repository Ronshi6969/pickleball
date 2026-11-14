<?php
// Upcoming open plays grouped by date, with slots left, roster and what the logged-in player joined
// (data for openplays.html)
require_once __DIR__ . '/../includes/functions.php';
$user = current_user();

$date  = $_GET['date'] ?? '';
$venue = (int)($_GET['venue'] ?? 0);
if ($date !== '' && !valid_date($date)) { $date = ''; }

$plays   = fetch_open_plays(['date' => $date, 'venue_id' => $venue]);
$ids     = array_map('intval', array_column($plays, 'id'));
$rosters = fetch_rosters($ids);
$mine    = ($user && $user['role'] === 'player') ? my_registrations((int)$user['id'], $ids) : [];

$now = date('Y-m-d H:i:s');
$groups = [];
foreach ($plays as $p) {
    $max  = (int)$p['max_slots'];
    $left = max(0, (int)$p['slots_left']);
    $p['start_time'] = hms($p['start_time']);
    $p['end_time']   = hms($p['end_time']);
    $p['left']    = $left;
    $p['pct']     = $max > 0 ? (int)round(($max - $left) / $max * 100) : 0;
    $p['roster']  = $rosters[$p['id']] ?? [];
    $p['myslots'] = $mine[$p['id']] ?? 0;
    $p['started'] = ($p['play_date'] . ' ' . $p['start_time']) <= $now;
    $groups[$p['play_date']][] = $p;
}
$byDate = [];
foreach ($groups as $d => $list) { $byDate[] = ['date' => $d, 'plays' => $list]; }

json_out([
    'groups' => $byDate,
    'venues' => db_all('SELECT id, name FROM venues ORDER BY name'),
]);
