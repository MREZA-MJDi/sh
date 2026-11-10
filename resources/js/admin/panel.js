const sidebar = document.querySelector('[data-panel-sidebar]');
const overlay = document.querySelector('[data-panel-overlay]');
const shell = document.querySelector('[data-panel-shell]');

const closeSidebar = () => {
    sidebar?.classList.remove('is-open');
    overlay?.classList.remove('is-visible');
    document.body.classList.remove('panel-menu-open');
};

document.querySelectorAll('[data-panel-menu-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        sidebar?.classList.toggle('is-open');
        overlay?.classList.toggle('is-visible');
        document.body.classList.toggle('panel-menu-open');
    });
});

overlay?.addEventListener('click', closeSidebar);
sidebar?.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeSidebar));

const miniToggle = document.querySelector('[data-panel-sidebar-mini-toggle]');
const savedSidebarMode = localStorage.getItem('shikhan-panel-sidebar');

if (savedSidebarMode === 'mini') {
    shell?.classList.add('is-mini');
}

miniToggle?.addEventListener('click', () => {
    shell?.classList.toggle('is-mini');
    localStorage.setItem('shikhan-panel-sidebar', shell?.classList.contains('is-mini') ? 'mini' : 'full');
});

document.querySelectorAll('[data-nav-group-toggle]').forEach((button) => {
    const group = button.closest('.panel-nav-group');
    const items = group?.querySelector('.panel-nav-group-items');
    const hasActiveLink = Boolean(items?.querySelector('.panel-nav-link.is-active'));

    const shouldExpand = hasActiveLink || button.getAttribute('aria-expanded') !== 'false';

    group?.classList.toggle('is-collapsed', !shouldExpand);
    button.setAttribute('aria-expanded', String(shouldExpand));

    button.addEventListener('click', () => {
        const collapsed = group?.classList.toggle('is-collapsed') ?? false;
        button.setAttribute('aria-expanded', String(!collapsed));
    });
});

const themeButton = document.querySelector('[data-panel-theme-toggle]');
const root = document.documentElement;
const savedTheme = localStorage.getItem('shikhan-panel-theme');

if (savedTheme === 'dark' || savedTheme === 'light') {
    root.dataset.theme = savedTheme;
}

themeButton?.addEventListener('click', () => {
    const systemPrefersDark = window.matchMedia?.('(prefers-color-scheme: dark)').matches ?? false;
    const currentlyDark = root.dataset.theme === 'dark' || (!root.dataset.theme && systemPrefersDark);
    const nextTheme = currentlyDark ? 'light' : 'dark';

    root.dataset.theme = nextTheme;
    localStorage.setItem('shikhan-panel-theme', nextTheme);
});

document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        const message = form.dataset.confirm || 'از انجام این عملیات مطمئن هستید؟';
        if (!window.confirm(message)) event.preventDefault();
    });
});

const tableSearch = document.querySelector('[data-table-search]');
tableSearch?.addEventListener('input', () => {
    const term = tableSearch.value.trim().toLocaleLowerCase('fa');
    document.querySelectorAll('[data-searchable-table] tbody tr').forEach((row) => {
        row.hidden = !row.textContent.toLocaleLowerCase('fa').includes(term);
    });
});

document.querySelectorAll('[data-auto-dismiss]').forEach((notice) => {
    window.setTimeout(() => {
        notice.style.opacity = '0';
        window.setTimeout(() => notice.remove(), 250);
    }, 5000);
});

document.querySelectorAll('[data-open-details]').forEach((button) => {
    button.addEventListener('click', () => {
        const details = document.getElementById(button.dataset.openDetails);
        if (details) details.open = !details.open;
    });
});
