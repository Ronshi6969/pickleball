<?php require __DIR__ . '/../layout/manager_nav.php'; ?>
<div class="container band" style="padding-top:1rem">
  <h1>Open plays</h1>
  <form method="post" class="form-card" style="margin-bottom:2rem" novalidate>
    <h2 style="font-size:1.6rem"><?= $editing ? 'Edit open play' : 'Create an open play' ?></h2>
    <?php if ($errors): ?><ul class="error-list"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul><?php endif; ?>
    <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)$id ?>">
    <div class="field"><label for="title">Title</label><input type="text" id="title" name="title" value="<?= e($form['title']) ?>" required maxlength="120"></div>
    <div class="row row-3">
      <div class="field"><label for="play_date">Date</label><input type="date" id="play_date" name="play_date" value="<?= e($form['play_date']) ?>" required></div>
      <div class="field"><label for="start_time">Starts</label><input type="time" id="start_time" name="start_time" value="<?= e($form['start_time']) ?>" required></div>
      <div class="field"><label for="end_time">Ends</label><input type="time" id="end_time" name="end_time" value="<?= e($form['end_time']) ?>" required></div>
    </div>
    <div class="row row-3">
      <div class="field"><label for="sport_id">Sport</label><select id="sport_id" name="sport_id"><?php foreach ($sports as $s): ?>
        <option value="<?= (int)$s['id'] ?>" <?= (int)$form['sport_id'] === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="skill_level">Skill level</label><select id="skill_level" name="skill_level">
        <?php foreach (['all' => 'All levels', 'beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced'] as $k => $l): ?>
        <option value="<?= $k ?>" <?= $form['skill_level'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="courts_used">Courts used</label><input type="number" id="courts_used" name="courts_used" min="1" value="<?= e($form['courts_used']) ?>" required></div>
    </div>
    <div class="row row-2">
      <div class="field"><label for="max_slots">Total slots</label><input type="number" id="max_slots" name="max_slots" min="1" value="<?= e($form['max_slots']) ?>" required></div>
      <div class="field"><label for="price">Price per slot (₱)</label><input type="number" id="price" name="price" min="0" step="0.01" value="<?= e($form['price']) ?>" required></div>
    </div>
    <div class="field"><label for="description">Description (optional)</label><textarea id="description" name="description"><?= e($form['description']) ?></textarea></div>
    <div class="actions"><button class="btn" type="submit"><?= $editing ? 'Save changes' : 'Create open play' ?></button>
      <?php if ($editing): ?><a class="btn btn-ghost" href="<?= e(url('manager/openplays.php')) ?>">Cancel</a><?php endif; ?></div>
  </form>

  <?php if ($roster !== null): ?>
    <div class="form-card" style="margin-bottom:2rem">
      <h2 style="font-size:1.6rem">Registered players</h2>
      <?php if (!$roster): ?><p class="muted">No players yet.</p><?php else: ?>
        <ul><?php foreach ($roster as $r): ?><li><?= e($r['name']) ?> &mdash; <?= (int)$r['slots'] ?> slot<?= $r['slots'] > 1 ? 's' : '' ?></li><?php endforeach; ?></ul>
      <?php endif; ?>
      <a class="btn btn-sm btn-ghost" href="<?= e(url('manager/openplays.php')) ?>">Close</a>
    </div>
  <?php endif; ?>

  <?php if (!$plays): ?><div class="empty">No open plays yet. Create your first one above.</div>
  <?php else: ?>
  <div class="table-wrap"><table>
    <thead><tr><th>Date and time</th><th>Title</th><th>Slots</th><th>Price</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($plays as $p): ?>
      <tr><td><?= e(date_label($p['play_date'])) ?><br><span class="small muted"><?= e(time_label($p['start_time'])) ?> to <?= e(time_label($p['end_time'])) ?></span></td>
        <td><?= e($p['title']) ?> <?= $p['past'] ? '<span class="badge">Past</span>' : '' ?><br><span class="small muted"><?= e($p['sport']) ?> &middot; <?= e($p['skill_level']) ?></span></td>
        <td><?= (int)$p['taken'] ?> / <?= (int)$p['max_slots'] ?></td>
        <td><?= e(money($p['price'])) ?></td>
        <td><div class="actions">
          <a class="btn btn-sm btn-ghost" href="?roster=<?= (int)$p['id'] ?>">Players</a>
          <a class="btn btn-sm btn-ghost" href="?edit=<?= (int)$p['id'] ?>">Edit</a>
          <form method="post" class="inline-form" data-confirm="Delete this open play and all its registrations?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
            <button class="btn btn-sm btn-danger" type="submit">Delete</button></form></div></td></tr>
    <?php endforeach; ?></tbody></table></div>
  <?php endif; ?>
</div>
