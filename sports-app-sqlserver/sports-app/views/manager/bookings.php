<?php require __DIR__ . '/../layout/manager_nav.php'; ?>
<div class="container band" style="padding-top:1rem">
  <h1>Court rentals</h1>
  <div class="tabs">
    <a href="?show=upcoming" class="<?= $show === 'upcoming' ? 'on' : '' ?>">Upcoming</a>
    <a href="?show=all" class="<?= $show === 'all' ? 'on' : '' ?>">All history</a>
  </div>
  <?php if (!$rows): ?><div class="empty">No rentals to show yet.</div>
  <?php else: ?>
  <div class="table-wrap"><table>
    <thead><tr><th>Date</th><th>Time</th><th>Court</th><th>Player</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $b): ?>
      <tr><td><?= e(date_label($b['booking_date'])) ?></td>
        <td><?= e($b['time_text']) ?></td>
        <td><?= e($b['court_name']) ?></td>
        <td><?= e($b['player']) ?><br><span class="small muted"><?= e($b['email']) ?></span></td>
        <td><?= e(money($b['total_price'])) ?></td>
        <td><?= $b['status'] === 'confirmed' ? '<span class="badge badge-ok">Confirmed</span>' : '<span class="badge badge-bad">Cancelled</span>' ?></td>
        <td><?php if ($b['future']): ?><form method="post" class="inline-form" data-confirm="Cancel this booking?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
          <button class="btn btn-sm btn-danger" type="submit">Cancel</button></form><?php endif; ?></td></tr>
    <?php endforeach; ?></tbody></table></div>
  <?php endif; ?>
</div>
