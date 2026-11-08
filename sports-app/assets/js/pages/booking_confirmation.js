// booking_confirmation.html
PT.ready.then(async () => {
  const { esc, money, dateLabel } = PT;
  const { booking: b } = await PT.api('booking.php', { ref: PT.query().get('ref') });
  document.getElementById('box').innerHTML = `
    <h1 style="font-size:2.4rem">You're booked!</h1>
    <p>Show this reference at the venue: <strong>${esc(b.reference)}</strong></p>
    <div class="summary">
      <dl>
        <dt>Court</dt><dd>${esc(b.court_name)}</dd>
        <dt>Venue</dt><dd>${esc(b.venue_name)}</dd>
        <dt>Date</dt><dd>${esc(dateLabel(b.booking_date))}</dd>
        <dt>Time</dt><dd>${esc(b.time_text)}</dd>
        <dt>Total</dt><dd>${esc(money(b.total_price))}</dd>
      </dl>
      <p class="small muted" style="margin:0">${esc(b.address)}, ${esc(b.city)}. Pay at the venue.</p>
    </div>
    <div class="actions" style="margin-top:1rem">
      <a class="btn" href="dashboard.html">View my bookings</a>
      <a class="btn btn-ghost" href="receipt.html?type=booking&id=${Number(b.id)}">Receipt</a>
    </div>`;
});
