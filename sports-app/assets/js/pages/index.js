// index.html: open plays + featured courts + sign-up banner
PT.ready.then(async session => {
  const { esc, money, dateLabel, timeLabel, media } = PT;
  const data = await PT.api('home.php');

  const plays = document.getElementById('plays-box');
  if (!data.plays.length) {
    plays.innerHTML = '<div class="empty">No open plays are scheduled yet. Check back soon.</div>';
  } else {
    plays.innerHTML = '<div class="grid">' + data.plays.map(p => `
      <article class="card flat">
        <div class="card-body">
          <h3>${esc(p.title)}</h3>
          <div><span class="badge">${esc(p.sport)}</span><span class="badge">${p.skill_level === 'all' ? 'All levels' : esc(p.skill_level.charAt(0).toUpperCase() + p.skill_level.slice(1))}</span></div>
          <div>${esc(p.venue_name)} &middot; ${PT.plural(p.courts_used, 'court')}</div>
          <div><strong>${esc(dateLabel(p.play_date))}</strong></div>
          <div>${esc(timeLabel(p.start_time))} to ${esc(timeLabel(p.end_time))}</div>
          <div class="price">${esc(money(p.price))} <span class="small muted">per slot</span></div>
          <div class="small">${Number(p.slots_left) > 0 ? Number(p.slots_left) + ' slots left' : 'Full'}</div>
          <a class="btn btn-ink" href="openplays.html?date=${esc(p.play_date)}#play-${Number(p.id)}">Join game</a>
        </div>
      </article>`).join('') + '</div>';
  }

  document.getElementById('featured-box').innerHTML = data.featured.map(c => `
    <article class="card">
      ${media(c.photo, c.venue_name)}
      <div class="card-body">
        <h3>${esc(c.name)}</h3>
        <div>${esc(c.venue_name)}, ${esc(c.city)}</div>
        <div><span class="badge">${esc(c.sport)}</span> <span class="price">${esc(money(c.hourly_rate))}</span><span class="small muted"> per hour</span></div>
        <a class="btn btn-ink" href="court.html?id=${Number(c.id)}">Check times</a>
      </div>
    </article>`).join('');

  if (!session.user) document.getElementById('cta').hidden = false;
});
