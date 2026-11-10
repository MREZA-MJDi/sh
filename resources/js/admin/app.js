import './persian-date.js';

const body = document.body;
const sidebar = document.querySelector('[data-panel-sidebar]');
const scrim = document.querySelector('.admin-scrim');
const drawer = document.getElementById('admin-drawer');

const closeMenu = () => {
    sidebar?.classList.remove('is-open');
    scrim?.classList.remove('is-visible');
};
document.querySelector('[data-panel-menu-toggle]')?.addEventListener('click', () => {
    sidebar?.classList.toggle('is-open');
    scrim?.classList.toggle('is-visible');
});
sidebar?.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeMenu));

const closeDrawer = () => {
    drawer?.classList.remove('is-open');
    drawer?.setAttribute('aria-hidden', 'true');
    body.classList.remove('admin-drawer-open');
};
document.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) return;
    if (target.closest('[data-admin-drawer-open]')) {
        const button = target.closest('[data-admin-drawer-open]');
        const template = document.getElementById(button.dataset.adminDrawerOpen);
        if (!drawer || !(template instanceof HTMLTemplateElement)) return;
        drawer.querySelector('[data-drawer-title]').textContent = button.dataset.drawerTitle || 'فرم';
        const drawerBody = drawer.querySelector('[data-drawer-body]');
        drawerBody.replaceChildren(template.content.cloneNode(true));
        window.initializeAdminPersianPickers?.(drawerBody);
        drawer.classList.add('is-open');
        drawer.setAttribute('aria-hidden', 'false');
        body.classList.add('admin-drawer-open');
        window.setTimeout(() => drawer.querySelector('input:not([type=hidden]),select,textarea')?.focus(), 80);
    }
    if (target.closest('[data-admin-close]')) {
        closeDrawer();
        closeMenu();
    }
    const tab = target.closest('[data-admin-filter]');
    if (tab) {
        const group = tab.closest('[data-admin-filter-group]') || document;
        group.querySelectorAll('[data-admin-filter]').forEach((item) => item.setAttribute('aria-selected', String(item === tab)));
        const value = tab.dataset.adminFilter;
        document.querySelectorAll('[data-admin-filterable]').forEach((item) => {
            item.hidden = value !== 'all' && !((item.dataset.filter || '').split(' ').includes(value));
        });
    }
});
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeDrawer();
        closeMenu();
    }
});
document.querySelector('[data-panel-theme-toggle]')?.addEventListener('click', () => {
    const dark = body.dataset.theme !== 'dark';
    body.dataset.theme = dark ? 'dark' : 'light';
    try { localStorage.setItem('shikhan-admin-theme', dark ? 'dark' : 'light'); } catch { /* optional */ }
});
try {
    const savedTheme = localStorage.getItem('shikhan-admin-theme');
    if (savedTheme === 'dark' || savedTheme === 'light') body.dataset.theme = savedTheme;
} catch { /* optional */ }

document.querySelector('[data-table-search]')?.addEventListener('input', (event) => {
    const term = event.target.value.trim().toLocaleLowerCase();
    document.querySelectorAll('[data-searchable-table] tbody tr, [data-admin-searchable]').forEach((row) => {
        row.hidden = term !== '' && !row.textContent.toLocaleLowerCase().includes(term);
    });
});
document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm || 'از انجام این عملیات مطمئن هستید؟')) event.preventDefault();
    });
});
document.querySelectorAll('[data-auto-dismiss]').forEach((notice) => {
    window.setTimeout(() => notice.remove(), 5000);
});
