<?php
// Open plays of this venue (manager/openplays.html).
// ?edit=ID returns that play for the form, ?roster=ID returns the players who joined.
require_once __DIR__ . '/../../includes/functions.php';
$user  = api_require('manager');
$venue = manager_venue((int)$user['id']);
$vid   = (int)$venue['id'];

function own_play(int $id, int $vid): ?array
{
    return db_one('SELECT * FROM open_plays WHERE id = ? AND venue_id = ?', [$id, $vid]);
}

$editing = isset($_GET['edit']) ? own_play((int)$_GET['edit'], $vid) : null;
if ($editing) {
    $editing['start_time'] = hm($editing['start_time']);   // "07:00:00.0000000" -> "07:00"
    $editing['end_time']   = hm($editing['end_time']);
}

$plays = db_all("SELECT o.id, o.title, o.play_date, o.start_time, o.end_time, o.skill_level, o.max_slots, o.price, s.name AS sport,
                        COALESCE((SELECT SUM(r.slots) FROM open_play_registrations r WHERE r.open_play_id = o.id AND r.status = 'joined'), 0) AS taken
                 FROM open_plays o JOIN sports s ON s.id = o.sport_id WHERE o.venue_id = ? ORDER BY o.play_date DESC, o.start_time", [$vid]);
foreach ($plays as $i => $p) {
    $plays[$i]['start_time'] = hms($p['start_time']);
    $plays[$i]['end_time']   = hms($p['end_time']);
    $plays[$i]['past']       = $p['play_date'] < date('Y-m-d');
}

$rosterId = (int)($_GET['roster'] ?? 0);
$roster = $rosterId && own_play($rosterId, $vid) ? (fetch_rosters([$rosterId])[$rosterId] ?? []) : null;

json_out([
    'sports'  => db_all('SELECT id, name FROM sports ORDER BY name'),
    'editing' => $editing,
    'plays'   => $plays,
    'roster'  => $roster,
]);
