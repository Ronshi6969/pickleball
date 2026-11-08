<?php
// JSON endpoint used by the court booking date picker.
require_once __DIR__ . '/../includes/functions.php';

$courtId = (int)($_GET['court_id'] ?? 0);
$date    = $_GET['date'] ?? '';
if ($courtId < 1 || !valid_date($date)) { json_error('Invalid court or date.', 400); }
json_out(['slots' => court_slots($courtId, $date)]);
