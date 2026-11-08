// register.html: list the sports as checkboxes; the form posts to actions/register.php
PT.ready.then(async session => {
  if (session.user) { location.replace('dashboard.html'); return; }
  const { sports } = await PT.api('sports.php');
  const form = document.getElementById('register-form');
  document.getElementById('sports-box').innerHTML = sports.map(s =>
    `<label><input type="checkbox" name="sports[]" value="${Number(s.id)}"><span>${PT.esc(s.name)}</span></label>`).join('');
  PT.fillForm(form, session.old);
});
