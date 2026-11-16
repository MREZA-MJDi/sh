// Attendance roster + score sheets (assignments / exams).
//   [data-roster]                  wrapper;  .adm-marks radios named status[<id>]
//   [data-mark-all="present"]      sets every student to that status
//   [data-t="present"] b           live counters
//   [data-scores] [data-score]     numeric inputs;  [data-avg] shows their average
const tally = (root) => {
  const counts = { present: 0, absent: 0, late: 0, excused: 0 };
  root.querySelectorAll('.adm-marks input:checked').forEach((r) => counts[r.value]++);
  root.querySelectorAll('[data-t]').forEach((el) => (el.querySelector('b').textContent = counts[el.dataset.t]));
};

document.querySelectorAll('[data-roster]').forEach(tally);

document.addEventListener('change', (e) => {
  const roster = e.target.closest('[data-roster]');
  if (roster && e.target.type === 'radio') tally(roster);
});

document.addEventListener('click', (e) => {
  const b = e.target.closest('[data-mark-all]');
  if (!b) return;
  const roster = b.closest('[data-roster]');
  roster.querySelectorAll(`.adm-marks input[value="${b.dataset.markAll}"]`).forEach((r) => (r.checked = true));
  tally(roster);
});

document.addEventListener('input', (e) => {
  const box = e.target.closest('[data-scores]');
  if (!box || !e.target.matches('[data-score]')) return;
  const vals = [...box.querySelectorAll('[data-score]')].map((i) => parseFloat(i.value)).filter((n) => !isNaN(n));
  const avg = box.querySelector('[data-avg]');
  if (avg) avg.textContent = vals.length ? (vals.reduce((a, b) => a + b, 0) / vals.length).toFixed(1) : '—';
});
