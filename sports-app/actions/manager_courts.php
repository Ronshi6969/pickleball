<?php
// Form handler for manager/courts.html: add / edit / hide-show / delete a court (own venue only).
require_once __DIR__ . '/../includes/functions.php';
$user  = require_login('manager');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('manager/courts.html'); }
csrf_check();
$venue = manager_venue((int)$user['id']);
$vid   = (int)$venue['id'];

// Fetch a court only if it belongs to THIS manager's venue
function own_court(int $id, int $vid): ?array
{
    return db_one('SELECT * FROM courts WHERE id = ? AND venue_id = ?', [$id, $vid]);
}

$action = $_POST['action'] ?? '';
$id = (int)($_POST['id'] ?? 0);

if ($action === 'save') {
    $name = trim($_POST['name'] ?? ''); $sport = pickleball_sport_id(); $rate = (float)($_POST['hourly_rate'] ?? 0);
    $errors = [];
    if ($name === '' || mb_strlen($name) > 80)           { $errors[] = 'Enter a court name (max 80 characters).'; }
    if ($rate <= 0 || $rate > 100000)                    { $errors[] = 'Hourly rate must be more than 0.'; }
    if ($id && !own_court($id, $vid))                    { $errors[] = 'Court not found.'; }
    if (!$errors) {
        if ($id) {
            db_run('UPDATE courts SET name=?, sport_id=?, hourly_rate=? WHERE id=? AND venue_id=?', [$name, $sport, $rate, $id, $vid]);
            flash('success', 'Court updated.');
        } else {
            db_run('INSERT INTO courts (venue_id, sport_id, name, hourly_rate) VALUES (?, ?, ?, ?)', [$vid, $sport, $name, $rate]);
            flash('success', 'Court added.');
        }
        redirect('manager/courts.html');
    }
    foreach ($errors as $er) { flash('error', $er); }
    old_input(['name' => $name, 'hourly_rate' => $_POST['hourly_rate'] ?? '']);
    redirect('manager/courts.html' . ($id ? '?edit=' . $id : ''));
} elseif ($action === 'toggle' && own_court($id, $vid)) {
    db_run('UPDATE courts SET is_active = 1 - is_active WHERE id = ? AND venue_id = ?', [$id, $vid]);
    flash('success', 'Court visibility changed.');
} elseif ($action === 'delete' && own_court($id, $vid)) {
    try {
        db_run('DELETE FROM courts WHERE id = ? AND venue_id = ?', [$id, $vid]);
        flash('success', 'Court deleted.');
    } catch (PDOException $ex) {   // foreign key: the court already has bookings
        flash('error', 'This court has booking history, so it cannot be deleted. Turn it off instead.');
    }
}
redirect('manager/courts.html');
