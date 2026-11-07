<?php
// JSON endpoint used by the court booking date picker.
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

$courtId = (int)($_GET['court_id'] ?? 0);
$date    = $_GET['date'] ?? '';
if ($courtId < 1 || !valid_date($date)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid court or date.']);
    exit;
}
echo json_encode(['slots' => court_slots($courtId, $date)]);
