// register.html: pickleball player registration form; posts to actions/register.php
PT.ready.then(session => {
  if (session.user) { location.replace('dashboard.html'); return; }
  const form = document.getElementById('register-form');
  PT.fillForm(form, session.old);
});
