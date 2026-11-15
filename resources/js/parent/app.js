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

    const navLinks = [...document.querySelectorAll('[data-nav-link][href^="#"]')];
    const targets = navLinks.map((link) => document.querySelector(link.getAttribute('href'))).filter(Boolean);
    if ('IntersectionObserver' in window && targets.length) {
        const observer = new IntersectionObserver((entries) => {
            const visible = entries.filter((entry) => entry.isIntersecting)
                .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
            if (!visible) return;
            navLinks.forEach((link) => {
                const active = link.getAttribute('href') === '#' + visible.target.id;
                link.classList.toggle('on', active);
                if (active) link.setAttribute('aria-current', 'page');
                else link.removeAttribute('aria-current');
            });
        }, { rootMargin: '-18% 0px -62% 0px', threshold: [0, .2, .5] });
        targets.forEach((target) => observer.observe(target));
    }

    search?.addEventListener('input', () => {
        const term = search.value.trim().toLocaleLowerCase('fa');
        const sections = [...document.querySelectorAll('#main section[id], #main article[id]')];
        sections.forEach((section) => {
            const matches = !term || section.textContent.toLocaleLowerCase('fa').includes(term);
            section.hidden = !matches;
        });
    });

    if (root.dataset.theme === 'dark') themeButton?.setAttribute('aria-pressed', 'true');
})();
