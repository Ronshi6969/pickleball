<?php
// Courts of this manager's venue (manager/courts.html). ?edit=ID also returns that court for the form.
require_once __DIR__ . '/../../includes/functions.php';
$user  = api_require('manager');
$venue = manager_venue((int)$user['id']);
$vid   = (int)$venue['id'];

$edit = isset($_GET['edit']) ? db_one('SELECT * FROM courts WHERE id = ? AND venue_id = ?', [(int)$_GET['edit'], $vid]) : null;
json_out([
    'sports' => db_all('SELECT id, name FROM sports ORDER BY name'),
    'edit'   => $edit,
    'courts' => db_all('SELECT c.*, s.name AS sport FROM courts c JOIN sports s ON s.id = c.sport_id WHERE c.venue_id = ? ORDER BY c.name', [$vid]),
]);
