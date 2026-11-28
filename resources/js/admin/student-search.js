document.querySelectorAll('[data-admin-student-search]').forEach((root) => {
    const query = root.querySelector('[data-query]');
    const matches = root.querySelector('[data-matches]');
    const selected = root.querySelector('[data-selected]');
    const initial = selected.innerHTML;
    const multiple = root.hasAttribute('data-multiple');
    let pending;

    function select(student) {
        if (Array.from(selected.querySelectorAll('input')).some((input) => input.value === String(student.id))) return;
        if (!multiple) selected.replaceChildren();
        const chip = document.createElement('span');
        chip.className = 'adm-chip';
        const id = document.createElement('input');
        id.type = 'hidden';
        id.name = multiple ? 'student_ids[]' : 'student_id';
        id.value = student.id;
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.dataset.remove = '';
        remove.setAttribute('aria-label', 'حذف انتخاب ' + student.label);
        remove.textContent = '×';
        chip.append(id, document.createTextNode(student.label + ' '), remove);
        selected.append(chip);
        matches.replaceChildren();
        query.value = '';
        query.focus();
    }

    async function search() {
        pending?.abort();
        const term = query.value.trim();
        if (term.length < 2) {
            matches.textContent = 'حداقل دو حرف وارد کنید.';
            return;
        }
        const request = new AbortController();
        pending = request;
        matches.textContent = 'در حال جست‌وجو…';
        try {
            const response = await fetch(root.dataset.endpoint + '?q=' + encodeURIComponent(term), {
                signal: request.signal, headers: { Accept: 'application/json' },
            });
            if (!response.ok) throw new Error('Lookup failed');
            const result = await response.json();
            if (request.signal.aborted) return;
            matches.replaceChildren();
            for (const student of result.data) {
                const choice = document.createElement('button');
                choice.type = 'button';
                choice.className = 'adm-btn adm-btn-line adm-btn-sm';
                choice.textContent = student.label + (student.meta ? ' · ' + student.meta : '');
                choice.addEventListener('click', () => select(student));
                matches.append(choice);
            }
            if (result.data.length === 0) matches.textContent = 'دانش‌آموزی پیدا نشد.';
            else if (result.has_more) matches.append('برای نتیجه دقیق‌تر، نام کامل یا شماره همراه را وارد کنید.');
        } catch {
            if (!request.signal.aborted) matches.textContent = 'جست‌وجو انجام نشد. دوباره تلاش کنید.';
        }
    }

    root.querySelector('[data-find]').addEventListener('click', search);
    query.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' || event.isComposing) return;
        event.preventDefault();
        search();
    });
    selected.addEventListener('click', (event) => event.target.closest('[data-remove]')?.closest('.adm-chip')?.remove());
    root.closest('form').addEventListener('reset', () => {
        pending?.abort();
        matches.replaceChildren();
        selected.innerHTML = initial;
    });
});
