<section class="container hero">
  <div>
    <h1>Play<br>Together</h1>
    <p style="font-size:1.15rem;max-width:30rem">Book a court by the hour or drop into an open play near you. Find the game, bring your paddle.</p>
    <div class="hero-actions">
      <a class="pill-xl" href="<?= e(url('openplays.php')) ?>">Open play</a>
      <a class="pill-xl" href="<?= e(url('venues.php')) ?>">Court rental</a>
    </div>
  </div>
  <div class="court-art" role="img" aria-label="Illustration of a pickleball court"></div>
</section>

<section class="band band-pink" id="open-plays">
  <div class="container">
    <div class="section-head"><h2>Open plays this week</h2><a class="btn btn-ghost" href="<?= e(url('openplays.php')) ?>">See all open plays</a></div>
    <?php if (!$plays): ?>
      <div class="empty">No open plays are scheduled yet. Check back soon.</div>
    <?php else: ?>
      <div class="grid">
        <?php foreach ($plays as $p): ?>
          <article class="card flat">
            <div class="card-body">
              <h3><?= e($p['title']) ?></h3>
              <div><span class="badge"><?= e($p['sport']) ?></span><span class="badge"><?= e($p['skill_level'] === 'all' ? 'All levels' : ucfirst($p['skill_level'])) ?></span></div>
              <div><?= e($p['venue_name']) ?> &middot; <?= e($p['courts_used']) ?> court<?= $p['courts_used'] > 1 ? 's' : '' ?></div>
              <div><strong><?= e(date_label($p['play_date'])) ?></strong></div>
              <div><?= e(time_label($p['start_time'])) ?> to <?= e(time_label($p['end_time'])) ?></div>
              <div class="price"><?= e(money($p['price'])) ?> <span class="small muted">per slot</span></div>
              <div class="small"><?= (int)$p['slots_left'] > 0 ? (int)$p['slots_left'] . ' slots left' : 'Full' ?></div>
              <a class="btn btn-ink" href="<?= e(url('openplays.php?date=' . $p['play_date'] . '#play-' . $p['id'])) ?>">Join game</a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<section class="band band-lime">
  <div class="container">
    <div class="section-head"><h2>Featured courts</h2><a class="btn btn-ghost" href="<?= e(url('venues.php')) ?>">Browse all courts</a></div>
    <div class="grid">
      <?php foreach ($featured as $c): ?>
        <article class="card">
          <?= media($c['photo'], $c['venue_name']) ?>
          <div class="card-body">
            <h3><?= e($c['name']) ?></h3>
            <div><?= e($c['venue_name']) ?>, <?= e($c['city']) ?></div>
            <div><span class="badge"><?= e($c['sport']) ?></span> <span class="price"><?= e(money($c['hourly_rate'])) ?></span><span class="small muted"> per hour</span></div>
            <a class="btn btn-ink" href="<?= e(url('court.php?id=' . $c['id'])) ?>">Check times</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php if (!$user): ?>
<section class="band band-sky">
  <div class="container center">
    <h2>Ready to play?</h2>
    <p>Make a free player account to book courts and join open plays. Run a venue? List your courts and manage bookings in one place.</p>
    <div class="actions" style="justify-content:center">
      <a class="btn btn-ink" href="<?= e(url('register.php')) ?>">Join as a player</a>
      <a class="btn btn-ghost" href="<?= e(url('venue_register.php')) ?>">List your venue</a>
    </div>
  </div>
</section>
<?php endif; ?>
