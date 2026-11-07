<?php
// Create and manage court rentals: add, edit, set rate, turn on/off, delete
require_once __DIR__ . '/../includes/functions.php';
$user  = require_login('manager');
$venue = manager_venue((int)$user['id']);
$vid   = (int)$venue['id'];
$sports = db_all('SELECT id, name FROM sports ORDER BY name');
$errors = [];

// Fetch a court only if it belongs to THIS manager's venue
function own_court(int $id, int $vid): ?array
{
    return db_one('SELECT * FROM courts WHERE id = ? AND venue_id = ?', [$id, $vid]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'save') {
        $name = trim($_POST['name'] ?? ''); $sport = (int)($_POST['sport_id'] ?? 0); $rate = (float)($_POST['hourly_rate'] ?? 0);
        if ($name === '' || mb_strlen($name) > 80)           { $errors[] = 'Enter a court name (max 80 characters).'; }
        if (!db_val('SELECT 1 FROM sports WHERE id = ?', [$sport])) { $errors[] = 'Choose a sport.'; }
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
            redirect('manager/courts.php');
        }
    } elseif ($action === 'toggle' && own_court($id, $vid)) {
        db_run('UPDATE courts SET is_active = 1 - is_active WHERE id = ? AND venue_id = ?', [$id, $vid]);
        flash('success', 'Court visibility changed.');
        redirect('manager/courts.php');
    } elseif ($action === 'delete' && own_court($id, $vid)) {
        try {
            db_run('DELETE FROM courts WHERE id = ? AND venue_id = ?', [$id, $vid]);
            flash('success', 'Court deleted.');
        } catch (PDOException $ex) {   // foreign key: the court already has bookings
            flash('error', 'This court has booking history, so it cannot be deleted. Turn it off instead.');
        }
        redirect('manager/courts.php');
    }
}

$edit = isset($_GET['edit']) ? own_court((int)$_GET['edit'], $vid) : null;
$courts = db_all('SELECT c.*, s.name AS sport FROM courts c JOIN sports s ON s.id = c.sport_id WHERE c.venue_id = ? ORDER BY c.name', [$vid]);

render('manager/courts', [
    'pageTitle' => 'Courts', 'navActive' => 'manager', 'mnav' => 'courts',
    'user' => $user, 'sports' => $sports, 'errors' => $errors, 'edit' => $edit, 'courts' => $courts,
]);
