<?php
// Printable receipt data for a court booking or an open play registration (owner only)
require_once __DIR__ . '/../includes/functions.php';
$user = api_require('player');
$type = $_GET['type'] ?? '';
$id   = (int)($_GET['id'] ?? 0);

if ($type === 'booking') {
    $sql = "SELECT b.reference, b.total_price AS amount, b.status, b.created_at, b.booking_date AS d,
                   b.start_hour, b.end_hour, c.name AS item, v.name AS venue
            FROM bookings b JOIN courts c ON c.id = b.court_id JOIN venues v ON v.id = c.venue_id
            WHERE b.id = ? AND b.user_id = ?";
} elseif ($type === 'openplay') {
    $sql = "SELECT CONCAT('OP-', r.id) AS reference, r.amount, r.status, r.created_at, o.play_date AS d,
                   o.start_time, o.end_time, r.slots, o.title AS item, v.name AS venue
            FROM open_play_registrations r JOIN open_plays o ON o.id = r.open_play_id JOIN venues v ON v.id = o.venue_id
            WHERE r.id = ? AND r.user_id = ?";
} else {
    json_error('Receipt not found.', 404);
}
$r = db_one($sql, [$id, $user['id']]);
if (!$r) { json_error('Receipt not found.', 404); }

$r['time_text'] = $type === 'booking'
    ? hour_range((int)$r['start_hour'], (int)$r['end_hour'])
    : time_label($r['start_time']) . ' to ' . time_label($r['end_time']);
$r['issued_text'] = date('M j, Y g:i A', strtotime($r['created_at']));
$r['status_text'] = ($type === 'openplay' && $r['status'] === 'cancelled') ? 'Left' : ucfirst($r['status']);
$r['type'] = $type;
json_out(['receipt' => $r]);
