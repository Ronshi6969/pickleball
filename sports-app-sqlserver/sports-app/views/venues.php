<div class="container band">
  <h1>Court rental</h1>
  <form class="filters" method="get">
    <div><label for="q">Court or venue name</label><input type="text" id="q" name="q" value="<?= e($q) ?>" placeholder="e.g. ALAB"></div>
    <div><label for="location">Location</label><input type="text" id="location" name="location" value="<?= e($loc) ?>" placeholder="e.g. Imus"></div>
    <div><label for="sport">Sport</label>
      <select id="sport" name="sport"><option value="0">All sports</option>
        <?php foreach ($sports as $s): ?><option value="<?= (int)$s['id'] ?>" <?= $sport === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?>
      </select></div>
    <div class="actions"><button class="btn" type="submit">Search</button>
      <?php if ($q !== '' || $loc !== '' || $sport): ?><a class="btn btn-ghost" href="<?= e(url('venues.php')) ?>">Clear</a><?php endif; ?></div>
  </form>

  <?php if (!$courts): ?>
    <div class="empty"><h3>No courts match your search</h3><p>Try a shorter name or clear the filters.</p></div>
  <?php else: ?>
    <p class="muted"><?= count($courts) ?> court<?= count($courts) === 1 ? '' : 's' ?> found</p>
    <div class="grid">
      <?php foreach ($courts as $c): ?>
        <article class="card">
          <?= media($c['photo'], $c['venue_name']) ?>
          <div class="card-body">
            <h3><?= e($c['name']) ?></h3>
            <div><strong><?= e($c['venue_name']) ?></strong></div>
            <div class="small"><?= e($c['address']) ?>, <?= e($c['city']) ?></div>
            <div class="small">Open <?= e(hour_label((int)$c['opening_hour'])) ?> to <?= e((int)$c['closing_hour'] === 24 ? '12:00 AM' : hour_label((int)$c['closing_hour'])) ?></div>
            <div><span class="badge"><?= e($c['sport']) ?></span> <span class="price"><?= e(money($c['hourly_rate'])) ?></span><span class="small muted"> per hour</span></div>
            <a class="btn btn-ink" href="<?= e(url('court.php?id=' . $c['id'])) ?>">Check times</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
