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
        let high = baseDay + 370;
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
        if (!withTime) return datePart;
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
        const fullDate = /(^|[^\d])((?:19|20)\d{2})[/-](0?[1-9]|1[0-2])[/-](0?[1-9]|[12]\d|3[01])(?:[ T](\d{1,2}):([0-5]\d)(?::([0-5]\d))?)?/g;
        value = value.replace(fullDate, (full, prefix, year, month, day, hour, minute, second) => {
            const utcHour = hour === undefined ? 8 : Number(hour) - 3;
            const utcMinute = hour === undefined ? 30 : Number(minute || 0) - 30;
            const date = new Date(Date.UTC(Number(year), Number(month) - 1, Number(day), utcHour, utcMinute, Number(second || 0)));
            return prefix + (formatJalaliDate(date, hour !== undefined) || full.slice(prefix.length));
        });

        const monthOnly = /(^|[^\d])((?:19|20)\d{2})-(0?[1-9]|1[0-2])(?![\/\d])/g;
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
        const date = dateFromIso(raw);
        visible.value = (date && formatJalaliDate(date, original.type === 'datetime-local')) || raw;
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
        ['aria-label', 'aria-describedby', 'title'].forEach(attribute => {
            if (original.hasAttribute(attribute)) visible.setAttribute(attribute, original.getAttribute(attribute));
        });

        original.dataset.localeEnhanced = 'true';
        original.dataset.noLocale = 'true';
        original.required = false;
        original.tabIndex = -1;
        original.setAttribute('aria-hidden', 'true');
        original.style.display = 'none';
        original.after(visible);

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
            visible.setCustomValidity(iso || visible.value === ''
                ? ''
                : 'تاریخ شمسی معتبر نیست. نمونه: ۱۴۰۵/۰۸/۲۷ ۱۴:۳۰');
            if (iso) valueDescriptor.set.call(original, iso);
            else if (visible.value === '') valueDescriptor.set.call(original, '');
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
                        const visibleInput = input.nextElementSibling;
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