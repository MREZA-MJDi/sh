// Mobile sidebar + click-outside for the topbar dropdowns (<details class="adm-menu">).
const shell = () => document.querySelector('[data-shell]');

document.addEventListener('click', (e) => {
  const miniToggle = e.target.closest('[data-admin-sidebar-mini-toggle]');
  if (miniToggle) {
    const root = shell();
    root?.classList.toggle('is-mini');
    miniToggle.setAttribute('aria-pressed', root?.classList.contains('is-mini') ? 'true' : 'false');
  }

  if (e.target.closest('[data-menu]')) shell()?.classList.toggle('adm-open');
  else if (e.target.closest('[data-menu-close]') || e.target.closest('.adm-sidebar a')) shell()?.classList.remove('adm-open');

  document.querySelectorAll('details.adm-menu[open]').forEach((d) => { if (!d.contains(e.target)) d.open = false; });
});

document.addEventListener('keydown', (e) => {
  if (e.key !== 'Escape') return;
  shell()?.classList.remove('adm-open');
  document.querySelectorAll('details.adm-menu[open]').forEach((d) => (d.open = false));
});
