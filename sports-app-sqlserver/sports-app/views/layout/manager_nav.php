<?php /* Manager sub-menu. Expects $mnav = current section key */
$items = ['index' => 'Overview', 'openplays' => 'Open plays', 'courts' => 'Courts', 'blocks' => 'Blocked hours', 'bookings' => 'Rentals', 'venue' => 'Venue details'];
?>
<nav class="subnav container" aria-label="Manager">
  <?php foreach ($items as $k => $label): ?>
    <a href="<?= e(url('manager/' . $k . '.php')) ?>" class="<?= ($mnav ?? '') === $k ? 'on' : '' ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>
