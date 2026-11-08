// court.html: court details + date picker -> api/availability.php -> choose one continuous block of hours.
// The server does NOT trust this page: actions/book.php validates everything again.
PT.ready.then(async session => {
  const { esc, money, hourLabel, closeText, media } = PT;
  const id = PT.query().get('id');
  const { court } = await PT.api('court.php', { id });

  document.title = court.name + ' | Play Together';
  document.getElementById('court-info').innerHTML = `
    ${media(court.photo, court.venue_name)}
    <h1 style="margin-top:1rem;font-size:2.6rem">${esc(court.name)}</h1>
    <p><strong>${esc(court.venue_name)}</strong><br>${esc(court.address)}, ${esc(court.city)}</p>
    <p><span class="badge">${esc(court.sport)}</span>
       <span class="price">${esc(money(court.hourly_rate))}</span><span class="small muted"> per hour</span></p>
    ${court.description ? '<p>' + esc(court.description) + '</p>' : ''}
    <p class="small muted">Hours: ${esc(hourLabel(court.opening_hour))} to ${esc(closeText(court.closing_hour))}. Book one continuous block of hours.</p>`;

  // booking button depends on who is logged in
  const user = session.user;
  const canBook = !!user && user.role === 'player';
  const action = document.getElementById('book-action');
  if (canBook) {
    action.innerHTML = '<button class="btn btn-block" type="submit" id="confirm-btn" disabled>Confirm booking</button>';
  } else if (user) {
    action.innerHTML = '<p class="small">Venue manager accounts cannot book courts. Log in with a player account.</p>' +
                       '<button class="btn btn-block" type="button" id="confirm-btn" disabled>Confirm booking</button>';
  } else {
    action.innerHTML = '<a class="btn btn-block btn-ink" href="login.html?next=' + encodeURIComponent(location.pathname + location.search) + '">Log in to book</a>' +
                       '<button type="button" id="confirm-btn" hidden disabled></button>';
  }

  const rate = parseFloat(court.hourly_rate);
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
  document.getElementById('f-court').value = court.id;
  document.getElementById('sum-court').textContent = court.name;

  let wanted = PT.query().get('date') || session.today;
  if (wanted < session.today) wanted = session.today;
  dateInput.min = session.today;
  dateInput.value = wanted;

  let slots = [];
  let selected = [];   // sorted list of chosen hours (always one continuous block)
  const label = { open: 'Open', booked: 'Booked', blocked: 'Unavailable', past: 'Past' };

  function loadSlots() {
    status.textContent = 'Loading times...';
    selected = [];
    fetch(PT.root + 'api/availability.php?court_id=' + encodeURIComponent(court.id) + '&date=' + encodeURIComponent(dateInput.value), { credentials: 'same-origin' })
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
      b.innerHTML = esc(s.label) + '<small>' + (selected.includes(s.hour) ? 'Selected' : label[s.status]) + '</small>';
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
      sumTime.textContent = '-'; sumHours.textContent = '0'; sumTotal.textContent = money(0);
      confirmBtn.disabled = true;
      return;
    }
    const start = selected[0], end = selected[n - 1] + 1;
    sumTime.textContent = hourLabel(start) + ' to ' + hourLabel(end % 24);
    sumHours.textContent = n;
    sumTotal.textContent = money(n * rate);
    fDate.value = dateInput.value; fStart.value = start; fEnd.value = end;
    confirmBtn.disabled = !canBook;
  }

  dateInput.addEventListener('change', loadSlots);
  form.addEventListener('submit', e => { if (!selected.length) e.preventDefault(); });
  loadSlots();
});
