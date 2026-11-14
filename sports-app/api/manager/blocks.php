<?php
// Blocked hours + courts of this venue (manager/blocks.html)
require_once __DIR__ . '/../../includes/functions.php';
$user  = api_require('manager');
$venue = manager_venue((int)$user['id']);
$vid   = (int)$venue['id'];

$pid    = pickleball_sport_id();
$blocks = db_all('SELECT cb.id, cb.block_date, cb.start_hour, cb.end_hour, cb.reason, c.name AS court_name
                  FROM court_blocks cb JOIN courts c ON c.id = cb.court_id
                  WHERE c.venue_id = ? AND c.sport_id = ? AND cb.block_date >= ? ORDER BY cb.block_date, cb.start_hour, c.name', [$vid, $pid, date('Y-m-d')]);
foreach ($blocks as $i => $b) { $blocks[$i]['time_text'] = hour_range((int)$b['start_hour'], (int)$b['end_hour']); }

json_out([
    'courts' => db_all('SELECT id, name FROM courts WHERE venue_id = ? AND sport_id = ? ORDER BY name', [$vid, $pid]),
    'blocks' => $blocks,
    'opening_hour' => (int)$venue['opening_hour'],
    'closing_hour' => (int)$venue['closing_hour'],
]);
