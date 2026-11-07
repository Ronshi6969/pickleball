<?php /* Layout top. Expects: $pageTitle, $navActive, $user, $flashes (all set by render()) */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> | Play Together</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Lilita+One&family=Outfit:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body>
<header class="site-header">
  <div class="container bar">
    <a class="logo" href="<?= e(url('index.php')) ?>">Play<br>Together</a>
    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="main-nav">Menu</button>
    <nav id="main-nav" class="main-nav" aria-label="Main">
      <a href="<?= e(url('venues.php')) ?>" class="<?= $navActive === 'courts' ? 'on' : '' ?>">Court rental</a>
      <a href="<?= e(url('openplays.php')) ?>" class="<?= $navActive === 'open' ? 'on' : '' ?>">Open play</a>
      <?php if ($user && $user['role'] === 'manager'): ?>
        <a href="<?= e(url('manager/index.php')) ?>" class="<?= $navActive === 'manager' ? 'on' : '' ?>">Manager</a>
      <?php elseif ($user): ?>
        <a href="<?= e(url('dashboard.php')) ?>" class="<?= $navActive === 'dash' ? 'on' : '' ?>">My plays</a>
      <?php endif; ?>
      <?php if ($user): ?>
        <?php if ($user['role'] === 'player'): ?>
          <a href="<?= e(url('profile.php')) ?>" class="<?= $navActive === 'profile' ? 'on' : '' ?>">Profile</a>
        <?php endif; ?>
        <a href="<?= e(url('logout.php')) ?>" class="btn btn-sm btn-ghost">Log out</a>
      <?php else: ?>
        <a href="<?= e(url('login.php')) ?>">Log in</a>
        <a href="<?= e(url('register.php')) ?>" class="btn btn-sm btn-ink">Join as player</a>
        <a href="<?= e(url('venue_register.php')) ?>" class="btn btn-sm btn-ghost">List your venue</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<?php if ($flashes): ?>
  <div class="container flashes" role="status">
    <?php foreach ($flashes as $f): ?>
      <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<main>
