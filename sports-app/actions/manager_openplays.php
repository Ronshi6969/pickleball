<?php
// Form handler for manager/openplays.html: create / edit / delete an open play (own venue only).
require_once __DIR__ . '/../includes/functions.php';
$user  = require_login('manager');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('manager/openplays.html'); }
csrf_check();
$venue = manager_venue((int)$user['id']);
$vid   = (int)$venue['id'];

// Fetch an open play only if it belongs to THIS manager's venue
function own_play(int $id, int $vid): ?array
{
    return db_one('SELECT * FROM open_plays WHERE id = ? AND venue_id = ?', [$id, $vid]);
}
function joined_slots(int $playId): int
{
    return (int)db_val("SELECT COALESCE(SUM(slots), 0) FROM open_play_registrations WHERE open_play_id = ? AND status = 'joined'", [$playId]);
}

$action = $_POST['action'] ?? '';
$id = (int)($_POST['id'] ?? 0);

if ($action === 'save') {
    $form = ['title' => '', 'description' => '', 'play_date' => '', 'start_time' => '', 'end_time' => '',
             'sport_id' => 0, 'skill_level' => 'all', 'courts_used' => 1, 'max_slots' => 20, 'price' => ''];
    foreach (array_keys($form) as $k) { $form[$k] = trim($_POST[$k] ?? $form[$k]); }
    $sportOk  = db_val('SELECT 1 FROM sports WHERE id = ?', [(int)$form['sport_id']]);
    $existing = $id ? own_play($id, $vid) : null;
    $errors = [];

    if ($form['title'] === '' || mb_strlen($form['title']) > 120)             { $errors[] = 'Enter a title (max 120 characters).'; }
    if (!valid_date($form['play_date']))                                       { $errors[] = 'Choose a valid date.'; }
    elseif (!$id && $form['play_date'] < date('Y-m-d'))                        { $errors[] = 'The date cannot be in the past.'; }
    if (!preg_match('/^\d{2}:\d{2}$/', $form['start_time']) || !preg_match('/^\d{2}:\d{2}$/', $form['end_time']) || $form['end_time'] <= $form['start_time']) {
        $errors[] = 'End time must be later than start time.';
    }
    if (!$sportOk)                                                             { $errors[] = 'Choose a sport.'; }
    if (!in_array($form['skill_level'], ['all', 'beginner', 'intermediate', 'advanced'], true)) { $errors[] = 'Choose a skill level.'; }
    if ((int)$form['courts_used'] < 1 || (int)$form['courts_used'] > 50)       { $errors[] = 'Courts used must be 1 or more.'; }
    if ((int)$form['max_slots'] < 1 || (int)$form['max_slots'] > 500)          { $errors[] = 'Total slots must be between 1 and 500.'; }
    if ($form['price'] === '' || (float)$form['price'] < 0)                    { $errors[] = 'Enter the price per slot (0 for free).'; }
    if ($id && !$existing)                                                      { $errors[] = 'Open play not found.'; }
    if ($existing && (int)$form['max_slots'] < joined_slots($id)) {
        $errors[] = 'Total slots cannot be lower than the ' . joined_slots($id) . ' slots already taken.';
    }

    if (!$errors) {
        $vals = [$form['title'], $form['description'] ?: null, $form['play_date'], $form['start_time'], $form['end_time'],
                 (int)$form['sport_id'], $form['skill_level'], (int)$form['courts_used'], (int)$form['max_slots'], (float)$form['price']];
        if ($id) {
            db_run('UPDATE open_plays SET title=?, description=?, play_date=?, start_time=?, end_time=?, sport_id=?, skill_level=?, courts_used=?, max_slots=?, price=? WHERE id=? AND venue_id=?',
                   array_merge($vals, [$id, $vid]));
            flash('success', 'Open play updated.');
        } else {
            db_run('INSERT INTO open_plays (title, description, play_date, start_time, end_time, sport_id, skill_level, courts_used, max_slots, price, venue_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
                   array_merge($vals, [$vid]));
            flash('success', 'Open play created.');
        }
        redirect('manager/openplays.html');
    }
    foreach ($errors as $er) { flash('error', $er); }
    old_input($form);
    redirect('manager/openplays.html' . ($id ? '?edit=' . $id : ''));
} elseif ($action === 'delete' && own_play($id, $vid)) {
    db_run('DELETE FROM open_plays WHERE id = ? AND venue_id = ?', [$id, $vid]);   // registrations cascade
    flash('success', 'Open play deleted.');
}
redirect('manager/openplays.html');
