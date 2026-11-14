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
      links += '<a href="' + root + 'actions/logout.php" class="btn btn-sm btn-ghost" data-logout>Log out</a>';
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

  /* ---------- Custom HTML/CSS Confirmation Modal (for forms & logout) ---------- */
  function showConfirm(opts) {
    const {
      kicker = 'Confirm Action',
      title = 'Are you sure?',
      message = '',
      confirmText = 'Confirm',
      cancelText = 'Cancel',
      danger = true,
      onConfirm = () => {}
    } = opts || {};
    const backdrop = document.createElement('div');
    backdrop.className = 'confirm-backdrop';
    backdrop.innerHTML =
      '<div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="confirm-title">' +
        '<div class="kicker">' + esc(kicker) + '</div>' +
        '<h2 id="confirm-title">' + esc(title) + '</h2>' +
        '<p>' + esc(message) + '</p>' +
        '<div class="confirm-actions">' +
          '<button type="button" class="btn btn-sm btn-ghost" data-cancel>' + esc(cancelText) + '</button>' +
          '<button type="button" class="btn btn-sm ' + (danger ? 'btn-danger' : 'btn-ink') + '" data-ok>' + esc(confirmText) + '</button>' +
        '</div>' +
      '</div>';
    const close = () => {
      document.removeEventListener('keydown', onKey);
      backdrop.remove();
    };
    const onKey = e => { if (e.key === 'Escape') close(); };
    backdrop.addEventListener('click', e => {
      if (e.target === backdrop || e.target.closest('[data-cancel]')) { close(); return; }
      if (e.target.closest('[data-ok]')) { close(); onConfirm(); }
    });
    document.addEventListener('keydown', onKey);
    document.body.appendChild(backdrop);
    const okBtn = backdrop.querySelector('[data-ok]');
    if (okBtn) okBtn.focus();
  }

  document.addEventListener('submit', e => {
    const form = e.target;
    if (!form || !form.getAttribute) return;
    if (form.dataset.confirmed === '1') {
      form.dataset.confirmed = '';
      return;
    }
    const msg = form.getAttribute('data-confirm');
    if (!msg) return;
    e.preventDefault();

    let message = msg;
    const slotSel = form.querySelector('select[name="slots"]');
    if (slotSel && form.getAttribute('data-confirm-slots') === '1') {
      message = msg.replace('{slots}', plural(slotSel.value, 'slot'));
    }

    showConfirm({
      kicker: form.getAttribute('data-confirm-kicker') || 'Confirm Action',
      title: form.getAttribute('data-confirm-title') || 'Are you sure?',
      message,
      confirmText: form.getAttribute('data-confirm-btn') || 'Confirm',
      cancelText: form.getAttribute('data-confirm-cancel') || 'Back',
      danger: form.getAttribute('data-confirm-kind') !== 'primary',
      onConfirm: () => {
        form.dataset.confirmed = '1';
        form.submit();
      }
    });
  });

  document.addEventListener('click', e => {
    const link = e.target.closest && e.target.closest('[data-logout]');
    if (!link) return;
    e.preventDefault();
    const href = link.getAttribute('href');
    showConfirm({
      kicker: 'Confirm Action',
      title: 'Log out?',
      message: 'Are you sure you want to log out of your Play Together Pickleball account?',
      confirmText: 'Yes, log out',
      cancelText: 'Cancel',
      danger: true,
      onConfirm: () => { location.href = href; }
    });
  });

  /* ---------- Show / Hide Password Toggle ---------- */
  function initPasswordToggles() {
    const eyeOpen = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
    const eyeClosed = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
    document.querySelectorAll('input[type="password"]').forEach(input => {
      if (input.closest('.pw-wrap')) return;
      const wrap = document.createElement('div');
      wrap.className = 'pw-wrap';
      input.parentNode.insertBefore(wrap, input);
      wrap.appendChild(input);

      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'pw-toggle';
      btn.setAttribute('aria-label', 'Show password');
      btn.setAttribute('aria-pressed', 'false');
      btn.innerHTML = eyeOpen;
      btn.addEventListener('click', () => {
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.setAttribute('aria-pressed', show ? 'true' : 'false');
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        btn.innerHTML = show ? eyeClosed : eyeOpen;
      });
      wrap.appendChild(btn);
    });
  }

  /* ---------- Interactive Image Cropper & Adjuster ---------- */
  function attachCropper(inputEl, opts) {
    if (!inputEl) return;
    const { aspect = 1, circle = true, outputWidth = 500, previewEl = null, previewClass = 'avatar' } = opts || {};
    inputEl.addEventListener('change', () => {
      const file = inputEl.files && inputEl.files[0];
      if (!file || !file.type.startsWith('image/')) return;
      const reader = new FileReader();
      reader.onload = ev => {
        const img = new Image();
        img.onload = () => openCropModal(img, file);
        img.src = ev.target.result;
      };
      reader.readAsDataURL(file);
    });

    function openCropModal(img, origFile) {
      const boxW = 360;
      const boxH = Math.round(boxW / aspect);
      const outW = outputWidth;
      const outH = Math.round(outW / aspect);

      let rotation = 0;
      let zoom = 1;
      let offsetX = 0;
      let offsetY = 0;

      const backdrop = document.createElement('div');
      backdrop.className = 'confirm-backdrop';
      backdrop.innerHTML =
        '<div class="crop-modal" role="dialog" aria-modal="true" aria-labelledby="crop-title">' +
          '<div class="kicker">Adjust &amp; Crop Photo</div>' +
          '<h2 id="crop-title">Frame your photo</h2>' +
          '<p class="small muted" style="margin-bottom:.5rem">Drag the photo to reposition, and use the slider to zoom.</p>' +
          '<div class="crop-stage"><canvas width="' + boxW + '" height="' + boxH + '"></canvas></div>' +
          '<div class="crop-controls">' +
            '<label for="crop-zoom">Zoom</label>' +
            '<input id="crop-zoom" type="range" min="1" max="3" step="0.02" value="1">' +
            '<button type="button" class="btn btn-sm btn-ghost" data-rotate>Rotate 90&deg;</button>' +
          '</div>' +
          '<div class="confirm-actions">' +
            '<button type="button" class="btn btn-sm btn-ghost" data-cancel>Cancel</button>' +
            '<button type="button" class="btn btn-sm btn-ink" data-apply>Apply crop</button>' +
          '</div>' +
        '</div>';
      document.body.appendChild(backdrop);

      const canvas = backdrop.querySelector('canvas');
      const ctx = canvas.getContext('2d');
      const zoomInput = backdrop.querySelector('#crop-zoom');

      function getBaseScale() {
        const swapped = rotation % 180 !== 0;
        const iw = swapped ? img.height : img.width;
        const ih = swapped ? img.width : img.height;
        return Math.max(boxW / iw, boxH / ih);
      }

      function clampOffsets() {
        const swapped = rotation % 180 !== 0;
        const iw = swapped ? img.height : img.width;
        const ih = swapped ? img.width : img.height;
        const scale = getBaseScale() * zoom;
        const maxX = Math.max(0, (iw * scale - boxW) / 2);
        const maxY = Math.max(0, (ih * scale - boxH) / 2);
        offsetX = Math.max(-maxX, Math.min(maxX, offsetX));
        offsetY = Math.max(-maxY, Math.min(maxY, offsetY));
      }

      function draw(targetCtx, w, h, withOverlay) {
        targetCtx.clearRect(0, 0, w, h);
        targetCtx.save();
        targetCtx.fillStyle = '#050806';
        targetCtx.fillRect(0, 0, w, h);
        const ratio = w / boxW;
        const scale = getBaseScale() * zoom * ratio;
        targetCtx.translate(w / 2 + offsetX * ratio, h / 2 + offsetY * ratio);
        targetCtx.rotate((rotation * Math.PI) / 180);
        targetCtx.drawImage(img, (-img.width * scale) / 2, (-img.height * scale) / 2, img.width * scale, img.height * scale);
        targetCtx.restore();

        if (withOverlay) {
          targetCtx.save();
          targetCtx.fillStyle = 'rgba(5, 8, 6, 0.58)';
          targetCtx.beginPath();
          targetCtx.rect(0, 0, w, h);
          if (circle) {
            const r = Math.min(w, h) * 0.46;
            targetCtx.arc(w / 2, h / 2, r, 0, Math.PI * 2, true);
          } else {
            const pad = 10;
            targetCtx.rect(pad, pad, w - pad * 2, h - pad * 2);
          }
          targetCtx.fill();

          targetCtx.strokeStyle = '#c8ff1a';
          targetCtx.lineWidth = 2.5;
          targetCtx.beginPath();
          if (circle) {
            targetCtx.arc(w / 2, h / 2, Math.min(w, h) * 0.46, 0, Math.PI * 2);
          } else {
            targetCtx.rect(10, 10, w - 20, h - 20);
          }
          targetCtx.stroke();
          targetCtx.restore();
        }
      }

      function render() {
        clampOffsets();
        draw(ctx, boxW, boxH, true);
      }
      render();

      let dragging = false;
      let startX = 0;
      let startY = 0;
      canvas.addEventListener('pointerdown', e => {
        dragging = true;
        startX = e.clientX;
        startY = e.clientY;
        canvas.setPointerCapture(e.pointerId);
      });
      canvas.addEventListener('pointermove', e => {
        if (!dragging) return;
        const rect = canvas.getBoundingClientRect();
        const scalePx = boxW / rect.width;
        offsetX += (e.clientX - startX) * scalePx;
        offsetY += (e.clientY - startY) * scalePx;
        startX = e.clientX;
        startY = e.clientY;
        render();
      });
      const stopDrag = () => { dragging = false; };
      canvas.addEventListener('pointerup', stopDrag);
      canvas.addEventListener('pointercancel', stopDrag);

      zoomInput.addEventListener('input', () => {
        zoom = parseFloat(zoomInput.value);
        render();
      });

      backdrop.querySelector('[data-rotate]').addEventListener('click', () => {
        rotation = (rotation + 90) % 360;
        offsetX = 0;
        offsetY = 0;
        render();
      });

      const close = (keep) => {
        if (!keep) inputEl.value = '';
        document.removeEventListener('keydown', onKey);
        backdrop.remove();
      };
      const onKey = e => { if (e.key === 'Escape') close(false); };
      document.addEventListener('keydown', onKey);

      backdrop.addEventListener('click', e => {
        if (e.target === backdrop || e.target.closest('[data-cancel]')) close(false);
      });

      backdrop.querySelector('[data-apply]').addEventListener('click', () => {
        const outCanvas = document.createElement('canvas');
        outCanvas.width = outW;
        outCanvas.height = outH;
        const outCtx = outCanvas.getContext('2d');
        draw(outCtx, outW, outH, false);

        const mime = origFile.type === 'image/png' ? 'image/png' : 'image/jpeg';
        const ext = mime === 'image/png' ? 'png' : 'jpg';
        outCanvas.toBlob(blob => {
          if (!blob) { close(false); return; }
          const croppedFile = new File([blob], 'cropped.' + ext, { type: mime });
          if (typeof DataTransfer !== 'undefined') {
            const dt = new DataTransfer();
            dt.items.add(croppedFile);
            inputEl.files = dt.files;
          }
          if (previewEl) {
            const url = URL.createObjectURL(croppedFile);
            previewEl.innerHTML = '<img class="' + esc(previewClass) + '" src="' + esc(url) + '" alt="Cropped preview">';
          }
          close(true);
        }, mime, 0.92);
      });
    }
  }

  /* ---------- Selective scroll reveal (used on landing page cards only) ---------- */
  function reveal(selector) {
    const els = Array.from(document.querySelectorAll(selector));
    if (!els.length) return;
    if (!('IntersectionObserver' in window)) {
      els.forEach(el => el.classList.add('in'));
      return;
    }
    const io = new IntersectionObserver(entries => {
      entries.forEach(en => {
        if (!en.isIntersecting) return;
        en.target.classList.add('in');
        io.unobserve(en.target);
      });
    }, { threshold: 0.12 });
    els.forEach((el, idx) => {
      el.classList.add('reveal');
      el.style.transitionDelay = Math.min(idx * 65, 260) + 'ms';
      io.observe(el);
    });
  }

  /* ---------- start ---------- */
  initPasswordToggles();
  const ready = api('session.php').then(session => {
    buildHeader(session.user);
    buildFooter();
    buildManagerNav();
    showFlashes(session.flashes);
    document.querySelectorAll('input[name="csrf"]').forEach(i => { i.value = session.csrf; });
    return session;
  });

  window.PT = { root, esc, money, hourLabel, timeLabel, dateLabel, plural, closeText, query, media, hourOptions, api, fillForm, showError, requireRole, reveal, showConfirm, attachCropper, ready };
})();
