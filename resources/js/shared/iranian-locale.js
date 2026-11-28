(() => {
    'use strict';

    const asciiDigits = value => String(value).replace(/[۰-۹٠-٩]/g, digit => {
        const faIndex = '۰۱۲۳۴۵۶۷۸۹'.indexOf(digit);
        return String(faIndex >= 0 ? faIndex : '٠١٢٣٤٥٦٧٨٩'.indexOf(digit));
    });
    const faDigits = value => String(value).replace(/\d/g, digit => '۰۱۲۳۴۵۶۷۸۹'[Number(digit)]);
    const groupedNumber = new Intl.NumberFormat('fa-IR', { maximumFractionDigits: 12 });
    const tehranTimeFormatter = new Intl.DateTimeFormat('en-GB', {
        timeZone: 'Asia/Tehran', hour: '2-digit', minute: '2-digit',
        second: '2-digit', hourCycle: 'h23'
    });

    let persianFormatter = null;
    try {
        persianFormatter = new Intl.DateTimeFormat('fa-IR-u-ca-persian', {
            timeZone: 'Asia/Tehran', year: 'numeric', month: '2-digit', day: '2-digit'
        });
    } catch (_) {}

    function persianDateParts(date) {
        if (!persianFormatter || !(date instanceof Date) || Number.isNaN(date.getTime())) return null;
        const values = Object.fromEntries(persianFormatter.formatToParts(date).map(part => [part.type, part.value]));
        if (!values.year || !values.month || !values.day) return null;
        return {
            year: Number(asciiDigits(values.year)),
            month: Number(asciiDigits(values.month)),
            day: Number(asciiDigits(values.day))
        };
    }

    function formatJalaliDate(date, includeTime) {
        const parts = persianDateParts(date);
        if (!parts) return null;
        let result = faDigits(String(parts.year).padStart(4, '0')) + '/'
            + faDigits(String(parts.month).padStart(2, '0')) + '/'
            + faDigits(String(parts.day).padStart(2, '0'));
        if (includeTime) {
            const timeParts = Object.fromEntries(tehranTimeFormatter.formatToParts(date).map(part => [part.type, part.value]));
            result += ' ' + faDigits(timeParts.hour || '00') + ':' + faDigits(timeParts.minute || '00');
            if (timeParts.second && timeParts.second !== '00') result += ':' + faDigits(timeParts.second);
        }
        return result;
    }

    function compareParts(left, right) {
        for (const key of ['year', 'month', 'day']) {
            if (left[key] < right[key]) return -1;
            if (left[key] > right[key]) return 1;
        }
        return 0;
    }

    // Inverse conversion: find the Gregorian day represented by a Jalali date.
    function jalaliToGregorian(year, month, day) {
        if (!persianFormatter || month < 1 || month > 12 || day < 1 || day > 31) return null;
        const baseDay = Math.floor(Date.UTC(year + 621, 0, 1, 12) / 86400000);
        let low = baseDay - 370;
        let high = baseDay + 460;
        const target = { year, month, day };

        while (low <= high) {
            const middle = Math.floor((low + high) / 2);
            const candidate = new Date(middle * 86400000 + 12 * 3600000);
            const actual = persianDateParts(candidate);
            if (!actual) return null;
            const comparison = compareParts(actual, target);
            if (comparison === 0) {
                return {
                    year: candidate.getUTCFullYear(),
                    month: candidate.getUTCMonth() + 1,
                    day: candidate.getUTCDate()
                };
            }
            if (comparison < 0) low = middle + 1;
            else high = middle - 1;
        }
        return null;
    }

    function jalaliInputToIso(value, withTime) {
        const normalized = asciiDigits(value).trim();
        const match = normalized.match(/^(\d{4})[\/-](\d{1,2})[\/-](\d{1,2})(?:[ T](\d{1,2}):([0-5]\d)(?::([0-5]\d))?)?$/);
        if (!match) return null;
        const jalali = jalaliToGregorian(Number(match[1]), Number(match[2]), Number(match[3]));
        if (!jalali) return null;
        if (withTime && (match[4] === undefined || Number(match[4]) > 23)) return null;

        const datePart = String(jalali.year).padStart(4, '0') + '-'
            + String(jalali.month).padStart(2, '0') + '-'
            + String(jalali.day).padStart(2, '0');
        if (!withTime) return match[4] === undefined ? datePart : null;
        return datePart + 'T' + String(Number(match[4])).padStart(2, '0') + ':' + match[5]
            + (match[6] ? ':' + match[6] : '');
    }

    function dateFromIso(value) {
        const match = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})(?:T(\d{2}):(\d{2})(?::(\d{2}))?)?$/);
        if (!match) return null;
        // Database datetime fields are stored in Asia/Tehran local time.
        // Model that wall-clock time as the equivalent UTC instant so ICU's
        // Asia/Tehran formatters reproduce the original clock without depending
        // on the visitor's browser timezone.
        const hour = match[4] === undefined ? 8 : Number(match[4]) - 3;
        const minute = match[4] === undefined ? 30 : Number(match[5]) - 30;
        return new Date(Date.UTC(
            Number(match[1]), Number(match[2]) - 1, Number(match[3]),
            hour, minute, Number(match[6] || 0)
        ));
    }

    function localizeDateText(value) {
        const fullDate = /(^|[^\d])((?:19|20)\d{2})[/-](0?[1-9]|1[0-2])[/-]([12]\d|3[01]|0?[1-9])(?!\d)(?:[ T](\d{1,2}):([0-5]\d)(?::([0-5]\d))?)?/g;
        value = value.replace(fullDate, (full, prefix, year, month, day, hour, minute, second) => {
            const date = new Date(Date.UTC(Number(year), Number(month) - 1, Number(day), 12));
            if (date.getUTCMonth() !== Number(month) - 1 || date.getUTCDate() !== Number(day)) return full;
            const formatted = formatJalaliDate(date, false);
            const clock = hour === undefined ? '' : ' ' + faDigits(hour.padStart(2, '0') + ':' + minute + (second ? ':' + second : ''));
            return formatted ? prefix + formatted + clock : full;
        });

        const monthOnly = /(^|[^\d])((?:19|20)\d{2})-(0?[1-9]|1[0-2])(?![-/\d])/g;
        value = value.replace(monthOnly, (full, prefix, year, month) => {
            const parts = persianDateParts(new Date(Number(year), Number(month) - 1, 15, 12));
            if (!parts) return full;
            return prefix + faDigits(String(parts.year).padStart(4, '0')) + '/' + faDigits(String(parts.month).padStart(2, '0'));
        });
        return value;
    }

    function localizeText(value) {
        if (!value || !/[0-9۰-۹٠-٩]/.test(value)) return value;
        value = localizeDateText(value);
        value = value.replace(/\d{1,3}(?:,\d{3})+(?:\.\d+)?/g, raw => {
            const parsed = Number(raw.replace(/,/g, ''));
            return Number.isFinite(parsed) ? groupedNumber.format(parsed) : raw;
        });
        value = value.replace(/(?<![\d\/:-])\d{4,}(?![\d\/:-])/g, raw => {
            const parsed = Number(raw);
            return Number.isFinite(parsed) ? groupedNumber.format(parsed) : raw;
        });
        return faDigits(value);
    }

    function updateDateField(original, visible, valueDescriptor) {
        const raw = valueDescriptor.get.call(original);
        if (!raw) {
            visible.value = '';
            return;
        }
        const date = dateFromIso(raw.slice(0, 10));
        const formatted = date && formatJalaliDate(date, false);
        const clock = raw.split('T')[1];
        visible.value = formatted ? formatted + (clock ? ' ' + faDigits(clock) : '') : raw;
    }

    function normalizeNumber(value, original) {
        const normalized = asciiDigits(value).replace(/[,\u066C٬\s]/g, '').replace(/[٫]/g, '.');
        if (normalized === '') return { valid: !original.required, value: '' };
        if (!/^-?(?:\d+\.?\d*|\.\d+)$/.test(normalized)) return { valid: false, value: '' };
        const number = Number(normalized);
        if (!Number.isFinite(number)) return { valid: false, value: '' };
        const min = original.getAttribute('min');
        const max = original.getAttribute('max');
        if (min !== null && number < Number(min)) return { valid: false, value: normalized };
        if (max !== null && number > Number(max)) return { valid: false, value: normalized };
        return { valid: true, value: normalized };
    }

    function attachJalaliCalendar(original, visible) {
        if (!persianFormatter || visible.readOnly || !HTMLElement.prototype.showPopover) return;
        const calendar = document.createElement('div');
        calendar.setAttribute('popover', 'auto');
        calendar.setAttribute('role', 'dialog');
        calendar.setAttribute('aria-label', 'تقویم شمسی');
        calendar.dir = 'rtl';
        calendar.style.cssText = 'position:fixed;margin:0;padding:12px;max-width:calc(100vw - 16px);box-sizing:border-box;border:1px solid #94a3b8;border-radius:8px;background:Canvas;color:CanvasText;font:inherit';
        (original.closest('dialog') || document.body).append(calendar);

        const button = (label, action) => {
            const element = document.createElement('button');
            element.type = 'button';
            element.textContent = label;
            element.addEventListener('click', action);
            return element;
        };
        const months = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
            'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
        const isoDay = ({ year, month, day }) =>
            [String(year).padStart(4, '0'), String(month).padStart(2, '0'), String(day).padStart(2, '0')].join('-');
        let monthIndex;
        let opener;
        const render = () => {
            const year = Math.floor(monthIndex / 12);
            const month = monthIndex % 12 + 1;
            const heading = document.createElement('div');
            heading.style.cssText = 'display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:8px';
            const title = document.createElement('strong');
            title.textContent = months[month - 1] + ' ' + faDigits(year);
            const yearInput = document.createElement('input');
            yearInput.type = 'text';
            yearInput.inputMode = 'numeric';
            yearInput.size = 4;
            yearInput.value = faDigits(year);
            yearInput.setAttribute('aria-label', 'سال شمسی');
            yearInput.addEventListener('change', () => {
                const entered = asciiDigits(yearInput.value).trim();
                if (!/^[1-9]\d{3}$/.test(entered) || Number(entered) > 2500) {
                    yearInput.value = faDigits(year);
                    return;
                }
                monthIndex = Number(entered) * 12 + month - 1;
                render();
                calendar.querySelector('input')?.focus();
            });
            heading.append(button('ماه قبل', () => move(-1)), title, yearInput, button('ماه بعد', () => move(1)));
            const grid = document.createElement('div');
            grid.style.cssText = 'display:grid;grid-template-columns:repeat(7,1fr);gap:4px;text-align:center';
            for (const weekday of ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج']) {
                const cell = document.createElement('span');
                cell.textContent = weekday;
                grid.append(cell);
            }
            const first = jalaliToGregorian(year, month, 1);
            if (!first) return;
            const firstDate = Date.UTC(first.year, first.month - 1, first.day, 12);
            const offset = (new Date(firstDate).getUTCDay() + 1) % 7;
            for (let i = 0; i < offset; i++) grid.append(document.createElement('span'));
            const next = jalaliToGregorian(month === 12 ? year + 1 : year, month === 12 ? 1 : month + 1, 1);
            const length = (Date.UTC(next.year, next.month - 1, next.day, 12) - firstDate) / 86400000;
            for (let day = 1; day <= length; day++) {
                const date = new Date(firstDate + (day - 1) * 86400000);
                const iso = isoDay({ year: date.getUTCFullYear(), month: date.getUTCMonth() + 1, day: date.getUTCDate() });
                const choice = button(faDigits(day), () => {
                    const enteredTime = asciiDigits(visible.value).match(/\s(\d{1,2}:[0-5]\d(?::[0-5]\d)?)$/)?.[1];
                    const time = enteredTime || original.value.split('T')[1] || '09:00';
                    const dateText = faDigits([year, String(month).padStart(2, '0'), String(day).padStart(2, '0')].join('/'));
                    visible.value = original.type === 'datetime-local' ? dateText + ' ' + faDigits(time) : dateText;
                    visible.dispatchEvent(new Event('input', { bubbles: true }));
                    visible.dispatchEvent(new Event('change', { bubbles: true }));
                    calendar.hidePopover();
                    visible.focus();
                });
                choice.setAttribute('aria-label', faDigits(day) + ' ' + title.textContent);
                choice.setAttribute('aria-pressed', String(original.value.slice(0, 10) === iso));
                choice.disabled = Boolean((original.min && iso < original.min.slice(0, 10)) ||
                    (original.max && iso > original.max.slice(0, 10)));
                grid.append(choice);
            }
            calendar.replaceChildren(heading, grid);
        };

        function move(delta) {
            monthIndex += delta;
            render();
            calendar.querySelectorAll('button')[delta < 0 ? 0 : 1]?.focus();
        }
        function open() {
            if (calendar.matches(':popover-open')) {
                calendar.hidePopover();
                return;
            }
            const parts = persianDateParts(dateFromIso(original.value) || new Date());
            if (!parts) return;
            monthIndex = parts.year * 12 + parts.month - 1;
            render();
            calendar.showPopover();
            const anchor = opener.getBoundingClientRect();
            const panel = calendar.getBoundingClientRect();
            calendar.style.left = Math.max(8, Math.min(anchor.right - panel.width, window.innerWidth - panel.width - 8)) + 'px';
            calendar.style.top = Math.max(8, Math.min(anchor.bottom + 4, window.innerHeight - panel.height - 8)) + 'px';
            (calendar.querySelector('[aria-pressed="true"]:enabled') || calendar.querySelector('button:enabled'))?.focus();
        }

        opener = button('▦', open);
        opener.style.cssText = 'margin-inline-start:4px;vertical-align:middle';
        opener.setAttribute('aria-label', 'انتخاب تاریخ از تقویم شمسی');
        opener.setAttribute('aria-haspopup', 'dialog');
        opener.title = 'تقویم شمسی';
        const wrapper = document.createElement('span');
        wrapper.style.cssText = 'position:relative;display:block;min-width:0';
        visible.after(wrapper);
        wrapper.append(visible, opener);
        opener.style.cssText = 'position:absolute;inset-inline-end:6px;inset-block-start:50%;transform:translateY(-50%)';
        visible.style.paddingInlineEnd = '2rem';
        visible.style.boxSizing = 'border-box';
        visible.addEventListener('keydown', event => {
            if (event.altKey && event.key === 'ArrowDown') {
                event.preventDefault();
                open();
            }
        });
        calendar.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                event.preventDefault();
                calendar.hidePopover();
                opener.focus();
            }
        });
    }

    function prepareLocalizedField(original) {
        if (!original || original.dataset.localeEnhanced === 'true' || original.disabled) return;
        const originalType = original.type;
        if (!['date', 'datetime-local', 'number'].includes(originalType)) return;

        const valueDescriptor = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');
        if (!valueDescriptor || !valueDescriptor.get || !valueDescriptor.set) return;

        const form = original.form;
        const visible = document.createElement('input');
        visible.type = 'text';
        visible.className = original.className;
        visible.style.cssText = original.style.cssText;
        visible.placeholder = original.placeholder || (
            originalType === 'number' ? 'عدد را به فارسی وارد کنید'
                : (originalType === 'date' ? '۱۴۰۵/۰۸/۲۷' : '۱۴۰۵/۰۸/۲۷ ۱۴:۳۰')
        );
        visible.autocomplete = 'off';
        visible.inputMode = originalType === 'number' ? 'decimal' : 'numeric';
        visible.dir = 'rtl';
        visible.required = original.required;
        visible.readOnly = original.readOnly;
        visible.dataset.noLocale = 'true';
        visible.dataset.localeVisible = originalType;

        if (original.id) {
            visible.id = original.id + '-fa';
            document.querySelectorAll('label[for]').forEach(label => {
                if (label.htmlFor === original.id) label.htmlFor = visible.id;
            });
        }
        ['aria-label', 'aria-describedby', 'aria-invalid', 'title'].forEach(attribute => {
            if (original.hasAttribute(attribute)) visible.setAttribute(attribute, original.getAttribute(attribute));
        });

        original.dataset.localeEnhanced = 'true';
        original.dataset.noLocale = 'true';
        original.required = false;
        original.tabIndex = -1;
        original.setAttribute('aria-hidden', 'true');
        original.style.display = 'none';
        original.after(visible);
        if (originalType !== 'number') attachJalaliCalendar(original, visible);

        const refresh = () => {
            if (originalType === 'number') {
                const raw = valueDescriptor.get.call(original);
                visible.value = raw === '' ? '' : groupedNumber.format(Number(raw));
            } else {
                updateDateField(original, visible, valueDescriptor);
            }
        };

        try {
            Object.defineProperty(original, 'value', {
                configurable: true,
                get() { return valueDescriptor.get.call(this); },
                set(value) {
                    valueDescriptor.set.call(this, value);
                    refresh();
                }
            });
        } catch (_) {}

        refresh();

        visible.addEventListener('input', () => {
            if (originalType === 'number') {
                const result = normalizeNumber(visible.value, original);
                visible.setCustomValidity(result.valid ? '' : 'عدد واردشده معتبر نیست یا خارج از محدوده مجاز است.');
                if (result.valid) valueDescriptor.set.call(original, result.value);
                return;
            }

            const iso = jalaliInputToIso(visible.value, originalType === 'datetime-local');
            valueDescriptor.set.call(original, iso || '');
            const invalid = !iso && visible.value.trim() !== '';
            const outOfRange = iso && (original.validity.rangeUnderflow || original.validity.rangeOverflow);
            visible.setCustomValidity(invalid
                ? (originalType === 'date' ? 'تاریخ شمسی معتبر وارد کنید. نمونه: ۱۴۰۵/۰۸/۲۷'
                    : 'تاریخ و ساعت شمسی معتبر وارد کنید. نمونه: ۱۴۰۵/۰۸/۲۷ ۱۴:۳۰')
                : (outOfRange ? 'تاریخ واردشده خارج از محدودهٔ مجاز است.' : ''));
        });

        if (form && form.dataset.localeSubmitBound !== 'true') {
            form.dataset.localeSubmitBound = 'true';
            form.addEventListener('submit', event => {
                const fields = form.querySelectorAll('[data-locale-visible]');
                for (const field of fields) {
                    field.dispatchEvent(new Event('input', { bubbles: true }));
                    if (!field.checkValidity()) {
                        event.preventDefault();
                        field.reportValidity();
                        return;
                    }
                }
            }, true);

            form.addEventListener('reset', () => {
                requestAnimationFrame(() => {
                    form.querySelectorAll('[data-locale-enhanced="true"]').forEach(input => {
                        const sibling = input.nextElementSibling;
                        const visibleInput = sibling?.matches('[data-locale-visible]')
                            ? sibling : sibling?.querySelector('[data-locale-visible]');
                        if (!visibleInput || !visibleInput.dataset.localeVisible) return;
                        const descriptor = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');
                        if (input.type === 'number') {
                            const raw = descriptor.get.call(input);
                            visibleInput.value = raw === '' ? '' : groupedNumber.format(Number(raw));
                        } else {
                            updateDateField(input, visibleInput, descriptor);
                        }
                        visibleInput.setCustomValidity('');
                    });
                });
            });
        }
    }

    function skipTextNode(node) {
        const parent = node.parentElement;
        if (!parent) return false;
        if (parent.closest('[dir="ltr"],[data-no-locale],script,style,textarea,pre,code,kbd,samp')) return true;
        return /^(INPUT|TEXTAREA|SELECT|OPTION|SCRIPT|STYLE|CODE|PRE)$/i.test(parent.tagName);
    }

    function localize(root) {
        if (!root) return;
        if (root.nodeType === Node.ELEMENT_NODE) {
            if (root.matches('input[type="date"],input[type="datetime-local"],input[type="number"]')) prepareLocalizedField(root);
            root.querySelectorAll?.('input[type="date"],input[type="datetime-local"],input[type="number"]')
                .forEach(prepareLocalizedField);
        }

        const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
        const nodes = [];
        let node;
        while ((node = walker.nextNode())) {
            if (!skipTextNode(node) && /[0-9]/.test(node.nodeValue || '')) nodes.push(node);
        }
        nodes.forEach(textNode => {
            const localized = localizeText(textNode.nodeValue);
            if (localized !== textNode.nodeValue) textNode.nodeValue = localized;
        });
    }

    function start() {
        localize(document.body);
        const observer = new MutationObserver(records => {
            records.forEach(record => record.addedNodes.forEach(node => {
                if (node.nodeType === Node.TEXT_NODE) {
                    if (!skipTextNode(node)) node.nodeValue = localizeText(node.nodeValue);
                } else if (node.nodeType === Node.ELEMENT_NODE) {
                    localize(node);
                }
            }));
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start, { once: true });
    } else {
        start();
    }
})();
