<?php
// Form handler for login.html. Checks password + selected account type (player / manager), then redirects.
require_once __DIR__ . '/../includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('login.html'); }
csrf_check();

$email     = trim($_POST['email'] ?? '');
$pass      = $_POST['password'] ?? '';
$loginRole = $_POST['login_role'] ?? '';
$next      = $_GET['next'] ?? '';

$u = db_one('SELECT id, password_hash, role FROM users WHERE email = ?', [$email]);
if ($u && password_verify($pass, $u['password_hash'])) {
    if (in_array($loginRole, ['player', 'manager'], true) && $u['role'] !== $loginRole) {
        $label = $u['role'] === 'manager' ? 'Admin' : 'Player';
        flash('error', "This account is registered as {$label}. Please choose {$label} above to log in.");
        old_input(['email' => $email, 'login_role' => $loginRole]);
        redirect('login.html' . ($next !== '' ? '?next=' . urlencode($next) : ''));
    }

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
old_input(['email' => $email, 'login_role' => in_array($loginRole, ['player', 'manager'], true) ? $loginRole : 'player']);
redirect('login.html' . ($next !== '' ? '?next=' . urlencode($next) : ''));
