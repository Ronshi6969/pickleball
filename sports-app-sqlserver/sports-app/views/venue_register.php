<div class="container">
  <form method="post" class="form-card form-narrow" style="max-width:38rem" novalidate>
    <h1 style="font-size:2.4rem">List your venue</h1>
    <?php if ($errors): ?><ul class="error-list"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul><?php endif; ?>
    <?= csrf_field() ?>
    <div class="row row-2">
      <div class="field"><label for="name">Your name</label><input type="text" id="name" name="name" value="<?= e($old['name']) ?>" required></div>
      <div class="field"><label for="email">Email</label><input type="email" id="email" name="email" value="<?= e($old['email']) ?>" required></div>
    </div>
    <div class="field"><label for="password">Password</label><input type="password" id="password" name="password" required minlength="8" autocomplete="new-password"></div>
    <div class="field"><label for="venue">Venue name</label><input type="text" id="venue" name="venue" value="<?= e($old['venue']) ?>" required></div>
    <div class="row row-2">
      <div class="field"><label for="address">Address</label><input type="text" id="address" name="address" value="<?= e($old['address']) ?>" required></div>
      <div class="field"><label for="city">City</label><input type="text" id="city" name="city" value="<?= e($old['city']) ?>" placeholder="Imus, Cavite" required></div>
    </div>
    <div class="row row-2">
      <div class="field"><label for="open">Opens at</label>
        <select id="open" name="open"><?php for ($h = 0; $h <= 23; $h++): ?><option value="<?= $h ?>" <?= $old['open'] === $h ? 'selected' : '' ?>><?= e(hour_label($h)) ?></option><?php endfor; ?></select></div>
      <div class="field"><label for="close">Closes at</label>
        <select id="close" name="close"><?php for ($h = 1; $h <= 24; $h++): ?><option value="<?= $h ?>" <?= $old['close'] === $h ? 'selected' : '' ?>><?= e($h === 24 ? '12:00 AM (midnight)' : hour_label($h)) ?></option><?php endfor; ?></select></div>
    </div>
    <button class="btn btn-block" type="submit">Create venue account</button>
  </form>
</div>
