// profile.html: fill the form; it posts to actions/profile_save.php
PT.ready.then(async session => {
  if (!PT.requireRole(session, 'player')) return;
  const user = session.user;
  const data = await PT.api('profile.php');
  const form = document.getElementById('profile-form');

  document.getElementById('avatar-box').innerHTML = PT.media(user.avatar, user.name, 'avatar');
  form.elements.name.value = user.name;
  form.elements.skill.value = user.skill_level;
  document.getElementById('sports-box').innerHTML = data.sports.map(s =>
    `<label><input type="checkbox" name="sports[]" value="${Number(s.id)}"${data.mine.includes(Number(s.id)) ? ' checked' : ''}><span>${PT.esc(s.name)}</span></label>`).join('');
  PT.fillForm(form, session.old);
});
