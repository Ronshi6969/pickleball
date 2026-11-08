<?php
// Data for profile.html: the sports list and which ones this player picked
require_once __DIR__ . '/../includes/functions.php';
$user = api_require('player');
json_out([
    'sports' => db_all('SELECT id, name FROM sports ORDER BY name'),
    'mine'   => array_map('intval', db_col('SELECT sport_id FROM user_sports WHERE user_id = ?', [(int)$user['id']])),
    'skills' => SKILLS,
]);
