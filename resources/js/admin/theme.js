// Light/dark toggle. The saved value is applied by an inline script in <head> to avoid a flash.
document.addEventListener('click', (e) => {
  if (!e.target.closest('[data-theme-toggle]')) return;
  const root = document.documentElement;
  const next = root.dataset.theme === 'dark' ? 'light' : 'dark';
  root.dataset.theme = next;
  try { localStorage.setItem('adm-theme', next); } catch (_) {}
});
