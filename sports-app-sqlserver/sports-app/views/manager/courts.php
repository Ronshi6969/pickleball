<?php require __DIR__ . '/../layout/manager_nav.php'; ?>
<div class="container band" style="padding-top:1rem">
  <h1>Courts and rates</h1>
  <form method="post" class="form-card" style="margin-bottom:2rem" novalidate>
    <h2 style="font-size:1.6rem"><?= $edit ? 'Edit court' : 'Add a court' ?></h2>
    <?php if ($errors): ?><ul class="error-list"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul><?php endif; ?>
    <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
    <div class="row row-3">
      <div class="field"><label for="name">Court name</label><input type="text" id="name" name="name" value="<?= e($edit['name'] ?? '') ?>" required></div>
      <div class="field"><label for="sport_id">Sport</label><select id="sport_id" name="sport_id">
        <?php foreach ($sports as $s): ?><option value="<?= (int)$s['id'] ?>" <?= (int)($edit['sport_id'] ?? 0) === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="hourly_rate">Rate per hour (₱)</label><input type="number" id="hourly_rate" name="hourly_rate" min="1" step="0.01" value="<?= e($edit['hourly_rate'] ?? '') ?>" required></div>
    </div>
    <div class="actions"><button class="btn" type="submit"><?= $edit ? 'Save changes' : 'Add court' ?></button>
      <?php if ($edit): ?><a class="btn btn-ghost" href="<?= e(url('manager/courts.php')) ?>">Cancel</a><?php endif; ?></div>
  </form>

  <?php if (!$courts): ?>
    <div class="empty">No courts yet. Add your first court above so players can book it.</div>
  <?php else: ?>
  <div class="table-wrap"><table>
    <thead><tr><th>Court</th><th>Sport</th><th>Rate</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($courts as $c): ?>
      <tr>
        <td><?= e($c['name']) ?></td><td><?= e($c['sport']) ?></td><td><?= e(money($c['hourly_rate'])) ?>/hr</td>
        <td><?= $c['is_active'] ? '<span class="badge badge-ok">Open for booking</span>' : '<span class="badge badge-warn">Hidden</span>' ?></td>
        <td><div class="actions">
          <a class="btn btn-sm btn-ghost" href="?edit=<?= (int)$c['id'] ?>">Edit</a>
          <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <button class="btn btn-sm btn-ghost" type="submit"><?= $c['is_active'] ? 'Hide' : 'Show' ?></button></form>
          <form method="post" class="inline-form" data-confirm="Delete this court?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <button class="btn btn-sm btn-danger" type="submit">Delete</button></form>
        </div></td>
      </tr>
    <?php endforeach; ?>
    </tbody></table></div>
  <?php endif; ?>
</div>
