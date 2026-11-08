<?php
// Form handler for login.html. Checks the password, then redirects to a page.
require_once __DIR__ . '/../includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('login.html'); }
csrf_check();

$email = trim($_POST['email'] ?? '');
$pass  = $_POST['password'] ?? '';
$next  = $_GET['next'] ?? '';

$u = db_one('SELECT id, password_hash, role FROM users WHERE email = ?', [$email]);
if ($u && password_verify($pass, $u['password_hash'])) {
    session_regenerate_id(true);               // blocks session fixation
    $_SESSION['user_id'] = (int)$u['id'];
    // only same-site paths are allowed as "next" (no open redirect)
    if ($next !== '' && str_starts_with($next, BASE_URL . '/') && !str_contains($next, "\n") && !str_contains($next, "\r")) {
        header('Location: ' . $next);
        exit;
    }
    redirect($u['role'] === 'manager' ? 'manager/index.html' : 'dashboard.html');
}

flash('error', 'Email or password is incorrect.');
old_input(['email' => $email]);
redirect('login.html' . ($next !== '' ? '?next=' . urlencode($next) : ''));
