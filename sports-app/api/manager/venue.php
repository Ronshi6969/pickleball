<?php
// Venue details for the form (manager/venue.html)
require_once __DIR__ . '/../../includes/functions.php';
$user = api_require('manager');
json_out(['venue' => manager_venue((int)$user['id'])]);
