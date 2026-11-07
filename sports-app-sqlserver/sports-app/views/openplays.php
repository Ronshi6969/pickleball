<div class="container band">
  <h1>Open play</h1>
  <form class="filters" method="get">
    <div><label for="date">Date</label><input type="date" id="date" name="date" value="<?= e($date) ?>" min="<?= e($today) ?>"></div>
    <div><label for="venue">Venue</label>
      <select id="venue" name="venue"><option value="0">All venues</option>
        <?php foreach ($venues as $v): ?><option value="<?= (int)$v['id'] ?>" <?= $venue === (int)$v['id'] ? 'selected' : '' ?>><?= e($v['name']) ?></option><?php endforeach; ?>
      </select></div>
    <div><label for="sport">Sport</label>
      <select id="sport" name="sport"><option value="0">All sports</option>
        <?php foreach ($sports as $s): ?><option value="<?= (int)$s['id'] ?>" <?= $sport === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?>
      </select></div>
    <div class="actions"><button class="btn" type="submit">Filter</button>
      <?php if ($date !== '' || $venue || $sport): ?><a class="btn btn-ghost" href="<?= e(url('openplays.php')) ?>">Clear</a><?php endif; ?></div>
  </form>

  <?php if (!$byDate): ?>
    <div class="empty"><h3>No open plays found</h3><p>Try another date or clear the filters.</p></div>
  <?php endif; ?>

  <?php foreach ($byDate as $d => $list): ?>
    <section class="date-group">
      <h3><?= e(date_label($d)) ?></h3>
      <?php foreach ($list as $p): ?>
        <article class="play-card" id="play-<?= (int)$p['id'] ?>">
          <div>
            <h3 style="margin-bottom:.3rem"><?= e($p['title']) ?></h3>
            <div><span class="badge"><?= e($p['sport']) ?></span>
                 <span class="badge"><?= e($p['skill_level'] === 'all' ? 'All levels' : ucfirst($p['skill_level'])) ?></span>
                 <span class="badge"><?= (int)$p['courts_used'] ?> court<?= $p['courts_used'] > 1 ? 's' : '' ?></span></div>
            <p style="margin:.5rem 0 0"><strong><?= e($p['venue_name']) ?></strong>, <?= e($p['city']) ?><br>
               <?= e(time_label($p['start_time'])) ?> to <?= e(time_label($p['end_time'])) ?></p>
            <?php if ($p['description']): ?><p class="small muted" style="margin:.4rem 0 0"><?= e($p['description']) ?></p><?php endif; ?>
            <details class="roster">
              <summary>Who's playing (<?= count($p['roster']) ?>)</summary>
              <?php if ($p['roster']): ?>
                <ul><?php foreach ($p['roster'] as $r): ?>
                  <li><?= e($r['name']) ?><?= (int)$r['slots'] > 1 ? ' (' . (int)$r['slots'] . ' slots)' : '' ?></li>
                <?php endforeach; ?></ul>
              <?php else: ?><p class="small muted">No one has joined yet. Be the first.</p><?php endif; ?>
            </details>
          </div>
          <div>
            <div class="price"><?= e(money($p['price'])) ?> <span class="small muted">per slot</span></div>
            <div class="slot-meter" aria-hidden="true"><span style="width:<?= (int)$p['pct'] ?>%"></span></div>
            <p class="small"><strong><?= (int)$p['left'] ?></strong> of <?= (int)$p['max_slots'] ?> slots left</p>

            <?php if ($p['started']): ?>
              <p class="small muted">This session has started.</p>
            <?php elseif ($user && $user['role'] === 'manager'): ?>
              <p class="small muted">Log in as a player to join.</p>
            <?php elseif (!$user): ?>
              <a class="btn btn-ink" href="<?= e(url('login.php?next=' . urlencode($_SERVER['REQUEST_URI']))) ?>">Log in to join</a>
            <?php elseif ($p['myslots'] > 0): ?>
              <p><span class="badge badge-ok">You're in with <?= (int)$p['myslots'] ?> slot<?= $p['myslots'] > 1 ? 's' : '' ?></span></p>
              <form method="post" action="<?= e(url('openplay_action.php')) ?>" class="inline-form" data-confirm="Leave this open play?">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="leave">
                <input type="hidden" name="open_play_id" value="<?= (int)$p['id'] ?>">
                <button class="btn btn-ghost btn-sm" type="submit">Leave game</button>
              </form>
            <?php elseif ($p['left'] < 1): ?>
              <span class="badge badge-bad">Full</span>
            <?php else: ?>
              <form method="post" action="<?= e(url('openplay_action.php')) ?>" class="join-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="join">
                <input type="hidden" name="open_play_id" value="<?= (int)$p['id'] ?>">
                <div class="field"><label for="slots-<?= (int)$p['id'] ?>">Slots</label>
                  <select id="slots-<?= (int)$p['id'] ?>" name="slots">
                    <?php for ($i = 1; $i <= min($p['left'], 10); $i++): ?><option value="<?= $i ?>"><?= $i ?></option><?php endfor; ?>
                  </select></div>
                <button class="btn" type="submit">Join game</button>
              </form>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </section>
  <?php endforeach; ?>
</div>
