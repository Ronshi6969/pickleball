// manager/index.html: venue summary + numbers
PT.ready.then(async () => {
  const { esc, media } = PT;
  const { venue, stats } = await PT.api('manager/index.php');
  document.getElementById('hero').innerHTML = `
    ${media(venue.photo, venue.name, 'thumb')}
    <div>
      <h1>${esc(venue.name)}</h1>
      <p>${esc(venue.address)}, ${esc(venue.city)}</p>
      <a class="btn btn-ink" href="venue.html">Change venue photo</a>
    </div>`;
  document.getElementById('stats').innerHTML = `
    <div class="stat"><b>${stats.courts}</b>Active courts</div>
    <div class="stat"><b>${stats.upBook}</b>Upcoming rentals</div>
    <div class="stat"><b>${stats.upPlays}</b>Upcoming open plays</div>
    <div class="stat"><b>${stats.players}</b>Open play slots taken</div>`;
});
