<div class="container">
  <div class="form-card form-narrow">
    <h1 style="font-size:2.4rem">You're booked!</h1>
    <p>Show this reference at the venue: <strong><?= e($b['reference']) ?></strong></p>
    <div class="summary">
      <dl>
        <dt>Court</dt><dd><?= e($b['court_name']) ?></dd>
        <dt>Venue</dt><dd><?= e($b['venue_name']) ?></dd>
        <dt>Date</dt><dd><?= e(date_label($b['booking_date'])) ?></dd>
        <dt>Time</dt><dd><?= e($timeText) ?></dd>
        <dt>Total</dt><dd><?= e(money($b['total_price'])) ?></dd>
      </dl>
      <p class="small muted" style="margin:0"><?= e($b['address']) ?>, <?= e($b['city']) ?>. Pay at the venue.</p>
    </div>
    <div class="actions" style="margin-top:1rem">
      <a class="btn" href="<?= e(url('dashboard.php')) ?>">View my bookings</a>
      <a class="btn btn-ghost" href="<?= e(url('receipt.php?type=booking&id=' . $b['id'])) ?>">Receipt</a>
    </div>
  </div>
</div>
