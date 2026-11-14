<?php
// Form handler for profile.html: name, skill level, avatar photo (Pickleball only).
require_once __DIR__ . '/../includes/functions.php';
$user = require_login('player');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('profile.html'); }
csrf_check();

$uid = (int)$user['id'];
$pickleId = pickleball_sport_id();

$name   = trim($_POST['name'] ?? '');
$skill  = $_POST['skill'] ?? '';
$errors = [];
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
        db_run('DELETE FROM user_sports WHERE user_id = ?', [$uid]);
        db_run('INSERT INTO user_sports (user_id, sport_id) VALUES (?, ?)', [$uid, $pickleId]);
        $pdo->commit();
        flash('success', 'Profile saved.');
        redirect('dashboard.html');
    } catch (PDOException $ex) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        $errors[] = 'Could not save your profile. Please try again.';
    }
}

foreach ($errors as $er) { flash('error', $er); }
old_input(['name' => $name, 'skill' => $skill]);
redirect('profile.html');
