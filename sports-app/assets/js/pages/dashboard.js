// dashboard.html: player's upcoming / past court rentals and open plays
PT.ready.then(async session => {
  const { esc, money, dateLabel, timeLabel, media } = PT;
  if (!PT.requireRole(session, 'player')) return;
  const user = session.user;
  const tab = PT.query().get('tab') === 'history' ? 'history' : 'upcoming';
  const data = await PT.api('dashboard.php', { tab });
  const up = tab === 'upcoming';

  document.getElementById('hero').innerHTML = `
    ${media(user.avatar, user.name, 'avatar')}
    <div>
      <div class="kicker">Pickleball Player</div>
      <h1>Hello, ${esc(user.name.split(' ')[0])}!</h1>
      <p class="muted">${esc(user.name)} &middot; ${esc(user.skill_level.charAt(0).toUpperCase() + user.skill_level.slice(1))} Pickleball Player</p>
      <div class="actions">
        <a class="btn btn-ink" href="profile.html">Edit profile</a>
        <a class="btn btn-ghost" href="openplays.html">Find a game</a>
        <a class="btn btn-ghost" href="venues.html">Rent a court</a>
      </div>
    </div>`;

  const bookings = data.bookings.length ? data.bookings.map(b => `
    <div class="list-item">
      <div>
        <h3>${esc(b.court_name)} <span class="small muted">at ${esc(b.venue_name)}</span></h3>
        <div><strong>${esc(dateLabel(b.booking_date))}</strong> &middot; ${esc(b.time_text)}</div>
        <div class="small muted">Ref ${esc(b.reference)} &middot; ${esc(money(b.total_price))}</div>
      </div>
      <div class="actions">
        ${b.status === 'cancelled' ? '<span class="badge badge-bad">Cancelled</span>' : ''}
        <a class="btn btn-sm btn-ghost" href="receipt.html?type=booking&id=${Number(b.id)}">Receipt</a>
        ${b.cancellable ? `<form method="post" action="actions/cancel_booking.php" class="inline-form"
          data-confirm="Are you sure you want to cancel your booking for ${esc(b.court_name)} at ${esc(b.venue_name)}?"
          data-confirm-kicker="Cancel Booking"
          data-confirm-title="Cancel booking?"
          data-confirm-btn="Yes, cancel booking"
          data-confirm-cancel="Keep booking">
          <input type="hidden" name="csrf" value="${esc(session.csrf)}"><input type="hidden" name="id" value="${Number(b.id)}">
          <button class="btn btn-sm btn-danger" type="submit">Cancel</button></form>` : ''}
      </div>
    </div>`).join('')
    : `<div class="empty">${up ? 'No court bookings yet. <a href="venues.html">Browse courts</a>.' : 'No past court rentals yet.'}</div>`;

  const plays = data.plays.length ? data.plays.map(p => `
    <div class="list-item">
      <div>
        <h3>${esc(p.title)} <span class="small muted">at ${esc(p.venue_name)}</span></h3>
        <div><strong>${esc(dateLabel(p.play_date))}</strong> &middot; ${esc(timeLabel(p.start_time))} to ${esc(timeLabel(p.end_time))}</div>
        <div class="small muted">${PT.plural(Number(p.slots), 'slot')} &middot; ${esc(money(p.amount))}</div>
      </div>
      <div class="actions">
        ${p.status === 'cancelled' ? '<span class="badge badge-bad">Left</span>' : ''}
        <a class="btn btn-sm btn-ghost" href="receipt.html?type=openplay&id=${Number(p.reg_id)}">Receipt</a>
        ${up ? `<a class="btn btn-sm btn-ghost" href="openplays.html?date=${esc(p.play_date)}#play-${Number(p.play_id)}">View</a>` : ''}
      </div>
    </div>`).join('')
    : `<div class="empty">${up ? 'You have not joined any open play. <a href="openplays.html">See upcoming games</a>.' : 'No past open plays yet.'}</div>`;

  document.getElementById('content').innerHTML = `
    <div class="tabs">
      <a href="?tab=upcoming" class="${up ? 'on' : ''}">Upcoming</a>
      <a href="?tab=history" class="${up ? '' : 'on'}">Past activity</a>
    </div>
    <h2>${up ? 'Reserved courts' : 'Past court rentals'}</h2>
    ${bookings}
    <h2 style="margin-top:2rem">${up ? 'Joined open plays' : 'Past open plays'}</h2>
    ${plays}`;
});
