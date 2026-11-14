// venues.html: pickleball court catalog with search (name, location). The search form is a plain GET form.
PT.ready.then(async () => {
  const { esc, money, hourLabel, closeText, media } = PT;
  const q = PT.query();
  const params = { q: (q.get('q') || '').trim(), location: (q.get('location') || '').trim() };
  const data = await PT.api('venues.php', params);

  document.getElementById('q').value = params.q;
  document.getElementById('location').value = params.location;
  if (params.q || params.location) document.getElementById('clear-link').hidden = false;

  const box = document.getElementById('results');
  if (!data.courts.length) {
    box.innerHTML = '<div class="empty"><h3>No pickleball courts match your search</h3><p>Try a shorter name or clear the filters.</p></div>';
    return;
  }
  box.innerHTML = `<p class="muted">${PT.plural(data.courts.length, 'pickleball court')} found</p><div class="grid">` + data.courts.map(c => `
    <article class="card">
      ${media(c.photo, c.venue_name)}
      <div class="card-body">
        <h3>${esc(c.name)}</h3>
        <div><strong>${esc(c.venue_name)}</strong></div>
        <div class="small muted">${esc(c.address)}, ${esc(c.city)}</div>
        <div class="small muted">Open ${esc(hourLabel(c.opening_hour))} to ${esc(closeText(c.closing_hour))}</div>
        <div><span class="badge">Pickleball</span> <span class="price">${esc(money(c.hourly_rate))}</span><span class="small muted"> per hour</span></div>
        <a class="btn btn-ink" href="court.html?id=${Number(c.id)}">Check times <span class="btn-arrow">&rarr;</span></a>
      </div>
    </article>`).join('') + '</div>';
});
