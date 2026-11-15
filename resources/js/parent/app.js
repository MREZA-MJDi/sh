'use strict';

(() => {
    const root = document.documentElement;
    const body = document.body;
    const themeButton = document.querySelector('[data-theme-toggle]');
    const childSelect = document.querySelector('[data-child]');
    const search = document.querySelector('[data-panel-search]');
    const side = document.getElementById('side');
    const menuButton = document.querySelector('[data-menu]');
    const scrim = document.querySelector('[data-menu-close]');

    const setTheme = (theme) => {
        root.dataset.theme = theme;
        themeButton?.setAttribute('aria-pressed', String(theme === 'dark'));
        try { localStorage.setItem('guardian-theme', theme); } catch (error) {}
    };

    themeButton?.addEventListener('click', () => {
        setTheme(root.dataset.theme === 'dark' ? 'light' : 'dark');
    });

    document.querySelectorAll('[data-palette]').forEach((button) => {
        button.addEventListener('click', () => {
            const palette = button.dataset.palette;
            if (palette === 'i') delete root.dataset.pal;
            else root.dataset.pal = palette;
            try { localStorage.setItem('guardian-palette', palette); } catch (error) {}
        });
    });

    childSelect?.addEventListener('change', () => {
        const url = new URL(window.location.href);
        if (childSelect.value) url.searchParams.set('child', childSelect.value);
        else url.searchParams.delete('child');
        window.location.assign(url.toString());
    });

    const closeMenu = () => {
        body.classList.remove('parent-menu-open');
        menuButton?.setAttribute('aria-expanded', 'false');
    };
    menuButton?.addEventListener('click', () => {
        const open = !body.classList.contains('parent-menu-open');
        body.classList.toggle('parent-menu-open', open);
        menuButton.setAttribute('aria-expanded', String(open));
    });
    scrim?.addEventListener('click', closeMenu);
    side?.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeMenu));

    const navLinks = [...document.querySelectorAll('[data-nav-link][data-page]')];
    const pageSections = [...document.querySelectorAll('[data-page-section]')];
    const groups = [...document.querySelectorAll('#main .g')];

    const applyPage = () => {
        const page = root.dataset.parentPage || 'home';
        const term = search?.value.trim().toLocaleLowerCase('fa') || '';
        pageSections.forEach((section) => {
            const belongs = section.dataset.pageSection.split(/\\s+/).includes(page);
            const matches = !term || section.textContent.toLocaleLowerCase('fa').includes(term);
            section.hidden = !belongs || !matches;
        });
        groups.forEach((group) => {
            if (group.hasAttribute('data-page-section')) return;
            const pageChildren = [...group.children].filter((child) => child.hasAttribute('data-page-section'));
            if (pageChildren.length) group.hidden = pageChildren.every((child) => child.hidden);
        });
        navLinks.forEach((link) => {
            const active = link.dataset.page === page;
            link.classList.toggle('on', active);
            if (active) link.setAttribute('aria-current', 'page');
            else link.removeAttribute('aria-current');
        });
    };

    navLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            root.dataset.parentPage = link.dataset.page;
            if (search) search.value = '';
            applyPage();
            closeMenu();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });

    search?.addEventListener('input', applyPage);
    root.dataset.parentPage = 'home';
    applyPage();

    if (root.dataset.theme === 'dark') themeButton?.setAttribute('aria-pressed', 'true');
})();
