<div class="container">
  <div class="receipt">
    <h1>Play Together</h1>
    <p class="muted">Receipt <?= e($r['reference']) ?><br>Issued <?= e($issuedText) ?></p>
    <dl>
      <dt>Player</dt><dd><?= e($user['name']) ?></dd>
      <dt><?= $type === 'booking' ? 'Court' : 'Open play' ?></dt><dd><?= e($r['item']) ?></dd>
      <dt>Venue</dt><dd><?= e($r['venue']) ?></dd>
      <dt>Date</dt><dd><?= e(date_label($r['d'])) ?></dd>
      <dt>Time</dt><dd><?= e($timeText) ?></dd>
      <?php if ($type === 'openplay'): ?><dt>Slots</dt><dd><?= (int)$r['slots'] ?></dd><?php endif; ?>
      <dt>Status</dt><dd><?= e($statusText) ?></dd>
    </dl>
    <div class="total"><span>Total</span><span><?= e(money($r['amount'])) ?></span></div>
    <p class="small muted" style="margin-top:1rem">Payment is collected at the venue.</p>
    <div class="actions no-print">
      <button class="btn btn-sm" onclick="window.print()">Print</button>
      <a class="btn btn-sm btn-ghost" href="<?= e(url('dashboard.php?tab=history')) ?>">Back</a>
    </div>
  </div>
</div>
