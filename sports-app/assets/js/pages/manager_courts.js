// manager/courts.html: add / edit pickleball court, hide-show, delete. Forms post to actions/manager_courts.php
PT.ready.then(async session => {
  const { esc, money } = PT;
  const editId = PT.query().get('edit');
  const data = await PT.api('manager/courts.php', { edit: editId });
  const form = document.getElementById('court-form');
  const edit = data.edit;

  document.getElementById('form-title').textContent = edit ? 'Edit pickleball court' : 'Add a pickleball court';
  document.getElementById('submit-btn').textContent = edit ? 'Save changes' : 'Add court';
  document.getElementById('cancel-link').hidden = !edit;
  form.elements.id.value = edit ? edit.id : 0;
  if (edit) { form.elements.name.value = edit.name; form.elements.hourly_rate.value = edit.hourly_rate; }
  PT.fillForm(form, session.old);

  const csrf = `<input type="hidden" name="csrf" value="${esc(session.csrf)}">`;
  const box = document.getElementById('courts-box');
  if (!data.courts.length) { box.innerHTML = '<div class="empty">No pickleball courts yet. Add your first court above so players can book it.</div>'; return; }
  box.innerHTML = `<div class="table-wrap"><table>
    <thead><tr><th>Court</th><th>Rate</th><th>Status</th><th>Actions</th></tr></thead><tbody>` +
    data.courts.map(c => `<tr>
      <td>${esc(c.name)} <span class="badge">Pickleball</span></td><td>${esc(money(c.hourly_rate))}/hr</td>
      <td>${Number(c.is_active) ? '<span class="badge badge-ok">Open for booking</span>' : '<span class="badge badge-warn">Hidden</span>'}</td>
      <td><div class="actions">
        <a class="btn btn-sm btn-ghost" href="?edit=${Number(c.id)}">Edit</a>
        <form method="post" action="../actions/manager_courts.php" class="inline-form">${csrf}<input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="${Number(c.id)}">
          <button class="btn btn-sm btn-ghost" type="submit">${Number(c.is_active) ? 'Hide' : 'Show'}</button></form>
        <form method="post" action="../actions/manager_courts.php" class="inline-form" data-confirm="Delete this court?">${csrf}<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="${Number(c.id)}">
          <button class="btn btn-sm btn-danger" type="submit">Delete</button></form>
      </div></td></tr>`).join('') + '</tbody></table></div>';
});
