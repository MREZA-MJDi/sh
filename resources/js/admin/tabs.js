// <div data-tabs> <button data-tab="a">…</button> … </div>  +  <section data-tab-panel="a">
document.addEventListener('click', (e) => {
  const tab = e.target.closest('[data-tab]');
  if (!tab) return;
  const wrap = tab.closest('[data-tabs]');
  wrap.querySelectorAll('[data-tab]').forEach((t) => t.classList.toggle('is-active', t === tab));
  document.querySelectorAll('[data-tab-panel]').forEach((p) => p.classList.toggle('adm-hide', p.dataset.tabPanel !== tab.dataset.tab));
});

// collapsible chapters in the course builder
document.addEventListener('click', (e) => {
  const head = e.target.closest('[data-toggle-chapter]');
  if (head && !e.target.closest('button,a')) head.closest('.adm-chapter').classList.toggle('is-collapsed');
});
