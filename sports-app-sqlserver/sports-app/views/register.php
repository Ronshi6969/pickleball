<div class="container">
  <form method="post" class="form-card form-narrow" novalidate>
    <h1 style="font-size:2.4rem">Join as a player</h1>
    <?php if ($errors): ?><ul class="error-list"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul><?php endif; ?>
    <?= csrf_field() ?>
    <div class="field"><label for="name">Full name</label>
      <input type="text" id="name" name="name" value="<?= e($old['name']) ?>" required maxlength="100"></div>
    <div class="field"><label for="email">Email</label>
      <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" required></div>
    <div class="row row-2">
      <div class="field"><label for="password">Password</label>
        <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password"></div>
      <div class="field"><label for="password2">Confirm password</label>
        <input type="password" id="password2" name="password2" required minlength="8" autocomplete="new-password"></div>
    </div>
    <div class="field"><label for="skill">Skill level</label>
      <select id="skill" name="skill">
        <?php foreach (SKILLS as $s): ?>
          <option value="<?= e($s) ?>" <?= $old['skill'] === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
        <?php endforeach; ?>
      </select></div>
    <div class="field"><label>Preferred sports</label>
      <div class="checks">
        <?php foreach ($sports as $s): ?>
          <label><input type="checkbox" name="sports[]" value="<?= (int)$s['id'] ?>" <?= in_array((int)$s['id'], $old['sports'], true) ? 'checked' : '' ?>><span><?= e($s['name']) ?></span></label>
        <?php endforeach; ?>
      </div></div>
    <button class="btn btn-block" type="submit">Create account</button>
  </form>
</div>
