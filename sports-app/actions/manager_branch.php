<?php
// Switch active branch for the logged-in manager (persisted in session).
require_once __DIR__ . '/../includes/functions.php';
$user = require_login('manager');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('manager/index.html'); }
csrf_check();

$branchId = (int)($_POST['branch_id'] ?? 0);
$branch = db_one('SELECT id, name, city FROM venues WHERE id = ? AND manager_id = ?', [$branchId, $user['id']]);
if ($branch) {
    $_SESSION['manager_branch_id'] = (int)$branch['id'];
    flash('success', 'Switched to ' . $branch['city'] . ' branch.');
}

$allowed = ['index.html', 'openplays.html', 'courts.html', 'blocks.html', 'bookings.html', 'venue.html'];
$returnTo = basename(trim($_POST['return_to'] ?? 'index.html'));
if (!in_array($returnTo, $allowed, true)) { $returnTo = 'index.html'; }
redirect('manager/' . $returnTo);

