// venues.html: pickleball court catalog with location search + branch location suggestions.
PT.ready.then(async () => {
  const { esc, money, hourLabel, closeText, media } = PT;
  const q = PT.query();
  const params = { location: (q.get('location') || '').trim() };
  const data = await PT.api('venues.php', params);

  const form = document.getElementById('search-form');
  const locInput = document.getElementById('location');
  const dropdown = document.getElementById('loc-dropdown');
  const chipsBox = document.getElementById('loc-suggestions');

  locInput.value = params.location;
  if (params.location) document.getElementById('clear-link').hidden = false;

  const locations = data.locations || [];
  const goLocation = city => {
    locInput.value = city;
    form.submit();
  };

  if (locations.length && chipsBox) {
    const activeLoc = params.location.toLowerCase();
    chipsBox.innerHTML =
      '<span class="loc-bar-label">Available branch locations:</span>' +
      `<button type="button" class="loc-chip ${!activeLoc ? 'on' : ''}" data-loc="">All branches</button>` +
      locations.map(l => {
        const isOn = activeLoc === String(l.city).toLowerCase();
        return `<button type="button" class="loc-chip ${isOn ? 'on' : ''}" data-loc="${esc(l.city)}">${esc(l.city)} <span class="loc-count">${Number(l.court_count)}</span></button>`;
      }).join('');
    chipsBox.querySelectorAll('.loc-chip').forEach(btn => {
      btn.addEventListener('click', () => goLocation(btn.dataset.loc || ''));
    });
  }

  function renderDropdown(filterText) {
    if (!dropdown || !locations.length) return;
    const term = (filterText || '').trim().toLowerCase();
    const matches = term
      ? locations.filter(l => l.city.toLowerCase().includes(term) || l.name.toLowerCase().includes(term))
      : locations;
    if (!matches.length) {
      dropdown.innerHTML = '<div class="loc-opt muted">No matching branch location</div>';
    } else {
      dropdown.innerHTML = matches.map(l =>
        `<button type="button" class="loc-opt" role="option" data-loc="${esc(l.city)}">
          <strong>${esc(l.city)}</strong>
          <span class="small muted">${PT.plural(Number(l.court_count), 'court')}</span>
        </button>`
      ).join('');
    }
    dropdown.hidden = false;
  }

  locInput.addEventListener('focus', () => renderDropdown(''));
  locInput.addEventListener('click', () => {
    if (dropdown && dropdown.hidden) renderDropdown('');
  });
  locInput.addEventListener('input', () => renderDropdown(locInput.value));
  locInput.addEventListener('keydown', e => {
    if (e.key === 'Escape' && dropdown) dropdown.hidden = true;
  });
  if (dropdown) {
    dropdown.addEventListener('mousedown', e => {
      const btn = e.target.closest('.loc-opt[data-loc]');
      if (!btn) return;
      e.preventDefault();
      goLocation(btn.dataset.loc);
    });
  }
  document.addEventListener('click', e => {
    if (dropdown && !e.target.closest('.loc-suggest-wrap')) dropdown.hidden = true;
  });

  const box = document.getElementById('results');
  if (!data.courts.length) {
    box.innerHTML = '<div class="empty"><h3>No pickleball courts match your search</h3><p>Try picking one of the branch locations above or clear the filters.</p></div>';
    return;
  }
  box.innerHTML = `<p class="muted">${PT.plural(data.courts.length, 'pickleball court')} found</p><div class="grid">` + data.courts.map(c => `
    <article class="card">
      ${media(c.photo, c.venue_name, 'thumb', c.city)}
      <div class="card-body">
        <h3>${esc(c.name)}</h3>
        <div><strong>${esc(c.venue_name)}</strong></div>
        <div class="small muted">${esc(c.address)}</div>
        <div class="small muted">Open ${esc(hourLabel(c.opening_hour))} to ${esc(closeText(c.closing_hour))}</div>
        <div><span class="badge">${esc(c.city)}</span> <span class="badge">Pickleball</span> <span class="price">${esc(money(c.hourly_rate))}</span><span class="small muted"> per hour</span></div>
        <a class="btn btn-ink" href="court.html?id=${Number(c.id)}">Check times</a>
      </div>
    </article>`).join('') + '</div>';
});
