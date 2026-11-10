const persianCalendar = new Intl.DateTimeFormat('en-US-u-ca-persian-nu-latn', {
    year: 'numeric', month: 'numeric', day: 'numeric',
});
const persianDisplay = new Intl.DateTimeFormat('fa-IR-u-ca-persian', {
    year: 'numeric', month: '2-digit', day: '2-digit',
});
const persianMonth = new Intl.DateTimeFormat('fa-IR-u-ca-persian', { month: 'long' });
const persianDigits = (value) => String(value).replace(/[0-9]/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'[Number(digit)]);
const parseParts = (date) => {
    const parts = Object.fromEntries(persianCalendar.formatToParts(date).map((part) => [part.type, part.value]));
    return { year: Number(parts.year), month: Number(parts.month), day: Number(parts.day) };
};
const localDate = (value) => {
    if (!value) return new Date(new Date().getFullYear(), new Date().getMonth(), new Date().getDate());
    const [year, month, day] = value.slice(0, 10).split('-').map(Number);
    return new Date(year, month - 1, day);
};
const isoDate = (date) => {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
};
const persianDateText = (date) => persianDisplay.format(date);
const monthStart = (anchor) => {
    const target = parseParts(anchor);
    const cursor = new Date(anchor.getFullYear() - 1, 0, 1);
    for (let index = 0; index < 800; index += 1) {
        const current = new Date(cursor.getFullYear(), cursor.getMonth(), cursor.getDate() + index);
        const parts = parseParts(current);
        if (parts.year === target.year && parts.month === target.month && parts.day === 1) return current;
    }
    return new Date(anchor.getFullYear(), anchor.getMonth(), 1);
};
const shiftMonth = (anchor, amount) => {
    const first = monthStart(anchor);
    const probe = new Date(first.getFullYear(), first.getMonth(), first.getDate() + (amount > 0 ? 35 : -1));
    return monthStart(probe);
};
const monthDays = (first) => {
    const expected = parseParts(first);
    let count = 0;
    for (let index = 0; index < 32; index += 1) {
        const candidate = new Date(first.getFullYear(), first.getMonth(), first.getDate() + index);
        const parts = parseParts(candidate);
        if (parts.year !== expected.year || parts.month !== expected.month) break;
        count += 1;
    }
    return count;
};
const timeParts = (value) => {
    const [hour = '09', minute = '00'] = (value || '09:00').split(':');
    return { hour, minute };
};

function attachPersianDatePicker(input) {
    if (input.dataset.jalaliReady) return;
    const kind = input.type;
    if (!['date', 'datetime-local'].includes(kind) || !input.name) return;

    const name = input.name;
    const initial = input.value;
    const hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.name = name;
    hidden.value = initial;
    input.insertAdjacentElement('afterend', hidden);
    input.removeAttribute('name');
    input.type = 'text';
    input.readOnly = true;
    input.autocomplete = 'off';
    input.setAttribute('aria-haspopup', 'dialog');
    input.setAttribute('aria-label', kind === 'date' ? 'انتخاب تاریخ شمسی' : 'انتخاب تاریخ و ساعت شمسی');
    input.dataset.jalaliReady = 'true';

    const wrapper = document.createElement('div');
    wrapper.className = 'admin-date-wrap';
    input.parentNode.insertBefore(wrapper, input);
    wrapper.append(input);

    let selected = localDate(initial);
    let visibleMonth = new Date(selected);
    let time = timeParts(initial.includes('T') ? initial.split('T')[1] : '09:00');
    const popover = document.createElement('div');
    popover.className = 'admin-date-popover';
    popover.hidden = true;
    popover.setAttribute('role', 'dialog');
    popover.setAttribute('aria-label', 'تقویم شمسی');
    wrapper.append(popover);

    const updateValues = () => {
        const dateValue = isoDate(selected);
        hidden.value = kind === 'datetime-local' ? `${dateValue}T${time.hour}:${time.minute}` : dateValue;
        input.value = kind === 'datetime-local'
            ? `${persianDateText(selected)} · ${persianDigits(time.hour)}:${persianDigits(time.minute)}`
            : persianDateText(selected);
        hidden.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const makeTimeSelect = (unit, value, max) => {
        const select = document.createElement('select');
        select.setAttribute('aria-label', unit === 'hour' ? 'ساعت' : 'دقیقه');
        for (let number = 0; number <= max; number += 1) {
            const option = document.createElement('option');
            option.value = String(number).padStart(2, '0');
            option.textContent = persianDigits(option.value);
            option.selected = option.value === value;
            select.append(option);
        }
        select.addEventListener('change', () => {
            time[unit] = select.value;
            updateValues();
        });
        return select;
    };

    const render = () => {
        popover.replaceChildren();
        const first = monthStart(visibleMonth);
        const parts = parseParts(first);
        const head = document.createElement('div');
        head.className = 'admin-calendar-head';
        const previous = document.createElement('button');
        previous.type = 'button'; previous.textContent = '‹'; previous.setAttribute('aria-label', 'ماه قبل');
        previous.addEventListener('click', () => { visibleMonth = shiftMonth(visibleMonth, -1); render(); });
        const next = document.createElement('button');
        next.type = 'button'; next.textContent = '›'; next.setAttribute('aria-label', 'ماه بعد');
        next.addEventListener('click', () => { visibleMonth = shiftMonth(visibleMonth, 1); render(); });
        const monthLabel = document.createElement('strong');
        monthLabel.className = 'admin-calendar-month';
        monthLabel.textContent = `${persianMonth.format(first)} ${persianDigits(parts.year)}`;
        head.append(previous, monthLabel, next);
        popover.append(head);

        const grid = document.createElement('div');
        grid.className = 'admin-calendar-grid';
        ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'].forEach((day) => {
            const label = document.createElement('span');
            label.className = 'admin-calendar-weekday'; label.textContent = day; grid.append(label);
        });
        const offset = (first.getDay() + 1) % 7;
        const count = monthDays(first);
        const rows = Math.ceil((offset + count) / 7);
        const cells = rows * 7;
        for (let index = 0; index < cells; index += 1) {
            const candidate = new Date(first.getFullYear(), first.getMonth(), first.getDate() + index - offset);
            const candidateParts = parseParts(candidate);
            const button = document.createElement('button');
            button.type = 'button'; button.className = 'admin-calendar-day';
            button.textContent = persianDigits(candidateParts.day);
            if (candidateParts.month !== parts.month || candidateParts.year !== parts.year) button.classList.add('is-outside');
            if (isoDate(candidate) === isoDate(selected)) button.classList.add('is-selected');
            if (isoDate(candidate) === isoDate(new Date())) button.classList.add('is-today');
            button.addEventListener('click', () => {
                selected = candidate;
                updateValues();
                render();
                if (kind === 'date') close();
            });
            grid.append(button);
        }
        popover.append(grid);

        const footer = document.createElement('div');
        footer.className = 'admin-calendar-footer';
        const today = document.createElement('button');
        today.type = 'button'; today.className = 'panel-btn panel-btn-secondary panel-btn-sm';
        today.textContent = 'امروز';
        today.addEventListener('click', () => { selected = new Date(); visibleMonth = new Date(selected); updateValues(); render(); });
        footer.append(today);

        if (kind === 'datetime-local') {
            const controls = document.createElement('div');
            controls.className = 'admin-calendar-time';
            const hour = makeTimeSelect('hour', time.hour, 23);
            const minute = makeTimeSelect('minute', time.minute, 59);
            const separator = document.createElement('span'); separator.textContent = ':';
            controls.append(hour, separator, minute);
            footer.append(controls);
            const done = document.createElement('button');
            done.type = 'button'; done.className = 'panel-btn panel-btn-sm'; done.textContent = 'تأیید';
            done.addEventListener('click', close);
            footer.append(done);
        }
        popover.append(footer);
    };

    const open = () => { visibleMonth = new Date(selected); render(); popover.hidden = false; input.setAttribute('aria-expanded', 'true'); };
    const close = () => { popover.hidden = true; input.setAttribute('aria-expanded', 'false'); };
    input.addEventListener('click', () => popover.hidden ? open() : close());
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); open(); }
    });
    document.addEventListener('click', (event) => {
        if (!wrapper.contains(event.target)) close();
    });
    updateValues();
}

