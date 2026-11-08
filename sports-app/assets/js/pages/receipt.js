// receipt.html: printable receipt for a court booking or an open play
PT.ready.then(async session => {
  const { esc, money, dateLabel } = PT;
  const q = PT.query();
  const { receipt: r } = await PT.api('receipt.php', { type: q.get('type'), id: q.get('id') });
  document.getElementById('receipt').innerHTML = `
    <h1>Play Together</h1>
    <p class="muted">Receipt ${esc(r.reference)}<br>Issued ${esc(r.issued_text)}</p>
    <dl>
      <dt>Player</dt><dd>${esc(session.user.name)}</dd>
      <dt>${r.type === 'booking' ? 'Court' : 'Open play'}</dt><dd>${esc(r.item)}</dd>
      <dt>Venue</dt><dd>${esc(r.venue)}</dd>
      <dt>Date</dt><dd>${esc(dateLabel(r.d))}</dd>
      <dt>Time</dt><dd>${esc(r.time_text)}</dd>
      ${r.type === 'openplay' ? '<dt>Slots</dt><dd>' + Number(r.slots) + '</dd>' : ''}
      <dt>Status</dt><dd>${esc(r.status_text)}</dd>
    </dl>
    <div class="total"><span>Total</span><span>${esc(money(r.amount))}</span></div>
    <p class="small muted" style="margin-top:1rem">Payment is collected at the venue.</p>
    <div class="actions no-print">
      <button class="btn btn-sm" type="button" id="print-btn">Print</button>
      <a class="btn btn-sm btn-ghost" href="dashboard.html?tab=history">Back</a>
    </div>`;
  document.getElementById('print-btn').addEventListener('click', () => window.print());
});
