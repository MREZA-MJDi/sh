// Client-side list filter. Wrap toolbar + list in [data-filter].
//   [data-filter-q]                 text search (matches data-name, else the row text)
//   [data-filter-seg] > button[data-v]   status segments, matched against row data-state
//   [data-filter-select="role"]     select, matched against row data-role
//   [data-filter-list]              container whose children are the rows/cards
//   [data-empty]                    shown when nothing matches
const norm = (s) => (s || '').toString().replace(/ي/g, 'ی').replace(/ك/g, 'ک').toLowerCase().trim();

document.querySelectorAll('[data-filter]').forEach((wrap) => {
  const list = wrap.querySelector('[data-filter-list]');
  if (!list) return;
  const q = wrap.querySelector('[data-filter-q]');
  const seg = wrap.querySelector('[data-filter-seg]');
  const selects = wrap.querySelectorAll('[data-filter-select]');
  const empty = wrap.querySelector('[data-empty]');
  let state = 'all';

  const run = () => {
    const term = norm(q?.value);
    let shown = 0;
    [...list.children].forEach((row) => {
      let ok = (state === 'all' || row.dataset.state === state) && (!term || norm(row.dataset.name || row.textContent).includes(term));
      selects.forEach((s) => { if (ok && s.value) ok = row.dataset[s.dataset.filterSelect] === s.value; });
      row.classList.toggle('adm-hide', !ok);
      shown += ok;
    });
    empty?.classList.toggle('adm-hide', shown > 0);
  };

  q?.addEventListener('input', run);
  selects.forEach((s) => s.addEventListener('change', run));
  seg?.addEventListener('click', (e) => {
    const b = e.target.closest('button[data-v]');
    if (!b) return;
    seg.querySelectorAll('button').forEach((x) => x.classList.toggle('is-on', x === b));
    state = b.dataset.v;
    run();
  });
});
