// login.html: the form posts to actions/login.php. This only fills the "next" page and remembered email.
PT.ready.then(session => {
  if (session.user) { location.replace(session.user.role === 'manager' ? 'manager/index.html' : 'dashboard.html'); return; }
  const form = document.getElementById('login-form');
  const next = PT.query().get('next');
  if (next) form.action = 'actions/login.php?next=' + encodeURIComponent(next);
  PT.fillForm(form, session.old);
});
