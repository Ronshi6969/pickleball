<?php
// Form handler for manager/venue.html: venue details + photo upload.
require_once __DIR__ . '/../includes/functions.php';
$user   = require_login('manager');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('manager/venue.html'); }
csrf_check();

$action = $_POST['action'] ?? 'update';
if ($action === 'delete_photo') {
    $venue = manager_venue((int)$user['id']);
    $oldPhoto = $venue['photo'] ?? null;
    db_run('UPDATE venues SET photo = NULL WHERE id = ?', [$venue['id']]);
    if ($oldPhoto && str_starts_with($oldPhoto, 'uploads/venues/')) {
        $path = realpath(__DIR__ . '/../' . $oldPhoto);
        $dir  = realpath(__DIR__ . '/../uploads/venues');
        if ($path && $dir && str_starts_with($path, $dir) && is_file($path)) { @unlink($path); }
    }
    flash('success', 'Branch photo deleted.');
    redirect('manager/venue.html');
}

$name = trim($_POST['name'] ?? ''); $address = trim($_POST['address'] ?? ''); $city = trim($_POST['city'] ?? '');
$desc = trim($_POST['description'] ?? ''); $open = (int)($_POST['open'] ?? 0); $close = (int)($_POST['close'] ?? 0);
$errors = [];
if ($name === '' || $address === '' || $city === '') { $errors[] = 'Branch name, address and location are required.'; }
if ($open < 0 || $open > 23 || $close < 1 || $close > 24 || $close <= $open) { $errors[] = 'Closing time must be later than opening time.'; }
$photo = null;
if (!$errors) {
    try { $photo = upload_image($_FILES['photo'] ?? ['error' => UPLOAD_ERR_NO_FILE], 'venues'); }
    catch (RuntimeException $ex) { $errors[] = $ex->getMessage(); }
}
if (!$errors) {
    if ($action === 'create') {
        $newId = db_insert(
            'INSERT INTO venues (manager_id, name, address, city, description, photo, opening_hour, closing_hour) OUTPUT INSERTED.id VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$user['id'], $name, $address, $city, $desc, $photo, $open, $close]
        );
        $_SESSION['manager_branch_id'] = $newId;
        flash('success', 'New branch created (' . $city . '). Add courts for this branch next.');
        redirect('manager/courts.html');
    }
    $venue = manager_venue((int)$user['id']);
    $oldPhoto = $venue['photo'] ?? null;
    $sql = 'UPDATE venues SET name=?, address=?, city=?, description=?, opening_hour=?, closing_hour=?';
    $params = [$name, $address, $city, $desc, $open, $close];
    if ($photo !== null) { $sql .= ', photo=?'; $params[] = $photo; }   // keep the old photo when no new file was chosen
    $sql .= ' WHERE id=?';
    $params[] = $venue['id'];
    db_run($sql, $params);
    if ($photo !== null && $oldPhoto && str_starts_with($oldPhoto, 'uploads/venues/')) {
        $path = realpath(__DIR__ . '/../' . $oldPhoto);
        $dir  = realpath(__DIR__ . '/../uploads/venues');
        if ($path && $dir && str_starts_with($path, $dir) && is_file($path)) { @unlink($path); }
    }
    flash('success', 'Branch details saved.');
    redirect('manager/venue.html');
}

foreach ($errors as $er) { flash('error', $er); }
old_input(['name' => $name, 'address' => $address, 'city' => $city, 'description' => $desc, 'open' => $open, 'close' => $close]);
redirect('manager/venue.html');
