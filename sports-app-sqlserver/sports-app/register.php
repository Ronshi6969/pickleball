<?php
require_once __DIR__ . '/includes/functions.php';
if (current_user()) { redirect('dashboard.php'); }

$sports = db_all('SELECT id, name FROM sports ORDER BY name');
$validSportIds = array_map('intval', array_column($sports, 'id'));
$errors = [];
$old = ['name' => '', 'email' => '', 'skill' => 'beginner', 'sports' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $old['name']   = trim($_POST['name'] ?? '');
    $old['email']  = trim($_POST['email'] ?? '');
    $old['skill']  = $_POST['skill'] ?? 'beginner';
    $old['sports'] = array_map('intval', $_POST['sports'] ?? []);
    $pass  = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';

    if ($old['name'] === '' || mb_strlen($old['name']) > 100) { $errors[] = 'Enter your name (max 100 characters).'; }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL))     { $errors[] = 'Enter a valid email address.'; }
    if (strlen($pass) < 8)                                      { $errors[] = 'Password must be at least 8 characters.'; }
    if ($pass !== $pass2)                                       { $errors[] = 'Passwords do not match.'; }
    if (!in_array($old['skill'], SKILLS, true))                 { $errors[] = 'Choose a valid skill level.'; }

    if (!$errors && db_val('SELECT 1 FROM users WHERE email = ?', [$old['email']])) {
        $errors[] = 'That email is already registered. Try logging in.';
    }

    if (!$errors) {
        $pdo = db();
        try {
            $pdo->beginTransaction();   // user + chosen sports are saved together or not at all
            $uid = db_insert("INSERT INTO users (name, email, password_hash, role, skill_level)
                              OUTPUT INSERTED.id VALUES (?, ?, ?, 'player', ?)",
                             [$old['name'], $old['email'], password_hash($pass, PASSWORD_DEFAULT), $old['skill']]);
            foreach (array_unique(array_intersect($old['sports'], $validSportIds)) as $sid) {
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
            flash('success', 'Welcome to Play Together, ' . $old['name'] . '!');
            redirect('dashboard.php');
        }
    }
}

render('register', ['pageTitle' => 'Join as a player', 'errors' => $errors, 'old' => $old, 'sports' => $sports]);
