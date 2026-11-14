<?php
// Form handler for register.html: creates a pickleball player account.
require_once __DIR__ . '/../includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('register.html'); }
csrf_check();

$pickleId = pickleball_sport_id();

$name   = trim($_POST['name'] ?? '');
$email  = trim($_POST['email'] ?? '');
$skill  = $_POST['skill'] ?? 'beginner';
$pass   = $_POST['password'] ?? '';
$pass2  = $_POST['password2'] ?? '';
$errors = [];

if ($name === '' || mb_strlen($name) > 100)  { $errors[] = 'Enter your name (max 100 characters).'; }
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Enter a valid email address.'; }
if (strlen($pass) < 8)                        { $errors[] = 'Password must be at least 8 characters.'; }
if ($pass !== $pass2)                         { $errors[] = 'Passwords do not match.'; }
if (!in_array($skill, SKILLS, true))          { $errors[] = 'Choose a valid skill level.'; }
if (!$errors && db_val('SELECT 1 FROM users WHERE email = ?', [$email])) {
    $errors[] = 'That email is already registered. Try logging in.';
}

if (!$errors) {
    try {
        db_begin();   // user + pickleball sport preference saved together or not at all
        $uid = db_insert("INSERT INTO users (name, email, password_hash, role, skill_level)
                          OUTPUT INSERTED.id VALUES (?, ?, ?, 'player', ?)",
                         [$name, $email, password_hash($pass, PASSWORD_DEFAULT), $skill]);
        db_run('INSERT INTO user_sports (user_id, sport_id) VALUES (?, ?)', [$uid, $pickleId]);
        db_commit();
    } catch (SqlsrvException $ex) {
        db_rollback();
        $errors[] = is_duplicate_key($ex) ? 'That email is already registered. Try logging in.' : 'Could not create your account. Please try again.';
    }
    if (!$errors) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $uid;
        flash('success', 'Welcome to Play Together, ' . $name . '!');
        redirect('dashboard.html');
    }
}

foreach ($errors as $er) { flash('error', $er); }
old_input(['name' => $name, 'email' => $email, 'skill' => $skill]);
redirect('register.html');
