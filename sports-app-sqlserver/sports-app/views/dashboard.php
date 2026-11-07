<section class="band-sky">
  <div class="container dash-hero">
    <?= media($user['avatar'], $user['name'], 'avatar') ?>
    <div>
      <h1>Hello, <?= e(strtok($user['name'], ' ')) ?>!</h1>
      <p><?= e($user['name']) ?> &middot; <?= e(ucfirst($user['skill_level'])) ?><?= $mySports ? ' &middot; ' . e(implode(', ', $mySports)) : '' ?></p>
      <div class="actions">
        <a class="btn btn-ink" href="<?= e(url('profile.php')) ?>">Edit profile</a>
        <a class="btn btn-ghost" href="<?= e(url('openplays.php')) ?>">Find a game</a>
        <a class="btn btn-ghost" href="<?= e(url('venues.php')) ?>">Rent a court</a>
      </div>
    </div>
  </div>
</section>

<div class="container band">
  <div class="tabs">
    <a href="?tab=upcoming" class="<?= $tab === 'upcoming' ? 'on' : '' ?>">Upcoming</a>
    <a href="?tab=history" class="<?= $tab === 'history' ? 'on' : '' ?>">Past activity</a>
  </div>

  <h2><?= $tab === 'upcoming' ? 'Reserved courts' : 'Past court rentals' ?></h2>
  <?php if (!$bookings): ?>
    <div class="empty"><?= $tab === 'upcoming' ? 'No court bookings yet. <a href="' . e(url('venues.php')) . '">Browse courts</a>.' : 'No past court rentals yet.' ?></div>
  <?php endif; ?>
  <?php foreach ($bookings as $b): ?>
    <div class="list-item">
      <div>
        <h3><?= e($b['court_name']) ?> <span class="small muted">at <?= e($b['venue_name']) ?></span></h3>
        <div><strong><?= e(date_label($b['booking_date'])) ?></strong> &middot; <?= e($b['time_text']) ?></div>
        <div class="small muted">Ref <?= e($b['reference']) ?> &middot; <?= e(money($b['total_price'])) ?></div>
      </div>
      <div class="actions">
        <?php if ($b['status'] === 'cancelled'): ?><span class="badge badge-bad">Cancelled</span><?php endif; ?>
        <a class="btn btn-sm btn-ghost" href="<?= e(url('receipt.php?type=booking&id=' . $b['id'])) ?>">Receipt</a>
        <?php if ($b['cancellable']): ?>
          <form method="post" action="<?= e(url('cancel_booking.php')) ?>" class="inline-form" data-confirm="Cancel this booking?">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
            <button class="btn btn-sm btn-danger" type="submit">Cancel</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>

  <h2 style="margin-top:2rem"><?= $tab === 'upcoming' ? 'Joined open plays' : 'Past open plays' ?></h2>
  <?php if (!$plays): ?>
    <div class="empty"><?= $tab === 'upcoming' ? 'You have not joined any open play. <a href="' . e(url('openplays.php')) . '">See upcoming games</a>.' : 'No past open plays yet.' ?></div>
  <?php endif; ?>
  <?php foreach ($plays as $p): ?>
    <div class="list-item">
      <div>
        <h3><?= e($p['title']) ?> <span class="small muted">at <?= e($p['venue_name']) ?></span></h3>
        <div><strong><?= e(date_label($p['play_date'])) ?></strong> &middot; <?= e(time_label($p['start_time'])) ?> to <?= e(time_label($p['end_time'])) ?></div>
        <div class="small muted"><?= (int)$p['slots'] ?> slot<?= $p['slots'] > 1 ? 's' : '' ?> &middot; <?= e(money($p['amount'])) ?></div>
      </div>
      <div class="actions">
        <?php if ($p['status'] === 'cancelled'): ?><span class="badge badge-bad">Left</span><?php endif; ?>
        <a class="btn btn-sm btn-ghost" href="<?= e(url('receipt.php?type=openplay&id=' . $p['reg_id'])) ?>">Receipt</a>
        <?php if ($tab === 'upcoming'): ?><a class="btn btn-sm btn-ghost" href="<?= e(url('openplays.php?date=' . $p['play_date'] . '#play-' . $p['play_id'])) ?>">View</a><?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
