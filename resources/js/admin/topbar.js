// Keep the notification and profile panels predictable on every viewport.
const topbarMenus = Array.from(document.querySelectorAll('[data-admin-topbar-menu]'));

if (topbarMenus.length) {
  const closeMenus = (except = null) => {
    topbarMenus.forEach((menu) => {
      if (menu !== except) menu.open = false;
    });
  };

  topbarMenus.forEach((menu) => {
    menu.addEventListener('toggle', () => {
      if (menu.open) closeMenus(menu);
    });
  });

  document.addEventListener('click', (event) => {
    const clickedMenu = event.target instanceof Element
      ? event.target.closest('[data-admin-topbar-menu]')
      : null;

    if (!clickedMenu) closeMenus();
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;

    const openMenu = topbarMenus.find((menu) => menu.open);
    if (!openMenu) return;

    openMenu.open = false;
    openMenu.querySelector('summary')?.focus();
  });
}
