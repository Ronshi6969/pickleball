<div class="container">
  <form method="post" enctype="multipart/form-data" class="form-card form-narrow" novalidate>
    <h1 style="font-size:2.4rem">Edit profile</h1>
    <?php if ($errors): ?><ul class="error-list"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul><?php endif; ?>
    <?= csrf_field() ?>
    <div class="field" style="display:flex;gap:1rem;align-items:center">
      <?= media($user['avatar'], $user['name'], 'avatar') ?>
      <div style="flex:1"><label for="avatar">Profile photo</label><input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp"></div>
    </div>
    <div class="field"><label for="name">Full name</label><input type="text" id="name" name="name" value="<?= e($user['name']) ?>" required maxlength="100"></div>
    <div class="field"><label for="skill">Skill level</label>
      <select id="skill" name="skill"><?php foreach (SKILLS as $s): ?>
        <option value="<?= e($s) ?>" <?= $user['skill_level'] === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option><?php endforeach; ?></select></div>
    <div class="field"><label>Preferred sports</label>
      <div class="checks"><?php foreach ($sports as $s): ?>
        <label><input type="checkbox" name="sports[]" value="<?= (int)$s['id'] ?>" <?= in_array((int)$s['id'], $mine, true) ? 'checked' : '' ?>><span><?= e($s['name']) ?></span></label>
      <?php endforeach; ?></div></div>
    <button class="btn btn-block" type="submit">Save profile</button>
  </form>
</div>
