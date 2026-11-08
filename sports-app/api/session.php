<?php
// Tells the .html page who is logged in, gives the CSRF token for its forms,
// and hands over flash messages / remembered form input (each shown only once).
require_once __DIR__ . '/../includes/functions.php';
json_out([
    'user'    => current_user(),
    'csrf'    => csrf_token(),
    'flashes' => pull_flashes(),
    'old'     => pull_old(),
    'today'   => date('Y-m-d'),
]);
