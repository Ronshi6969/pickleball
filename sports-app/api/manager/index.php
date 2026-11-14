<?php
// Manager overview data (manager/index.html)
require_once __DIR__ . '/../../includes/functions.php';
$user  = api_require('manager');
$venue = manager_venue((int)$user['id']);
$vid   = (int)$venue['id'];
$today = date('Y-m-d');
$pid   = pickleball_sport_id();

json_out([
    'venue' => $venue,
    'stats' => [
        'courts'  => (int)db_val('SELECT COUNT(*) FROM courts WHERE venue_id = ? AND sport_id = ? AND is_active = 1', [$vid, $pid]),
        'upBook'  => (int)db_val("SELECT COUNT(*) FROM bookings b JOIN courts c ON c.id = b.court_id
                                  WHERE c.venue_id = ? AND c.sport_id = ? AND b.status = 'confirmed' AND b.booking_date >= ?", [$vid, $pid, $today]),
        'upPlays' => (int)db_val('SELECT COUNT(*) FROM open_plays WHERE venue_id = ? AND sport_id = ? AND play_date >= ?', [$vid, $pid, $today]),
        'players' => (int)db_val("SELECT COALESCE(SUM(r.slots), 0) FROM open_play_registrations r JOIN open_plays o ON o.id = r.open_play_id
                                  WHERE o.venue_id = ? AND o.sport_id = ? AND o.play_date >= ? AND r.status = 'joined'", [$vid, $pid, $today]),
    ],
]);
