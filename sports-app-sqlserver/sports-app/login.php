<?php
require_once __DIR__ . '/includes/functions.php';
if (current_user()) { redirect('dashboard.php'); }

$errors = [];
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $u = db_one('SELECT id, password_hash, role FROM users WHERE email = ?', [$email]);
    if ($u && password_verify($pass, $u['password_hash'])) {
        session_regenerate_id(true);               // blocks session fixation
        $_SESSION['user_id'] = (int)$u['id'];
        $next = $_GET['next'] ?? '';
        if ($next !== '' && str_starts_with($next, BASE_URL . '/')) {   // only same-site paths
            header('Location: ' . $next);
            exit;
        }
        redirect($u['role'] === 'manager' ? 'manager/index.php' : 'dashboard.php');
    }
    $errors[] = 'Email or password is incorrect.';
}

render('login', ['pageTitle' => 'Log in', 'errors' => $errors, 'email' => $email]);
