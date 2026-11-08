<?php
require_once __DIR__ . '/../includes/functions.php';
$_SESSION = [];
session_destroy();
session_start();
flash('success', 'You are logged out.');
redirect('index.html');
