// manager/openplays.html: create / edit / delete open plays, see who joined.
// Forms post to actions/manager_openplays.php
PT.ready.then(async session => {
  const { esc, money, dateLabel, timeLabel } = PT;
  const q = PT.query();
  const data = await PT.api('manager/openplays.php', { edit: q.get('edit'), roster: q.get('roster') });
  const form = document.getElementById('play-form');
  const editing = data.editing;

  document.getElementById('form-title').textContent = editing ? 'Edit open play' : 'Create an open play';
  document.getElementById('submit-btn').textContent = editing ? 'Save changes' : 'Create open play';
  document.getElementById('cancel-link').hidden = !editing;
  form.elements.sport_id.innerHTML = data.sports.map(s => `<option value="${Number(s.id)}">${esc(s.name)}</option>`).join('');

  // defaults for a new open play, or the values of the play being edited
  const values = editing
    ? { id: editing.id, title: editing.title, description: editing.description || '', play_date: editing.play_date, start_time: editing.start_time, end_time: editing.end_time,
        sport_id: editing.sport_id, skill_level: editing.skill_level, courts_used: editing.courts_used, max_slots: editing.max_slots, price: editing.price }
    : { id: 0, start_time: '07:00', end_time: '10:00', skill_level: 'all', courts_used: 1, max_slots: 20 };
  PT.fillForm(form, values);
  PT.fillForm(form, session.old);   // after a failed save, show what was typed

  if (data.roster !== null) {
    const box = document.getElementById('roster-box');
    box.hidden = false;
    document.getElementById('roster-list').innerHTML = data.roster.length
      ? '<ul>' + data.roster.map(r => `<li>${esc(r.name)} &mdash; ${PT.plural(Number(r.slots), 'slot')}</li>`).join('') + '</ul>'
      : '<p class="muted">No players yet.</p>';
  }

  const csrf = `<input type="hidden" name="csrf" value="${esc(session.csrf)}">`;
  const box = document.getElementById('plays-box');
  if (!data.plays.length) { box.innerHTML = '<div class="empty">No open plays yet. Create your first one above.</div>'; return; }
  box.innerHTML = `<div class="table-wrap"><table>
    <thead><tr><th>Date and time</th><th>Title</th><th>Slots</th><th>Price</th><th>Actions</th></tr></thead><tbody>` +
    data.plays.map(p => `<tr><td>${esc(dateLabel(p.play_date))}<br><span class="small muted">${esc(timeLabel(p.start_time))} to ${esc(timeLabel(p.end_time))}</span></td>
      <td>${esc(p.title)} ${p.past ? '<span class="badge">Past</span>' : ''}<br><span class="small muted">${esc(p.sport)} &middot; ${esc(p.skill_level)}</span></td>
      <td>${Number(p.taken)} / ${Number(p.max_slots)}</td>
      <td>${esc(money(p.price))}</td>
      <td><div class="actions">
        <a class="btn btn-sm btn-ghost" href="?roster=${Number(p.id)}">Players</a>
        <a class="btn btn-sm btn-ghost" href="?edit=${Number(p.id)}">Edit</a>
        <form method="post" action="../actions/manager_openplays.php" class="inline-form" data-confirm="Delete this open play and all its registrations?">${csrf}<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="${Number(p.id)}">
          <button class="btn btn-sm btn-danger" type="submit">Delete</button></form></div></td></tr>`).join('') + '</tbody></table></div>';
});
