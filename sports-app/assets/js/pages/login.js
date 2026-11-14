// login.html: the form posts to actions/login.php. Supports Player vs Admin selection + remembered email/role.
PT.ready.then(session => {
  if (session.user) { location.replace(session.user.role === 'manager' ? 'manager/index.html' : 'dashboard.html'); return; }
  const form = document.getElementById('login-form');
  const next = PT.query().get('next');
  if (next) form.action = 'actions/login.php?next=' + encodeURIComponent(next);

  const submitBtn = document.getElementById('login-submit');
  const radios = form.querySelectorAll('input[name="login_role"]');
  function syncRoleLabel() {
    const checked = form.querySelector('input[name="login_role"]:checked');
    const isAdmin = checked && checked.value === 'manager';
    if (submitBtn) submitBtn.innerHTML = (isAdmin ? 'Log in as Admin' : 'Log in as Player') + ' <span class="btn-arrow">&rarr;</span>';
  }
  radios.forEach(r => r.addEventListener('change', syncRoleLabel));

  if (session.old && session.old.login_role) {
    const target = form.querySelector('input[name="login_role"][value="' + session.old.login_role + '"]');
    if (target) target.checked = true;
  }
  PT.fillForm(form, session.old);
  syncRoleLabel();
});
