// profile.html: fill the form; it posts to actions/profile_save.php (Pickleball only)
PT.ready.then(async session => {
  if (!PT.requireRole(session, 'player')) return;
  const user = session.user;
  await PT.api('profile.php');
  const form = document.getElementById('profile-form');

  document.getElementById('avatar-box').innerHTML = PT.media(user.avatar, user.name, 'avatar');
  form.elements.name.value = user.name;
  form.elements.skill.value = user.skill_level;
  PT.fillForm(form, session.old);

  PT.attachCropper(document.getElementById('avatar'), {
    aspect: 1,
    circle: true,
    outputWidth: 500,
    previewEl: document.getElementById('avatar-box'),
    previewClass: 'avatar'
  });
});
