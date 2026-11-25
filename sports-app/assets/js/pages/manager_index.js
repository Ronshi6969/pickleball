// manager/index.html: active branch summary + stats + all branches switcher
PT.ready.then(async session => {
  const { esc, media, plural } = PT;
  const { venue, branches, stats } = await PT.api('manager/index.php');
  document.getElementById('hero').innerHTML = `
    ${media(venue.photo, venue.name, 'thumb', venue.city)}
    <div>
      <h1>${esc(venue.name)}</h1>
      <p class="muted">${esc(venue.address)}</p>
      <a class="btn btn-ink" href="venue.html">Edit branch &amp; photo</a>
    </div>`;
  document.getElementById('stats').innerHTML = `
    <div class="stat"><b>${stats.courts}</b>Active courts</div>
    <div class="stat"><b>${stats.upBook}</b>Upcoming rentals</div>
    <div class="stat"><b>${stats.upPlays}</b>Upcoming open plays</div>
    <div class="stat"><b>${stats.players}</b>Open play slots taken</div>`;

  const branchesEl = document.getElementById('branches-box');
  if (branchesEl && branches && branches.length) {
    branchesEl.innerHTML = branches.map(b => {
      const active = Number(b.id) === Number(venue.id);
      return `<form method="post" action="../actions/manager_branch.php" class="branch-card ${active ? 'active' : ''}">
        <input type="hidden" name="csrf" value="${esc(session.csrf)}">
        <input type="hidden" name="branch_id" value="${Number(b.id)}">
        <input type="hidden" name="return_to" value="index.html">
        <div class="branch-card-top">
          <span class="badge ${active ? 'badge-ok' : ''}">${active ? 'Active' : 'Branch'}</span>
          <span class="small muted">${plural(Number(b.courts_count), 'court')}</span>
        </div>
        <h3>${esc(b.city)}</h3>
        <p class="small muted">${esc(b.address)}</p>
        <button class="btn btn-sm ${active ? 'btn-ink' : 'btn-ghost'}" type="submit">${active ? 'Selected' : 'Manage ' + esc(b.city)}</button>
      </form>`;
    }).join('');
  }
});
