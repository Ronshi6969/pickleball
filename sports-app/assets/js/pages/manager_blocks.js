// manager/blocks.html: block out hours (maintenance, holidays). Forms post to actions/manager_blocks.php
PT.ready.then(async session => {
  const { esc, dateLabel } = PT;
  const data = await PT.api('manager/blocks.php');
  const area = document.getElementById('blocks-area');

  if (!data.courts.length) {
    area.innerHTML = '<div class="empty">Add a court first, then you can block hours on it. <a href="courts.html">Add a court</a></div>';
    return;
  }
  const form = document.getElementById('block-form');
  form.elements.court_id.innerHTML = '<option value="all">All courts</option>' + data.courts.map(c => `<option value="${Number(c.id)}">${esc(c.name)}</option>`).join('');
  form.elements.block_date.min = session.today;
  form.elements.start_hour.innerHTML = PT.hourOptions(0, 23, data.opening_hour);
  form.elements.end_hour.innerHTML = PT.hourOptions(1, 24, data.closing_hour, true);
  PT.fillForm(form, session.old);
  document.getElementById('block-wrap').hidden = false;

  const list = document.getElementById('blocks-box');
  if (!data.blocks.length) { list.innerHTML = '<div class="empty">No upcoming blocks. All hours are open for booking.</div>'; return; }
  list.innerHTML = `<div class="table-wrap"><table>
    <thead><tr><th>Date</th><th>Court</th><th>Hours</th><th>Reason</th><th></th></tr></thead><tbody>` +
    data.blocks.map(b => `<tr><td>${esc(dateLabel(b.block_date))}</td><td>${esc(b.court_name)}</td>
      <td>${esc(b.time_text)}</td><td>${esc(b.reason || '-')}</td>
      <td><form method="post" action="../actions/manager_blocks.php" class="inline-form" data-confirm="Remove this block?">
        <input type="hidden" name="csrf" value="${esc(session.csrf)}"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="${Number(b.id)}">
        <button class="btn btn-sm btn-ghost" type="submit">Remove</button></form></td></tr>`).join('') + '</tbody></table></div>';
});
