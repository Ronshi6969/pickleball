// setup_check.html: shows the result of api/setup_check.php (PHP version, driver, SQL Server connection)
const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
fetch('api/setup_check.php')
  .then(r => r.json())
  .then(data => {
    if (data.error) { document.getElementById('checks').textContent = data.error; return; }
    document.getElementById('checks').innerHTML = data.checks.map(c => `
      <div class="row ${c.ok ? 'ok' : 'bad'}">
        <strong>${c.ok ? '&#10003;' : '&#10007;'} ${esc(c.label)}</strong><br>${esc(c.detail)}
        ${!c.ok && c.hint ? '<p class="hint"><strong>Fix:</strong> ' + esc(c.hint) + '</p>' : ''}
      </div>`).join('');
    if (data.checks.every(c => c.ok)) document.getElementById('done').hidden = false;
  })
  .catch(() => { document.getElementById('checks').textContent = 'Could not reach api/setup_check.php. Is Apache running and the folder in htdocs?'; });
