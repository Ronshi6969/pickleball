// Play Together - shared front-end code. Plain JavaScript, no libraries.
// Every .html page loads this file first. It
//   1. asks api/session.php who is logged in,
//   2. builds the header, footer and flash messages,
//   3. puts the CSRF token into every form and refills a form after a failed submit,
//   4. offers helpers (PT.api, PT.esc, PT.money ...) to the page scripts in assets/js/pages/.
(function () {
  const root = document.body.dataset.root || '';   // '' for pages in the main folder, '../' for pages in /manager

  /* ---------- small helpers ---------- */
  const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const money = n => '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const hourLabel = h => {
    h = Number(h) % 24;
    return ((h + 11) % 12 + 1) + ':00 ' + (h >= 12 ? 'PM' : 'AM');
  };
  const timeLabel = t => {                       // "07:00:00.0000000" -> "7:00 AM"
    const [h, m] = String(t).split(':');
    const hh = Number(h);
    return ((hh + 11) % 12 + 1) + ':' + String(m).slice(0, 2) + ' ' + (hh >= 12 ? 'PM' : 'AM');
  };
  const dateLabel = d => {                       // "2026-11-08" -> "Sun, Nov 8, 2026"
    const [y, m, day] = String(d).slice(0, 10).split('-').map(Number);
    return new Date(y, m - 1, day).toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
  };
  const plural = (n, word) => n + ' ' + word + (Number(n) === 1 ? '' : 's');
  const closeText = h => Number(h) === 24 ? '12:00 AM' : hourLabel(h);
  const query = () => new URLSearchParams(location.search);

  // <img> if a photo exists, otherwise a coloured letter tile
  function media(path, alt, cls) {
    cls = cls || 'thumb';
    if (path) return '<img class="' + esc(cls) + '" src="' + esc(root + path) + '" alt="' + esc(alt) + '" loading="lazy">';
    return '<div class="' + esc(cls) + ' ph" role="img" aria-label="' + esc(alt) + '">' + esc(String(alt).charAt(0).toUpperCase()) + '</div>';
  }

  // <option> list of hours, e.g. hourOptions(0, 23, 6)
  function hourOptions(from, to, selected, midnightLabel) {
    let out = '';
    for (let h = from; h <= to; h++) {
      const text = (h === 24 && midnightLabel) ? '12:00 AM (midnight)' : hourLabel(h);
      out += '<option value="' + h + '"' + (Number(selected) === h ? ' selected' : '') + '>' + text + '</option>';
    }
    return out;
  }

  /* ---------- errors ---------- */
  function showError(message) {
    const main = document.querySelector('main');
    if (main) {
      main.innerHTML = '<div class="container band"><div class="empty"><h3>' + esc(message) + '</h3>' +
        '<p><a href="' + root + 'index.html">Back to home</a></p></div></div>';
    }
  }

  /* ---------- talking to the PHP files in api/ ---------- */
  async function api(path, params) {
    let qs = '';
    if (params) {
      const p = new URLSearchParams();
      Object.entries(params).forEach(([k, v]) => { if (v !== undefined && v !== null && v !== '' && v !== 0) p.append(k, v); });
      qs = p.toString() ? '?' + p.toString() : '';
    }
    let res;
    try {
      res = await fetch(root + 'api/' + path + qs, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
    } catch (e) {
      showError('Cannot reach the server. Is Apache running?');
      throw e;
    }
    let data = null;
    try { data = await res.json(); } catch (e) { /* not JSON */ }
    if (res.status === 401) {                    // not logged in: go to the login page and come back afterwards
      location.href = root + 'login.html?next=' + encodeURIComponent(location.pathname + location.search);
      return new Promise(() => {});
    }
    if (!res.ok || !data) {
      const msg = (data && data.error) || 'Something went wrong while loading this page.';
      showError(msg);
      throw new Error(msg);
    }
    return data;
  }

  // For pages only one kind of account may open. Sends guests to the login page (and back afterwards).
  function requireRole(session, role) {
    if (!session.user) {
      location.href = root + 'login.html?next=' + encodeURIComponent(location.pathname + location.search);
      return false;
    }
    if (role && session.user.role !== role) {
      showError('Your account type cannot open this page.');
      return false;
    }
    return true;
  }

  /* ---------- forms ---------- */
  // Refill a form with remembered input after the server rejected it. Keys = field names.
  function fillForm(form, data) {
    if (!form || !data) return;
    Object.keys(data).forEach(k => {
      const val = data[k];
      if (Array.isArray(val)) {
        const wanted = val.map(String);
        form.querySelectorAll('input[name="' + k + '[]"]').forEach(i => { i.checked = wanted.includes(i.value); });
        return;
      }
      const el = form.elements[k];
      if (el && el.type !== 'file' && !(el instanceof RadioNodeList)) el.value = val ?? '';
    });
  }

  /* ---------- header, footer, flash messages ---------- */
  function buildHeader(user) {
    const nav = document.body.dataset.nav || '';
    const on = key => (nav === key ? ' class="on"' : '');
    let links = '<a href="' + root + 'index.html"' + on('home') + '>Home</a>' +
                '<a href="' + root + 'venues.html"' + on('courts') + '>Courts</a>' +
                '<a href="' + root + 'openplays.html"' + on('open') + '>Open play</a>';
    if (user && user.role === 'manager') links += '<a href="' + root + 'manager/index.html"' + on('manager') + '>Manager</a>';
    else if (user) links += '<a href="' + root + 'dashboard.html"' + on('dash') + '>My plays</a>';
    if (user) {
      if (user.role === 'player') links += '<a href="' + root + 'profile.html"' + on('profile') + '>Profile</a>';
      links += '<a href="' + root + 'venues.html" class="btn btn-sm">Book a court <span class="btn-arrow">&rarr;</span></a>';
      links += '<a href="' + root + 'actions/logout.php" class="btn btn-sm btn-ghost">Log out</a>';
    } else {
      links += '<a href="' + root + 'login.html">Log in</a>' +
               '<a href="' + root + 'register.html" class="btn btn-sm btn-ghost">Join</a>' +
               '<a href="' + root + 'venues.html" class="btn btn-sm">Book a court <span class="btn-arrow">&rarr;</span></a>';
    }
    const header = document.getElementById('site-header');
    if (!header) return;
    header.innerHTML =
      '<div class="container bar">' +
        '<a class="logo" href="' + root + 'index.html">' +
          '<span class="logo-badge"><img src="' + root + 'assets/img/logo-mark.png" alt="Play Together Pickleball logo"></span>' +
          '<span class="logo-text"><span class="logo-title">Play Together</span><span class="logo-sub">Pickleball Club</span></span>' +
        '</a>' +
        '<button class="nav-toggle" type="button" aria-expanded="false" aria-controls="main-nav">Menu</button>' +
        '<nav id="main-nav" class="main-nav" aria-label="Main">' + links + '</nav>' +
      '</div>';
    const btn = header.querySelector('.nav-toggle');
    const navEl = header.querySelector('#main-nav');
    btn.addEventListener('click', () => {
      const open = navEl.classList.toggle('open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  function buildFooter() {
    const footer = document.getElementById('site-footer');
    if (!footer) return;
    footer.innerHTML =
      '<div class="container footer-grid">' +
        '<div class="footer-col">' +
          '<a class="logo" href="' + root + 'index.html">' +
            '<span class="logo-badge"><img src="' + root + 'assets/img/logo-mark.png" alt="Play Together Pickleball logo"></span>' +
            '<span class="logo-text"><span class="logo-title">Play Together</span><span class="logo-sub">Pickleball Only &middot; ESTD 2025</span></span>' +
          '</a>' +
        '</div>' +
        '<div class="footer-col">' +
          '<h4>Cavite Pickleball Hub</h4>' +
          '<p>Open Daily &middot; 6:00 AM &ndash; 11:00 PM<br>Dedicated indoor &amp; covered pickleball courts</p>' +
        '</div>' +
        '<div class="footer-col">' +
          '<div class="footer-slogan">' +
            '<strong>Dink Together.<br>Win Together.</strong>' +
            '<span class="footer-dots" aria-hidden="true"></span>' +
          '</div>' +
        '</div>' +
      '</div>';
  }

  function showFlashes(flashes) {
    const box = document.getElementById('flashes');
    if (!box || !flashes || !flashes.length) return;
    box.className = 'container flashes';
    box.setAttribute('role', 'status');
    box.innerHTML = flashes.map(f => '<div class="alert alert-' + esc(f.type) + '">' + esc(f.msg) + '</div>').join('');
  }

  // Manager sub-menu (only on pages that have <nav id="manager-nav">)
  function buildManagerNav() {
    const el = document.getElementById('manager-nav');
    if (!el) return;
    const items = { index: 'Overview', openplays: 'Open plays', courts: 'Courts', blocks: 'Blocked hours', bookings: 'Rentals', venue: 'Venue details' };
    const current = document.body.dataset.mnav || '';
    el.className = 'subnav container';
    el.setAttribute('aria-label', 'Manager');
    el.innerHTML = Object.keys(items).map(k => '<a href="' + root + 'manager/' + k + '.html" class="' + (current === k ? 'on' : '') + '">' + items[k] + '</a>').join('');
  }

  /* ---------- "Are you sure?" on any form with data-confirm ---------- */
  document.addEventListener('submit', e => {
    const msg = e.target.getAttribute && e.target.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) e.preventDefault();
  });

  /* ---------- start ---------- */
  const ready = api('session.php').then(session => {
    buildHeader(session.user);
    buildFooter();
    buildManagerNav();
    showFlashes(session.flashes);
    document.querySelectorAll('input[name="csrf"]').forEach(i => { i.value = session.csrf; });
    return session;
  });

  window.PT = { root, esc, money, hourLabel, timeLabel, dateLabel, plural, closeText, query, media, hourOptions, api, fillForm, showError, requireRole, ready };
})();
