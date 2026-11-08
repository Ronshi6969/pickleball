// manager/bookings.html: court rentals at this venue, with cancel (posts to actions/manager_bookings.php)
PT.ready.then(async session => {
  const { esc, money, dateLabel } = PT;
  const show = PT.query().get('show') === 'all' ? 'all' : 'upcoming';
  const data = await PT.api('manager/bookings.php', { show });

  document.getElementById('tabs').innerHTML =
    `<a href="?show=upcoming" class="${show === 'upcoming' ? 'on' : ''}">Upcoming</a>` +
    `<a href="?show=all" class="${show === 'all' ? 'on' : ''}">All history</a>`;

  const box = document.getElementById('rows-box');
  if (!data.rows.length) { box.innerHTML = '<div class="empty">No rentals to show yet.</div>'; return; }
  box.innerHTML = `<div class="table-wrap"><table>
    <thead><tr><th>Date</th><th>Time</th><th>Court</th><th>Player</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody>` +
    data.rows.map(b => `<tr><td>${esc(dateLabel(b.booking_date))}</td>
      <td>${esc(b.time_text)}</td>
      <td>${esc(b.court_name)}</td>
      <td>${esc(b.player)}<br><span class="small muted">${esc(b.email)}</span></td>
      <td>${esc(money(b.total_price))}</td>
      <td>${b.status === 'confirmed' ? '<span class="badge badge-ok">Confirmed</span>' : '<span class="badge badge-bad">Cancelled</span>'}</td>
      <td>${b.future ? `<form method="post" action="../actions/manager_bookings.php" class="inline-form" data-confirm="Cancel this booking?">
        <input type="hidden" name="csrf" value="${esc(session.csrf)}"><input type="hidden" name="show" value="${show}"><input type="hidden" name="id" value="${Number(b.id)}">
        <button class="btn btn-sm btn-danger" type="submit">Cancel</button></form>` : ''}</td></tr>`).join('') + '</tbody></table></div>';
});
