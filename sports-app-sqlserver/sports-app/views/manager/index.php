<?php require __DIR__ . '/../layout/manager_nav.php'; ?>
<section class="band band-lime">
  <div class="container dash-hero">
    <?= media($venue['photo'], $venue['name'], 'thumb') ?>
    <div>
      <h1><?= e($venue['name']) ?></h1>
      <p><?= e($venue['address']) ?>, <?= e($venue['city']) ?></p>
      <a class="btn btn-ink" href="<?= e(url('manager/venue.php')) ?>">Change venue photo</a>
    </div>
  </div>
</section>
<div class="container band">
  <div class="stats">
    <div class="stat"><b><?= $stats['courts'] ?></b>Active courts</div>
    <div class="stat"><b><?= $stats['upBook'] ?></b>Upcoming rentals</div>
    <div class="stat"><b><?= $stats['upPlays'] ?></b>Upcoming open plays</div>
    <div class="stat"><b><?= $stats['players'] ?></b>Open play slots taken</div>
  </div>
  <div class="grid">
    <a class="card" href="<?= e(url('manager/openplays.php')) ?>" style="text-decoration:none"><div class="card-body"><h3>Manage open plays</h3><p>Create, edit and delete sessions. See who joined.</p></div></a>
    <a class="card" href="<?= e(url('manager/courts.php')) ?>" style="text-decoration:none"><div class="card-body"><h3>Manage courts</h3><p>Add courts, set hourly rates, switch courts on or off.</p></div></a>
    <a class="card" href="<?= e(url('manager/blocks.php')) ?>" style="text-decoration:none"><div class="card-body"><h3>Block out hours</h3><p>Close courts for maintenance or holidays.</p></div></a>
    <a class="card" href="<?= e(url('manager/bookings.php')) ?>" style="text-decoration:none"><div class="card-body"><h3>Court rentals</h3><p>See and cancel player bookings.</p></div></a>
  </div>
</div>
