<?php require __DIR__ . '/../layout/manager_nav.php'; ?>
<div class="container band" style="padding-top:1rem">
  <h1>Blocked hours</h1>
  <?php if (!$courts): ?>
    <div class="empty">Add a court first, then you can block hours on it. <a href="<?= e(url('manager/courts.php')) ?>">Add a court</a></div>
  <?php else: ?>
  <form method="post" class="form-card" style="margin-bottom:2rem" novalidate>
    <?php if ($errors): ?><ul class="error-list"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul><?php endif; ?>
    <?= csrf_field() ?><input type="hidden" name="action" value="add">
    <div class="row row-2">
      <div class="field"><label for="court_id">Court</label><select id="court_id" name="court_id">
        <option value="all">All courts</option>
        <?php foreach ($courts as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="block_date">Date</label><input type="date" id="block_date" name="block_date" min="<?= e($today) ?>" required></div>
    </div>
    <div class="row row-3">
      <div class="field"><label for="start_hour">From</label><select id="start_hour" name="start_hour"><?php for ($h = 0; $h <= 23; $h++): ?><option value="<?= $h ?>" <?= $h === (int)$venue['opening_hour'] ? 'selected' : '' ?>><?= e(hour_label($h)) ?></option><?php endfor; ?></select></div>
      <div class="field"><label for="end_hour">Until</label><select id="end_hour" name="end_hour"><?php for ($h = 1; $h <= 24; $h++): ?><option value="<?= $h ?>" <?= $h === (int)$venue['closing_hour'] ? 'selected' : '' ?>><?= e($h === 24 ? '12:00 AM (midnight)' : hour_label($h)) ?></option><?php endfor; ?></select></div>
      <div class="field"><label for="reason">Reason (optional)</label><input type="text" id="reason" name="reason" maxlength="150" placeholder="Floor resurfacing"></div>
    </div>
    <button class="btn" type="submit">Block these hours</button>
  </form>

  <?php if (!$blocks): ?><div class="empty">No upcoming blocks. All hours are open for booking.</div>
  <?php else: ?>
  <div class="table-wrap"><table>
    <thead><tr><th>Date</th><th>Court</th><th>Hours</th><th>Reason</th><th></th></tr></thead><tbody>
    <?php foreach ($blocks as $b): ?>
      <tr><td><?= e(date_label($b['block_date'])) ?></td><td><?= e($b['court_name']) ?></td>
        <td><?= e($b['time_text']) ?></td>
        <td><?= e($b['reason'] ?? '-') ?></td>
        <td><form method="post" class="inline-form" data-confirm="Remove this block?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
          <button class="btn btn-sm btn-ghost" type="submit">Remove</button></form></td></tr>
    <?php endforeach; ?></tbody></table></div>
  <?php endif; ?>
  <?php endif; ?>
</div>
