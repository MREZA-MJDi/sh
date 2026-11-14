export function initTabs() {
    document.querySelectorAll('[data-tabs]').forEach((root) => {
        const tabs = [...root.querySelectorAll('[data-tab]')];
        const panels = [...root.querySelectorAll('[data-panel]')];

        const show = (id) => {
            tabs.forEach((t) => t.setAttribute('aria-selected', String(t.dataset.tab === id)));
            panels.forEach((p) => (p.hidden = p.dataset.panel !== id));
        };

        tabs.forEach((t) => t.addEventListener('click', () => show(t.dataset.tab)));
        show(tabs[0].dataset.tab);
    });

    // پرسش و پاسخ (فقط نمایش در همین صفحه، بدون ذخیره)
    document.querySelectorAll('[data-qa]').forEach((form) => {
        const list = document.querySelector('[data-comments]');
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const input = form.querySelector('textarea');
            const text = input.value.trim();
            if (!text) return;

            const li = document.createElement('li');
            li.className = 'rounded-lg bg-[#F1F4F5] p-4';
            const name = document.createElement('p');
            name.className = 'text-sm font-bold text-[#16202A]';
            name.textContent = 'شما';
            const body = document.createElement('p');
            body.className = 'mt-1 leading-7 text-[#4A5560]';
            body.textContent = text;
            li.append(name, body);
            list.append(li);
            input.value = '';
        });
    });
}