function attachPersianTimePicker(input) {
    if (input.dataset.jalaliReady || input.type !== 'time' || !input.name) return;
    const hidden = document.createElement('input');
    hidden.type = 'hidden'; hidden.name = input.name; hidden.value = input.value || '09:00';
    input.insertAdjacentElement('afterend', hidden);
    input.removeAttribute('name');
    input.type = 'text'; input.readOnly = true; input.dataset.jalaliReady = 'true';
    input.setAttribute('aria-label', 'انتخاب ساعت');
    const wrapper = document.createElement('div'); wrapper.className = 'admin-date-wrap';
    input.parentNode.insertBefore(wrapper, input); wrapper.append(input);
    const popover = document.createElement('div'); popover.className = 'admin-date-popover'; popover.hidden = true;
    const controls = document.createElement('div'); controls.className = 'admin-calendar-time';
    const current = timeParts(hidden.value);
    const hour = document.createElement('select'), minute = document.createElement('select');
    [[hour, 23, current.hour, 'ساعت'], [minute, 59, current.minute, 'دقیقه']].forEach(([select, max, value, label]) => {
        select.setAttribute('aria-label', label);
        for (let index = 0; index <= max; index += 1) {
            const option = document.createElement('option');
            option.value = String(index).padStart(2, '0'); option.textContent = persianDigits(option.value);
            option.selected = option.value === value; select.append(option);
        }
    });
    const save = document.createElement('button'); save.type = 'button'; save.className = 'panel-btn panel-btn-sm'; save.textContent = 'تأیید';
    const update = () => {
        hidden.value = `${hour.value}:${minute.value}`;
        input.value = persianDigits(hidden.value);
        hidden.dispatchEvent(new Event('change', { bubbles: true }));
    };
    [hour, minute].forEach((select) => select.addEventListener('change', update));
    save.addEventListener('click', () => { update(); popover.hidden = true; });
    controls.append(hour, document.createTextNode(':'), minute, save); popover.append(controls); wrapper.append(popover);
    input.value = persianDigits(hidden.value);
    input.addEventListener('click', () => { popover.hidden = !popover.hidden; });
    document.addEventListener('click', (event) => { if (!wrapper.contains(event.target)) popover.hidden = true; });
}

window.initializeAdminPersianPickers = (scope = document) => {
    scope.querySelectorAll('input[type="date"], input[type="datetime-local"]').forEach(attachPersianDatePicker);
    scope.querySelectorAll('input[type="time"]').forEach(attachPersianTimePicker);
};

window.initializeAdminPersianPickers(document);
