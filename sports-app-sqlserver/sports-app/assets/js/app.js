// Play Together front-end behaviour. Plain JavaScript, no libraries.

/* 1. Mobile menu toggle */
(function () {
  const btn = document.querySelector('.nav-toggle');
  const nav = document.getElementById('main-nav');
  if (!btn || !nav) return;
  btn.addEventListener('click', function () {
    const open = nav.classList.toggle('open');
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
})();

/* 2. "Are you sure?" on any form with data-confirm */
document.addEventListener('submit', function (e) {
  const msg = e.target.getAttribute('data-confirm');
  if (msg && !window.confirm(msg)) e.preventDefault();
});

/* 3. Court booking: date picker -> fetch availability -> choose a range of hours */
(function () {
  const root = document.getElementById('booking-app');
  if (!root) return;

  const courtId = root.dataset.court;
  const rate = parseFloat(root.dataset.rate);
  const canBook = root.dataset.canbook === '1';
  const dateInput = document.getElementById('book-date');
  const grid = document.getElementById('slot-grid');
  const status = document.getElementById('slot-status');
  const form = document.getElementById('book-form');
  const fDate = document.getElementById('f-date');
  const fStart = document.getElementById('f-start');
  const fEnd = document.getElementById('f-end');
  const sumDate = document.getElementById('sum-date');
  const sumTime = document.getElementById('sum-time');
  const sumHours = document.getElementById('sum-hours');
  const sumTotal = document.getElementById('sum-total');
  const confirmBtn = document.getElementById('confirm-btn');

  let slots = [];
  let selected = [];   // sorted list of chosen hours (always one continuous block)

  const peso = n => '₱' + n.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const hourText = h => {
    const ampm = h >= 12 && h < 24 ? 'PM' : 'AM';
    return ((h + 11) % 12 + 1) + ':00 ' + ampm;
  };
  const label = { open: 'Open', booked: 'Booked', blocked: 'Unavailable', past: 'Past' };

  function loadSlots() {
    status.textContent = 'Loading times...';
    selected = [];
    fetch(root.dataset.api + '?court_id=' + encodeURIComponent(courtId) + '&date=' + encodeURIComponent(dateInput.value))
      .then(r => r.json())
      .then(data => { slots = data.slots || []; render(); })
      .catch(() => { status.textContent = 'Could not load times. Check your connection and pick the date again.'; });
  }

  function render() {
    grid.innerHTML = '';
    if (!slots.length) { status.textContent = 'No times to show for this date.'; }
    else {
      const open = slots.filter(s => s.status === 'open').length;
      status.textContent = open + ' of ' + slots.length + ' hours open. Tap hours to select a continuous block.';
    }
    slots.forEach(s => {
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'slot' + (selected.includes(s.hour) ? ' selected' : '');
      b.disabled = s.status !== 'open';
      b.innerHTML = s.label + '<small>' + (selected.includes(s.hour) ? 'Selected' : label[s.status]) + '</small>';
      b.addEventListener('click', () => toggle(s.hour));
      grid.appendChild(b);
    });
    updateSummary();
  }

  // Keep the selection one continuous block of open hours
  function toggle(h) {
    if (selected.includes(h)) {
      // clicking an end hour shrinks the block; clicking the middle restarts at that hour
      if (h === selected[0] || h === selected[selected.length - 1]) selected = selected.filter(x => x !== h);
      else selected = [h];
    } else if (selected.length && (h === selected[0] - 1 || h === selected[selected.length - 1] + 1)) {
      selected = selected.concat(h).sort((a, b) => a - b);
    } else {
      selected = [h];
    }
    render();
  }

  function updateSummary() {
    const n = selected.length;
    sumDate.textContent = dateInput.value || '-';
    if (!n) {
      sumTime.textContent = '-'; sumHours.textContent = '0'; sumTotal.textContent = peso(0);
      confirmBtn.disabled = true;
      return;
    }
    const start = selected[0], end = selected[n - 1] + 1;
    sumTime.textContent = hourText(start) + ' to ' + hourText(end % 24);
    sumHours.textContent = n;
    sumTotal.textContent = peso(n * rate);
    fDate.value = dateInput.value; fStart.value = start; fEnd.value = end;
    confirmBtn.disabled = !canBook;
  }

  dateInput.addEventListener('change', loadSlots);
  form.addEventListener('submit', function (e) { if (!selected.length) e.preventDefault(); });
  loadSlots();
})();
