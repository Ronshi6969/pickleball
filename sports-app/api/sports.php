<?php
// Pickleball sport lookup.
require_once __DIR__ . '/../includes/functions.php';
json_out(['sports' => db_all('SELECT id, name FROM sports WHERE id = ?', [pickleball_sport_id()])]);
