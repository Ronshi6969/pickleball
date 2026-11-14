<?php
// Form handler for venue_register.html: creates a manager account + its venue in one transaction.
require_once __DIR__ . '/../includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('venue_register.html'); }
csrf_check();

$old = [
    'name'    => trim($_POST['name'] ?? ''),
    'email'   => trim($_POST['email'] ?? ''),
    'venue'   => trim($_POST['venue'] ?? ''),
    'address' => trim($_POST['address'] ?? ''),
    'city'    => trim($_POST['city'] ?? ''),
    'open'    => (int)($_POST['open'] ?? 6),
    'close'   => (int)($_POST['close'] ?? 22),
];
$pass = $_POST['password'] ?? '';
$errors = [];

if ($old['name'] === '')                               { $errors[] = 'Enter the manager name.'; }
if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) { $errors[] = 'Enter a valid email address.'; }
if (strlen($pass) < 8)                                  { $errors[] = 'Password must be at least 8 characters.'; }
if ($old['venue'] === '' || $old['address'] === '' || $old['city'] === '') { $errors[] = 'Venue name, address and city are required.'; }
if ($old['open'] < 0 || $old['open'] > 23 || $old['close'] < 1 || $old['close'] > 24 || $old['close'] <= $old['open']) {
    $errors[] = 'Closing time must be later than opening time.';
}
if (!$errors && db_val('SELECT 1 FROM users WHERE email = ?', [$old['email']])) {
    $errors[] = 'That email is already registered.';
}

if (!$errors) {
    try {
        db_begin();   // user + venue are created together or not at all
        $uid = db_insert("INSERT INTO users (name, email, password_hash, role)
                          OUTPUT INSERTED.id VALUES (?, ?, ?, 'manager')",
                         [$old['name'], $old['email'], password_hash($pass, PASSWORD_DEFAULT)]);
        db_run('INSERT INTO venues (manager_id, name, address, city, opening_hour, closing_hour) VALUES (?, ?, ?, ?, ?, ?)',
               [$uid, $old['venue'], $old['address'], $old['city'], $old['open'], $old['close']]);
        db_commit();
    } catch (SqlsrvException $ex) {
        db_rollback();
        $errors[] = is_duplicate_key($ex) ? 'That email is already registered.' : 'Could not create the venue account. Please try again.';
    }
    if (!$errors) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $uid;
        flash('success', 'Venue created. Add your courts next.');
        redirect('manager/courts.html');
    }
}

foreach ($errors as $er) { flash('error', $er); }
old_input($old);
redirect('venue_register.html');
