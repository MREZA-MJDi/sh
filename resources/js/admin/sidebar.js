// Mobile navigation, persistent compact mode, and topbar menu dismissal.
const shell = () => document.querySelector('[data-shell]');
const menuButton = () => document.querySelector('[data-menu]');
const miniButton = () => document.querySelector('[data-admin-sidebar-mini-toggle]');

const setMini = (enabled) => {
  const root = shell();
  if (!root) return;
  root.classList.toggle('is-mini', enabled);
  miniButton()?.setAttribute('aria-pressed', enabled ? 'true' : 'false');
};

try {
  setMini(localStorage.getItem('adm-sidebar-mini') === '1');
} catch (_) {
  setMini(false);
}

const closeMobileMenu = () => {
  shell()?.classList.remove('adm-open');
  menuButton()?.setAttribute('aria-expanded', 'false');
};

document.addEventListener('click', (event) => {
  const miniToggle = event.target.closest('[data-admin-sidebar-mini-toggle]');
  if (miniToggle) {
    const enabled = !shell()?.classList.contains('is-mini');
    setMini(enabled);
    try { localStorage.setItem('adm-sidebar-mini', enabled ? '1' : '0'); } catch (_) {}
  }

  if (event.target.closest('[data-menu]')) {
    const opening = !shell()?.classList.contains('adm-open');
    shell()?.classList.toggle('adm-open', opening);
    menuButton()?.setAttribute('aria-expanded', opening ? 'true' : 'false');
  } else if (event.target.closest('[data-menu-close]') || event.target.closest('.adm-sidebar a')) {
    closeMobileMenu();
  }

  document.querySelectorAll('details.adm-menu[open]').forEach((details) => {
    if (!details.contains(event.target)) details.open = false;
  });
});

document.addEventListener('keydown', (event) => {
  if (event.key !== 'Escape') return;
  closeMobileMenu();
  document.querySelectorAll('details.adm-menu[open]').forEach((details) => { details.open = false; });
});
