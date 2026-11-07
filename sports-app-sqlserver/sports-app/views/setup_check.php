<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Setup check | Play Together</title>
  <style>
    body { font-family: system-ui, sans-serif; max-width: 52rem; margin: 2rem auto; padding: 0 1rem; line-height: 1.5; }
    .row { border: 1px solid #ccc; border-radius: 8px; padding: .8rem 1rem; margin-bottom: .7rem; }
    .ok { border-color: #2a9d4b; background: #effaf2; }
    .bad { border-color: #c0392b; background: #fdf0ee; }
    .hint { margin: .4rem 0 0; font-size: .95rem; }
    code { background: #eee; padding: 0 .25rem; }
  </style>
</head>
<body>
  <h1>Setup check</h1>
  <?php foreach ($checks as $c): ?>
    <div class="row <?= $c['ok'] ? 'ok' : 'bad' ?>">
      <strong><?= $c['ok'] ? '&#10003;' : '&#10007;' ?> <?= htmlspecialchars($c['label']) ?></strong><br>
      <?= htmlspecialchars($c['detail']) ?>
      <?php if (!$c['ok'] && $c['hint']): ?><p class="hint"><strong>Fix:</strong> <?= htmlspecialchars($c['hint']) ?></p><?php endif; ?>
    </div>
  <?php endforeach; ?>
  <?php $allOk = !in_array(false, array_column($checks, 'ok'), true); ?>
  <?php if ($allOk): ?>
    <p><strong>All good.</strong> Delete <code>setup_check.php</code>, then open <a href="<?= htmlspecialchars(BASE_URL) ?>/">the app</a>.</p>
  <?php endif; ?>
</body>
</html>
