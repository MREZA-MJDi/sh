// Mobile sidebar + click-outside for the topbar dropdowns (<details class="adm-menu">).
const shell = () => document.querySelector('[data-shell]');

document.addEventListener('click', (e) => {
  if (e.target.closest('[data-menu]')) shell()?.classList.toggle('adm-open');
  else if (e.target.closest('[data-menu-close]') || e.target.closest('.adm-sidebar a')) shell()?.classList.remove('adm-open');

  document.querySelectorAll('details.adm-menu[open]').forEach((d) => { if (!d.contains(e.target)) d.open = false; });
});

document.addEventListener('keydown', (e) => {
  if (e.key !== 'Escape') return;
  shell()?.classList.remove('adm-open');
  document.querySelectorAll('details.adm-menu[open]').forEach((d) => (d.open = false));
});
