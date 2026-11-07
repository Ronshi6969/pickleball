<div class="container booking-layout">
  <section>
    <?= media($court['photo'], $court['venue_name']) ?>
    <h1 style="margin-top:1rem;font-size:2.6rem"><?= e($court['name']) ?></h1>
    <p><strong><?= e($court['venue_name']) ?></strong><br><?= e($court['address']) ?>, <?= e($court['city']) ?></p>
    <p><span class="badge"><?= e($court['sport']) ?></span>
       <span class="price"><?= e(money($court['hourly_rate'])) ?></span><span class="small muted"> per hour</span></p>
    <?php if ($court['description']): ?><p><?= e($court['description']) ?></p><?php endif; ?>
    <p class="small muted">Hours: <?= e(hour_label((int)$court['opening_hour'])) ?> to <?= e($closeText) ?>. Book one continuous block of hours.</p>
  </section>

  <section class="form-card" id="booking-app"
           data-court="<?= (int)$court['id'] ?>" data-rate="<?= e($court['hourly_rate']) ?>"
           data-api="<?= e(url('api/availability.php')) ?>" data-canbook="<?= $canBook ? '1' : '0' ?>">
    <h2 style="font-size:1.8rem">Pick a date and time</h2>
    <div class="field">
      <label for="book-date">Date</label>
      <input type="date" id="book-date" value="<?= e($date) ?>" min="<?= e($today) ?>">
    </div>
    <p id="slot-status" class="small muted" aria-live="polite"></p>
    <div id="slot-grid" class="slot-grid"></div>

    <form method="post" action="<?= e(url('book.php')) ?>" id="book-form">
      <?= csrf_field() ?>
      <input type="hidden" name="court_id" value="<?= (int)$court['id'] ?>">
      <input type="hidden" name="date" id="f-date">
      <input type="hidden" name="start_hour" id="f-start">
      <input type="hidden" name="end_hour" id="f-end">
      <div class="summary">
        <dl>
          <dt>Court</dt><dd><?= e($court['name']) ?></dd>
          <dt>Date</dt><dd id="sum-date">-</dd>
          <dt>Time</dt><dd id="sum-time">-</dd>
          <dt>Hours</dt><dd id="sum-hours">0</dd>
          <dt>Total</dt><dd id="sum-total">₱0.00</dd>
        </dl>
        <?php if ($canBook): ?>
          <button class="btn btn-block" type="submit" id="confirm-btn" disabled>Confirm booking</button>
        <?php elseif ($user): ?>
          <p class="small">Venue manager accounts cannot book courts. Log in with a player account.</p>
          <button class="btn btn-block" type="button" id="confirm-btn" disabled>Confirm booking</button>
        <?php else: ?>
          <a class="btn btn-block btn-ink" href="<?= e(url('login.php?next=' . urlencode($_SERVER['REQUEST_URI']))) ?>">Log in to book</a>
          <button type="button" id="confirm-btn" hidden disabled></button>
        <?php endif; ?>
      </div>
    </form>
  </section>
</div>
