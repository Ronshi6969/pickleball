<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_login('player');
$uid  = (int)$user['id'];

$sports = db_all('SELECT id, name FROM sports ORDER BY name');
$validSportIds = array_map('intval', array_column($sports, 'id'));
$mine = array_map('intval', db_col('SELECT sport_id FROM user_sports WHERE user_id = ?', [$uid]));
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name   = trim($_POST['name'] ?? '');
    $skill  = $_POST['skill'] ?? '';
    $picked = array_map('intval', $_POST['sports'] ?? []);
    if ($name === '' || mb_strlen($name) > 100) { $errors[] = 'Enter your name (max 100 characters).'; }
    if (!in_array($skill, SKILLS, true))        { $errors[] = 'Choose a valid skill level.'; }

    $avatar = null;
    if (!$errors) {
        try { $avatar = upload_image($_FILES['avatar'] ?? ['error' => UPLOAD_ERR_NO_FILE], 'avatars'); }
        catch (RuntimeException $ex) { $errors[] = $ex->getMessage(); }
    }
    if (!$errors) {
        $pdo = db();
        try {
            $pdo->beginTransaction();
            if ($avatar !== null) {
                db_run('UPDATE users SET name = ?, skill_level = ?, avatar = ? WHERE id = ?', [$name, $skill, $avatar, $uid]);
            } else {
                db_run('UPDATE users SET name = ?, skill_level = ? WHERE id = ?', [$name, $skill, $uid]);
            }
            db_run('DELETE FROM user_sports WHERE user_id = ?', [$uid]);   // replace the whole set
            foreach (array_unique(array_intersect($picked, $validSportIds)) as $sid) {
                db_run('INSERT INTO user_sports (user_id, sport_id) VALUES (?, ?)', [$uid, $sid]);
            }
            $pdo->commit();
            flash('success', 'Profile saved.');
            redirect('dashboard.php');
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $errors[] = 'Could not save your profile. Please try again.';
        }
    }
    $user['name'] = $name; $user['skill_level'] = $skill; $mine = $picked;
}

render('profile', [
    'pageTitle' => 'Edit profile',
    'navActive' => 'profile',
    'user'      => $user,
    'sports'    => $sports,
    'mine'      => $mine,
    'errors'    => $errors,
]);
