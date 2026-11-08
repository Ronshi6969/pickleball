<?php
// Form handler for register.html: creates a player account (user + preferred sports in one transaction).
require_once __DIR__ . '/../includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('register.html'); }
csrf_check();

$sports = db_all('SELECT id FROM sports');
$validSportIds = array_map('intval', array_column($sports, 'id'));

$name   = trim($_POST['name'] ?? '');
$email  = trim($_POST['email'] ?? '');
$skill  = $_POST['skill'] ?? 'beginner';
$picked = array_map('intval', (array)($_POST['sports'] ?? []));
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
    $pdo = db();
    try {
        $pdo->beginTransaction();   // user + chosen sports are saved together or not at all
        $uid = db_insert("INSERT INTO users (name, email, password_hash, role, skill_level)
                          OUTPUT INSERTED.id VALUES (?, ?, ?, 'player', ?)",
                         [$name, $email, password_hash($pass, PASSWORD_DEFAULT), $skill]);
        foreach (array_unique(array_intersect($picked, $validSportIds)) as $sid) {
            db_run('INSERT INTO user_sports (user_id, sport_id) VALUES (?, ?)', [$uid, $sid]);
        }
        $pdo->commit();
    } catch (PDOException $ex) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
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
old_input(['name' => $name, 'email' => $email, 'skill' => $skill, 'sports' => $picked]);
redirect('register.html');
