import './persian-date.js';

const body = document.body;
const sidebar = document.querySelector('[data-panel-sidebar]');
const scrim = document.querySelector('.admin-scrim');
const drawer = document.getElementById('admin-drawer');

const adminShell = document.querySelector('[data-admin-shell]');
const miniSidebarToggle = document.querySelector('[data-admin-sidebar-mini-toggle]');

try {
    if (localStorage.getItem('shikhan-admin-sidebar') === 'mini') {
        adminShell?.classList.add('is-mini');
    }
} catch { /* Sidebar preference is optional. */ }

miniSidebarToggle?.addEventListener('click', () => {
    adminShell?.classList.toggle('is-mini');
    try {
        localStorage.setItem(
            'shikhan-admin-sidebar',
            adminShell?.classList.contains('is-mini') ? 'mini' : 'full'
        );
    } catch { /* Sidebar preference is optional. */ }
});

sidebar?.querySelectorAll('.panel-nav-link').forEach((link) => {
    const label = link.querySelector('span:not(.panel-nav-icon)')?.textContent.trim()
        || link.textContent.trim();
    if (!label) return;
    link.setAttribute('aria-label', label);
    link.title = label;
});

document.querySelectorAll('[data-admin-nav-group-toggle]').forEach((button) => {
    const items = [];
    let sibling = button.nextElementSibling;

    while (sibling && !sibling.matches('[data-admin-nav-group-toggle]')) {
        items.push(sibling);
        sibling = sibling.nextElementSibling;
    }

    const hasActiveLink = items.some((item) =>
        item.matches('.panel-nav-link.is-active') || item.querySelector('.panel-nav-link.is-active')
    );

    const applyExpandedState = (expanded) => {
        button.setAttribute('aria-expanded', String(expanded));
        items.forEach((item) => {
            item.hidden = !expanded;
        });
    };

    applyExpandedState(hasActiveLink || button.getAttribute('aria-expanded') === 'true');

    button.addEventListener('click', () => {
        applyExpandedState(button.getAttribute('aria-expanded') !== 'true');
    });
});

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
document.addEventListener('change', (event) => {
    const select = event.target;

    if (!(select instanceof HTMLSelectElement) || !select.matches('[data-reset-spotplayer-id]')) {
        return;
    }

    if (select.value !== 'spotplayer') {
        const courseIdInput = select.form?.querySelector('[name="spotplayer_course_id"]');

        if (courseIdInput instanceof HTMLInputElement) {
            courseIdInput.value = '';
        }
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


// Remote lookups are opt-in: no request is sent on page load or drawer render.
const lookupTimers = new WeakMap();
const lookupRequests = new WeakMap();

document.addEventListener('input', (event) => {
    const input = event.target;
    if (!(input instanceof HTMLInputElement) || !input.matches('[data-lookup-input]')) return;

    const widget = input.closest('[data-remote-lookup]');
    if (!widget) return;

    const results = widget.querySelector('[data-lookup-results]');
    const status = widget.querySelector('[data-lookup-status]');
    const term = input.value.trim();
    const previousTimer = lookupTimers.get(widget);
    if (previousTimer) window.clearTimeout(previousTimer);

    const previousRequest = lookupRequests.get(widget);
    if (previousRequest) previousRequest.abort();

    results?.replaceChildren();
    if (term.length < 2) {
        if (status) status.textContent = 'برای جستجو حداقل دو حرف وارد کنید.';
        return;
    }

    if (status) status.textContent = 'در حال جستجو…';
    const timer = window.setTimeout(async () => {
        const controller = new AbortController();
        lookupRequests.set(widget, controller);

        try {
            const url = new URL(widget.dataset.lookupUrl, window.location.origin);
            url.searchParams.set('q', term);

            const response = await fetch(url, {
                method: 'GET',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                signal: controller.signal,
            });
            if (!response.ok) throw new Error('lookup_failed');

            const payload = await response.json();
            if (input.value.trim() !== term) return;

            const fragment = document.createDocumentFragment();
            (payload.data || []).forEach((item) => {
                const selected = [...widget.querySelectorAll('[data-lookup-value]')]
                    .some((node) => node.value === String(item.id));
                if (selected) return;

                const option = document.createElement('button');
                option.type = 'button';
                option.className = 'panel-lookup-option';
                option.dataset.lookupAdd = '';
                option.dataset.lookupValue = String(item.id);
                option.dataset.lookupLabel = item.label || '';
                option.setAttribute('role', 'option');

                const label = document.createElement('span');
                label.textContent = item.label || '';
                option.append(label);

                if (item.meta) {
                    const meta = document.createElement('small');
                    meta.textContent = item.meta;
                    option.append(meta);
                }
                fragment.append(option);
            });

            results?.replaceChildren(fragment);
            if (status) {
                const count = (payload.data || []).length;
                status.textContent = count
                    ? `${count} نتیجه نمایش داده شد.${payload.has_more ? ' برای محدودماندن نتایج، جستجو را دقیق‌تر کنید.' : ''}`
                    : 'موردی با این عبارت پیدا نشد.';
            }
        } catch (error) {
            if (error.name !== 'AbortError' && status) {
                status.textContent = 'جستجو انجام نشد؛ دوباره تلاش کنید.';
            }
        }
    }, 300);

    lookupTimers.set(widget, timer);
});

document.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) return;

    const option = target.closest('[data-lookup-add]');
    if (option) {
        const widget = option.closest('[data-remote-lookup]');
        const selected = widget?.querySelector('[data-lookup-selected]');
        if (!widget || !selected) return;

        const value = option.dataset.lookupValue;
        if ([...selected.querySelectorAll('[data-lookup-value]')].some((input) => input.value === value)) return;
        if (selected.querySelectorAll('[data-lookup-value]').length >= 30) {
            const status = widget.querySelector('[data-lookup-status]');
            if (status) status.textContent = 'حداکثر ۳۰ دانش‌آموز قابل اتصال است.';
            return;
        }

        const chip = document.createElement('span');
        chip.className = 'panel-lookup-chip';

        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = widget.dataset.lookupName;
        hidden.value = value;
        hidden.dataset.lookupValue = value;

        const label = document.createElement('span');
        label.textContent = option.dataset.lookupLabel || `شناسه ${value}`;

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.dataset.lookupRemove = '';
        remove.setAttribute('aria-label', 'حذف مورد انتخاب‌شده');
        remove.textContent = '×';

        chip.append(hidden, label, remove);
        selected.append(chip);
        option.remove();
        return;
    }

    const remove = target.closest('[data-lookup-remove]');
    if (remove) remove.closest('.panel-lookup-chip')?.remove();
});
