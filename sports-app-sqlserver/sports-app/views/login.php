<div class="container">
  <form method="post" class="form-card form-narrow" novalidate>
    <h1 style="font-size:2.4rem">Log in</h1>
    <?php if ($errors): ?><ul class="error-list"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul><?php endif; ?>
    <?= csrf_field() ?>
    <div class="field"><label for="email">Email</label>
      <input type="email" id="email" name="email" value="<?= e($email) ?>" required autocomplete="email"></div>
    <div class="field"><label for="password">Password</label>
      <input type="password" id="password" name="password" required autocomplete="current-password"></div>
    <button class="btn btn-block" type="submit">Log in</button>
    <p class="small center" style="margin-top:1rem">New here? <a href="<?= e(url('register.php')) ?>">Join as a player</a> or <a href="<?= e(url('venue_register.php')) ?>">list your venue</a>.</p>
  </form>
</div>
