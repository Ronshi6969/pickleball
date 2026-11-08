// venue_register.html: opening / closing hour lists; the form posts to actions/venue_register.php
PT.ready.then(session => {
  if (session.user) { location.replace('index.html'); return; }
  const form = document.getElementById('venue-form');
  document.getElementById('open').innerHTML = PT.hourOptions(0, 23, 6);
  document.getElementById('close').innerHTML = PT.hourOptions(1, 24, 22, true);
  PT.fillForm(form, session.old);
});
