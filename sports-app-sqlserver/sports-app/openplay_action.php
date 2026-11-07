<?php
// Join / leave an open play. The slot check runs inside a transaction with a row lock,
// so two players can never take the last slot at the same time.
require_once __DIR__ . '/includes/functions.php';
$user = require_login('player');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('openplays.php'); }
csrf_check();

$pdo    = db();
$action = $_POST['action'] ?? '';
$playId = (int)($_POST['open_play_id'] ?? 0);
$back   = 'openplays.php#play-' . $playId;

try {
    $pdo->beginTransaction();

    // WITH (UPDLOCK, ROWLOCK) locks this open play row until commit (SQL Server's "FOR UPDATE")
    $play = db_one('SELECT * FROM open_plays WITH (UPDLOCK, ROWLOCK) WHERE id = ?', [$playId]);
    if (!$play) { throw new RuntimeException('That open play no longer exists.'); }
    if (($play['play_date'] . ' ' . hms($play['start_time'])) <= date('Y-m-d H:i:s')) {
        throw new RuntimeException('This session has already started.');
    }

    $existing = db_one('SELECT id, status FROM open_play_registrations WHERE open_play_id = ? AND user_id = ?', [$playId, $user['id']]);

    if ($action === 'join') {
        $slots = (int)($_POST['slots'] ?? 0);
        if ($slots < 1 || $slots > 10) { throw new RuntimeException('Choose between 1 and 10 slots.'); }
        if ($existing && $existing['status'] === 'joined') { throw new RuntimeException('You already joined this game. Leave first to change your slots.'); }

        $taken = (int)db_val("SELECT COALESCE(SUM(slots), 0) FROM open_play_registrations WHERE open_play_id = ? AND status = 'joined'", [$playId]);
        $left  = (int)$play['max_slots'] - $taken;
        if ($slots > $left) {
            throw new RuntimeException($left < 1 ? 'Sorry, this game just filled up.' : "Only $left slot(s) left. Choose fewer slots.");
        }

        $amount = (float)$play['price'] * $slots;
        if ($existing) {   // re-joining after leaving: reuse the row (unique key on game + player)
            db_run("UPDATE open_play_registrations SET slots = ?, amount = ?, status = 'joined', created_at = ? WHERE id = ?",
                   [$slots, $amount, date('Y-m-d H:i:s'), $existing['id']]);
        } else {
            db_run('INSERT INTO open_play_registrations (open_play_id, user_id, slots, amount) VALUES (?, ?, ?, ?)',
                   [$playId, $user['id'], $slots, $amount]);
        }
        $pdo->commit();
        flash('success', "You're in! $slots slot" . ($slots > 1 ? 's' : '') . ' reserved for ' . $play['title'] . '. Pay at the venue.');

    } elseif ($action === 'leave') {
        if (!$existing || $existing['status'] !== 'joined') { throw new RuntimeException('You are not registered for this game.'); }
        db_run("UPDATE open_play_registrations SET status = 'cancelled' WHERE id = ?", [$existing['id']]);
        $pdo->commit();
        flash('success', 'You left ' . $play['title'] . '. Your slots are open again.');

    } else {
        throw new RuntimeException('Unknown action.');
    }
} catch (RuntimeException $ex) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    flash('error', $ex->getMessage());
} catch (PDOException $ex) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    flash('error', 'Something went wrong. Please try again.');
}
redirect($back);
