// openplays.html: upcoming pickleball open plays grouped by date, slots left, roster, join / leave.
// Joining posts to actions/openplay_action.php (the server re-checks the slots with a row lock).
PT.ready.then(async session => {
  const { esc, money, dateLabel, timeLabel } = PT;
  const user = session.user;
  const q = PT.query();
  const params = { date: q.get('date') || '', venue: q.get('venue') || '' };
  const data = await PT.api('openplays.php', params);

  const dateInput = document.getElementById('date');
  dateInput.min = session.today;
  dateInput.value = params.date;
  const options = (list, current, allLabel) => '<option value="0">' + allLabel + '</option>' +
    list.map(x => `<option value="${Number(x.id)}"${String(x.id) === current ? ' selected' : ''}>${esc(x.name)}</option>`).join('');
  document.getElementById('venue').innerHTML = options(data.venues, params.venue, 'All venues');
  if (params.date || (params.venue && params.venue !== '0')) document.getElementById('clear-link').hidden = false;

  const box = document.getElementById('results');
  if (!data.groups.length) {
    box.innerHTML = '<div class="empty"><h3>No pickleball open plays found</h3><p>Try another date or clear the filters.</p></div>';
    return;
  }
  const loginHref = 'login.html?next=' + encodeURIComponent(location.pathname + location.search);

  function joinArea(p) {
    if (p.started) return '<p class="small muted">This session has started.</p>';
    if (user && user.role === 'manager') return '<p class="small muted">Log in as a player to join.</p>';
    if (!user) return `<a class="btn btn-ink" href="${esc(loginHref)}">Log in to join <span class="btn-arrow">&rarr;</span></a>`;
    if (p.myslots > 0) {
      return `<p><span class="badge badge-ok">You're in with ${PT.plural(p.myslots, 'slot')}</span></p>
        <form method="post" action="actions/openplay_action.php" class="inline-form"
          data-confirm="Are you sure you want to leave ${esc(p.title)}?"
          data-confirm-kicker="Open Play"
          data-confirm-title="Leave game?"
          data-confirm-btn="Yes, leave game"
          data-confirm-cancel="Stay in game">
          <input type="hidden" name="csrf" value="${esc(session.csrf)}">
          <input type="hidden" name="action" value="leave">
          <input type="hidden" name="open_play_id" value="${Number(p.id)}">
          <button class="btn btn-ghost btn-sm" type="submit">Leave game</button>
        </form>`;
    }
    if (p.left < 1) return '<span class="badge badge-bad">Full</span>';
    let opts = '';
    for (let i = 1; i <= Math.min(p.left, 10); i++) opts += `<option value="${i}">${i}</option>`;
    return `<form method="post" action="actions/openplay_action.php" class="join-form"
        data-confirm="Are you sure you want to join ${esc(p.title)} ({slots})?"
        data-confirm-slots="1"
        data-confirm-kicker="Confirm Join"
        data-confirm-title="Join open play?"
        data-confirm-btn="Yes, join game"
        data-confirm-cancel="Not now"
        data-confirm-kind="primary">
        <input type="hidden" name="csrf" value="${esc(session.csrf)}">
        <input type="hidden" name="action" value="join">
        <input type="hidden" name="open_play_id" value="${Number(p.id)}">
        <div class="field"><label for="slots-${Number(p.id)}">Slots</label>
          <select id="slots-${Number(p.id)}" name="slots">${opts}</select></div>
        <button class="btn" type="submit">Join game <span class="btn-arrow">&rarr;</span></button>
      </form>`;
  }

  box.innerHTML = data.groups.map(g => `
    <section class="date-group">
      <h3>${esc(dateLabel(g.date))}</h3>
      ${g.plays.map(p => `
        <article class="play-card" id="play-${Number(p.id)}">
          <div>
            <h3 style="margin-bottom:.35rem">${esc(p.title)}</h3>
            <div><span class="badge">Pickleball</span>
                 <span class="badge">${p.skill_level === 'all' ? 'All levels' : esc(p.skill_level.charAt(0).toUpperCase() + p.skill_level.slice(1))}</span>
                 <span class="badge">${PT.plural(Number(p.courts_used), 'court')}</span></div>
            <p style="margin:.6rem 0 0"><strong>${esc(p.venue_name)}</strong>, ${esc(p.city)}<br>
               <span class="muted">${esc(timeLabel(p.start_time))} to ${esc(timeLabel(p.end_time))}</span></p>
            ${p.description ? '<p class="small muted" style="margin:.4rem 0 0">' + esc(p.description) + '</p>' : ''}
            <details class="roster">
              <summary>Who's playing (${p.roster.length})</summary>
              ${p.roster.length
                ? '<ul>' + p.roster.map(r => '<li>' + esc(r.name) + (Number(r.slots) > 1 ? ' (' + Number(r.slots) + ' slots)' : '') + '</li>').join('') + '</ul>'
                : '<p class="small muted">No one has joined yet. Be the first.</p>'}
            </details>
          </div>
          <div>
            <div class="price">${esc(money(p.price))} <span class="small muted" style="font-family:Outfit,sans-serif;font-weight:400">per slot</span></div>
            <div class="slot-meter" aria-hidden="true"><span style="width:${Number(p.pct)}%"></span></div>
            <p class="small"><strong>${Number(p.left)}</strong> of ${Number(p.max_slots)} slots left</p>
            ${joinArea(p)}
          </div>
        </article>`).join('')}
    </section>`).join('');

  if (location.hash) { const el = document.querySelector(location.hash); if (el) el.scrollIntoView(); }
});
