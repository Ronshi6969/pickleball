// manager/venue.html: venue details + photo. The form posts to actions/manager_venue.php
PT.ready.then(async session => {
  const { venue } = await PT.api('manager/venue.php');
  const form = document.getElementById('venue-form');
  document.getElementById('photo-box').innerHTML = PT.media(venue.photo, venue.name);
  document.getElementById('open').innerHTML = PT.hourOptions(0, 23, venue.opening_hour);
  document.getElementById('close').innerHTML = PT.hourOptions(1, 24, venue.closing_hour, true);
  PT.fillForm(form, { name: venue.name, address: venue.address, city: venue.city, description: venue.description || '' });
  PT.fillForm(form, session.old);   // after a failed save, show what was typed
});
