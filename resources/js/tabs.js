export function initTabs(root = document) {
    root.querySelectorAll('[data-tab-target]').forEach((tab) => {
        tab.addEventListener('click', () => {
            const target = tab.dataset.tabTarget;
            const panel = target ? root.querySelector(target) : null;

            if (!panel) return;

            const group = tab.closest('[data-tab-list]')?.parentElement ?? root;
            group.querySelectorAll('[data-tab-panel]').forEach((item) => {
                item.hidden = item !== panel;
            });
            group.querySelectorAll('[data-tab-target]').forEach((item) => {
                item.setAttribute('aria-selected', item === tab ? 'true' : 'false');
            });
        });
    });
}
