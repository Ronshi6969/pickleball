// manager/bookings.html: create & manage court rentals at this venue (posts to actions/manager_bookings.php)
PT.ready.then(async session => {
  const { esc, money, dateLabel } = PT;
  const show = PT.query().get('show') === 'all' ? 'all' : 'upcoming';
  const data = await PT.api('manager/bookings.php', { show });

  if (!data.courts.length) {
    document.getElementById('create-area').innerHTML =
      '<div class="empty" style="margin-bottom:1.5rem"><a href="courts.html">Add an active pickleball court first</a> before creating rentals.</div>';
  } else {
    document.getElementById('create-wrap').hidden = false;
    const form = document.getElementById('rental-form');
    form.elements.show.value = show;
    form.elements.court_id.innerHTML = data.courts.map(c =>
      `<option value="${Number(c.id)}">${esc(c.name)} (${esc(money(c.hourly_rate))}/hr)</option>`).join('');
    form.elements.user_id.innerHTML = data.players.length
      ? data.players.map(u => `<option value="${Number(u.id)}">${esc(u.name)} (${esc(u.email)})</option>`).join('')
      : '<option value="0">No players registered yet</option>';
    const dateInput = form.elements.booking_date;
    dateInput.min = session.today;
    dateInput.value = session.today;
    form.elements.start_hour.innerHTML = PT.hourOptions(data.opening_hour, data.closing_hour - 1, data.opening_hour);
    form.elements.end_hour.innerHTML = PT.hourOptions(data.opening_hour + 1, data.closing_hour, Math.min(data.opening_hour + 1, data.closing_hour));
    PT.fillForm(form, session.old);
  }

  document.getElementById('tabs').innerHTML =
    `<a href="?show=upcoming" class="${show === 'upcoming' ? 'on' : ''}">Upcoming</a>` +
    `<a href="?show=all" class="${show === 'all' ? 'on' : ''}">All history</a>`;

  const box = document.getElementById('rows-box');
  if (!data.rows.length) { box.innerHTML = '<div class="empty">No rentals to show yet.</div>'; return; }
  box.innerHTML = `<div class="table-wrap"><table>
    <thead><tr><th>Ref</th><th>Date</th><th>Time</th><th>Court</th><th>Player</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody>` +
    data.rows.map(b => `<tr>
      <td><strong>${esc(b.reference || '-')}</strong></td>
      <td>${esc(dateLabel(b.booking_date))}</td>
      <td>${esc(b.time_text)}</td>
      <td>${esc(b.court_name)}</td>
      <td>${esc(b.player)}<br><span class="small muted">${esc(b.email)}</span></td>
      <td>${esc(money(b.total_price))}</td>
      <td>${b.status === 'confirmed' ? '<span class="badge badge-ok">Confirmed</span>' : '<span class="badge badge-bad">Cancelled</span>'}</td>
      <td>${b.future ? `<form method="post" action="../actions/manager_bookings.php" class="inline-form" data-confirm="Cancel this booking?">
        <input type="hidden" name="csrf" value="${esc(session.csrf)}"><input type="hidden" name="action" value="cancel"><input type="hidden" name="show" value="${show}"><input type="hidden" name="id" value="${Number(b.id)}">
        <button class="btn btn-sm btn-danger" type="submit">Cancel</button></form>` : ''}</td></tr>`).join('') + '</tbody></table></div>';
});
