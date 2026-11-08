<?php
// Sports list for the register / profile forms.
require_once __DIR__ . '/../includes/functions.php';
json_out(['sports' => db_all('SELECT id, name FROM sports ORDER BY name')]);
