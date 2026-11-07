<?php require __DIR__ . '/../layout/manager_nav.php'; ?>
<div class="container">
  <form method="post" enctype="multipart/form-data" class="form-card" style="max-width:40rem;margin:1rem auto 3rem" novalidate>
    <h1 style="font-size:2.2rem">Venue details</h1>
    <?php if ($errors): ?><ul class="error-list"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul><?php endif; ?>
    <?= csrf_field() ?>
    <div class="field"><?= media($venue['photo'], $venue['name']) ?></div>
    <div class="field"><label for="photo">Venue photo</label><input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp"></div>
    <div class="field"><label for="name">Venue name</label><input type="text" id="name" name="name" value="<?= e($venue['name']) ?>" required></div>
    <div class="row row-2">
      <div class="field"><label for="address">Address</label><input type="text" id="address" name="address" value="<?= e($venue['address']) ?>" required></div>
      <div class="field"><label for="city">City</label><input type="text" id="city" name="city" value="<?= e($venue['city']) ?>" required></div>
    </div>
    <div class="field"><label for="description">Description</label><textarea id="description" name="description"><?= e($venue['description']) ?></textarea></div>
    <div class="row row-2">
      <div class="field"><label for="open">Opens at</label><select id="open" name="open"><?php for ($h = 0; $h <= 23; $h++): ?><option value="<?= $h ?>" <?= (int)$venue['opening_hour'] === $h ? 'selected' : '' ?>><?= e(hour_label($h)) ?></option><?php endfor; ?></select></div>
      <div class="field"><label for="close">Closes at</label><select id="close" name="close"><?php for ($h = 1; $h <= 24; $h++): ?><option value="<?= $h ?>" <?= (int)$venue['closing_hour'] === $h ? 'selected' : '' ?>><?= e($h === 24 ? '12:00 AM (midnight)' : hour_label($h)) ?></option><?php endfor; ?></select></div>
    </div>
    <button class="btn btn-block" type="submit">Save venue details</button>
  </form>
</div>
