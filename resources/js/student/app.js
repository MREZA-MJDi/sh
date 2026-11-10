import '../rich-text.js';
// کارتابل دانش‌آموز — تعامل‌های مستقل سمت مرورگر
const $ = (selector, root = document) => root.querySelector(selector);
const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];

const storage = {
    get(key) {
        try {
            return window.localStorage.getItem(key);
        } catch {
            return null;
        }
    },
    set(key, value) {
        try {
            window.localStorage.setItem(key, value);
        } catch {
            // Storage may be unavailable in private or restricted contexts.
        }
    },
};

function initMobileMenu() {
    const side = $('.side');
    const scrim = $('.scrim');
    const buttons = $$('[data-menu]');

    if (!side || !scrim || buttons.length === 0) {
        return;
    }

    const setMenuOpen = (isOpen) => {
        side.classList.toggle('open', isOpen);
        scrim.classList.toggle('open', isOpen);
        buttons.forEach((button) => button.setAttribute('aria-expanded', String(isOpen)));
    };

    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            setMenuOpen(!side.classList.contains('open'));
        });
    });

    scrim.addEventListener('click', () => setMenuOpen(false));

    $$('.side a[href^="#"]').forEach((link) => {
        link.addEventListener('click', () => setMenuOpen(false));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setMenuOpen(false);
        }
    });
}

function initThemeToggle() {
    const button = $('[data-theme-toggle]');
    if (!button) {
        return;
    }

    const root = document.documentElement;
    const systemPrefersDark = window.matchMedia
        ? window.matchMedia('(prefers-color-scheme: dark)').matches
        : false;

    const savedTheme = storage.get('theme');
    if (savedTheme === 'light' || savedTheme === 'dark') {
        root.dataset.theme = savedTheme;
    }

    button.addEventListener('click', () => {
        const currentTheme = root.dataset.theme || (systemPrefersDark ? 'dark' : 'light');
        const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';

        root.dataset.theme = nextTheme;
        storage.set('theme', nextTheme);
        button.setAttribute('aria-pressed', String(nextTheme === 'dark'));
    });

    button.setAttribute(
        'aria-pressed',
        String((root.dataset.theme || (systemPrefersDark ? 'dark' : 'light')) === 'dark')
    );
}

function initTabs() {
    $$('[data-tabs]').forEach((tabs) => {
        tabs.addEventListener('click', (event) => {
            const button = event.target.closest('[data-f]');
            if (!button || !tabs.contains(button)) {
                return;
            }

            const selector = tabs.dataset.target;
            const scope = selector ? $(selector) : document;
            if (!scope) {
                return;
            }

            $$('button[data-f]', tabs).forEach((item) => {
                const isActive = item === button;
                item.classList.toggle('on', isActive);
                item.setAttribute('aria-pressed', String(isActive));
            });

            $$('[data-item]', scope).forEach((item) => {
                item.hidden = !(
                    button.dataset.f === 'all' ||
                    item.dataset.cat === button.dataset.f
                );
            });
        });
    });
}

function initFileDropzones() {
    $$('[data-drop]').forEach((zone) => {
        const input = $('input[type="file"]', zone);
        const list = $('[data-files]', zone);

        if (!input || !list) {
            return;
        }

        const renderFiles = () => {
            list.replaceChildren();

            Array.from(input.files || []).forEach((file) => {
                const item = document.createElement('li');
                const name = document.createElement('span');
                const size = document.createElement('span');

                name.textContent = file.name;
                size.className = 'mu';
                size.textContent = (file.size / 1048576).toFixed(1).replace('.', '٫') + ' MB';

                item.append(name, size);
                list.append(item);
            });
        };

        input.addEventListener('change', renderFiles);

        ['dragenter', 'dragover'].forEach((eventName) => {
            zone.addEventListener(eventName, (event) => {
                event.preventDefault();
                zone.classList.add('over');
            });
        });

        ['dragleave', 'drop'].forEach((eventName) => {
            zone.addEventListener(eventName, (event) => {
                event.preventDefault();
                zone.classList.remove('over');
            });
        });

        zone.addEventListener('drop', (event) => {
            const droppedFiles = event.dataTransfer?.files;
            if (!droppedFiles || typeof DataTransfer === 'undefined') {
                return;
            }

            try {
                const transfer = new DataTransfer();
                Array.from(droppedFiles).forEach((file) => transfer.items.add(file));
                input.files = transfer.files;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            } catch {
                // Keep the native file input usable if the browser blocks assignment.
            }
        });
    });
}

function initCharacterCounters() {
    $$('textarea[data-count]').forEach((textarea) => {
        const maximum = Number.parseInt(textarea.dataset.count, 10);
        if (!Number.isFinite(maximum) || maximum <= 0) {
            return;
        }

        const counter = document.createElement('div');
        counter.className = 'cnt';
        counter.setAttribute('aria-live', 'polite');
        textarea.insertAdjacentElement('afterend', counter);
        textarea.maxLength = maximum;

        const update = () => {
            counter.textContent =
                textarea.value.length.toLocaleString('fa') +
                ' / ' +
                maximum.toLocaleString('fa');
        };

        textarea.addEventListener('input', update);
        update();
    });
}

function initToasts() {
    $$('[data-toast]').forEach((toast) => {
        const duration = Number.parseInt(toast.dataset.duration, 10);
        const delay = Number.isFinite(duration) && duration > 0 ? duration : 5000;

        window.setTimeout(() => {
            toast.style.opacity = '0';
            window.setTimeout(() => toast.remove(), 400);
        }, delay);
    });
}

function initDemoPlayers() {
    $$('[data-player]').forEach((player) => {
        const button = $('[data-play]', player);
        const progress = $('.bar i', player);
        const seekbar = $('.bar', player);
        const timeLabel = $('[data-time]', player);
        const watermark = $('[data-wm]', player);
        const configuredTotal = Number.parseInt(player.dataset.total, 10);
        const total = Number.isFinite(configuredTotal) && configuredTotal > 0
            ? configuredTotal
            : 3120;

        if (!button || !progress || !seekbar || !timeLabel) {
            return;
        }

        let elapsed = 0;
        let playbackTimer = null;
        let watermarkTimer = null;
        let speed = 1;

        const formatTime = (seconds) => {
            const safeSeconds = Math.max(0, Math.floor(seconds));
            const minutes = Math.floor(safeSeconds / 60);
            const remainder = safeSeconds % 60;

            return [minutes, remainder]
                .map((value) => value.toLocaleString('fa', { minimumIntegerDigits: 2 }))
                .join(':');
        };

        const draw = () => {
            const percentage = Math.min(100, Math.max(0, (elapsed / total) * 100));
            progress.style.width = percentage + '%';
            timeLabel.textContent = formatTime(elapsed) + ' / ' + formatTime(total);
            seekbar.setAttribute('aria-valuenow', String(Math.floor(elapsed)));
        };

        const stop = () => {
            if (playbackTimer !== null) {
                window.clearInterval(playbackTimer);
                playbackTimer = null;
            }

            button.textContent = '▶';
            button.setAttribute('aria-pressed', 'false');
        };

        const togglePlayback = () => {
            if (playbackTimer !== null) {
                stop();
                return;
            }

            if (elapsed >= total) {
                elapsed = 0;
                draw();
            }

            button.textContent = '❚❚';
            button.setAttribute('aria-pressed', 'true');

            playbackTimer = window.setInterval(() => {
                elapsed = Math.min(total, elapsed + speed);
                draw();

                if (elapsed >= total) {
                    stop();
                }
            }, 1000);
        };

        button.addEventListener('click', togglePlayback);

        seekbar.setAttribute('role', 'slider');
        seekbar.setAttribute('tabindex', '0');
        seekbar.setAttribute('aria-label', 'موقعیت پخش');
        seekbar.setAttribute('aria-valuemin', '0');
        seekbar.setAttribute('aria-valuemax', String(total));

        const seekTo = (clientX) => {
            const bounds = seekbar.getBoundingClientRect();
            if (bounds.width <= 0) {
                return;
            }

            const ratio = Math.max(0, Math.min(1, (bounds.right - clientX) / bounds.width));
            elapsed = ratio * total;
            draw();
        };

        seekbar.addEventListener('click', (event) => seekTo(event.clientX));
        seekbar.addEventListener('keydown', (event) => {
            if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
                return;
            }

            event.preventDefault();
            elapsed = Math.max(0, Math.min(total, elapsed + (event.key === 'ArrowLeft' ? 5 : -5)));
            draw();
        });

        $('[data-speed]', player)?.addEventListener('change', (event) => {
            const nextSpeed = Number.parseFloat(event.target.value);
            if (Number.isFinite(nextSpeed) && nextSpeed > 0) {
                speed = nextSpeed;
            }
        });

        if (watermark && !(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches)) {
            watermarkTimer = window.setInterval(() => {
                watermark.style.top = (8 + Math.random() * 78) + '%';
                watermark.style.insetInlineStart = (4 + Math.random() * 60) + '%';
            }, 4000);
        }

        window.addEventListener('pagehide', () => {
            stop();
            if (watermarkTimer !== null) {
                window.clearInterval(watermarkTimer);
            }
        }, { once: true });

        draw();
    });
}


function initChildSwitcher() {
    const selector = $('[data-child]');
    if (!selector) return;

    selector.addEventListener('change', () => {
        const url = new URL(window.location.href);
        if (selector.value) {
            url.searchParams.set('child', selector.value);
        } else {
            url.searchParams.delete('child');
        }
        window.location.assign(url.toString());
    });
}

function initDashboardNavigation() {
    const links = $('[data-dashboard-nav-target]');
    if (!links.length || typeof window.IntersectionObserver === 'undefined') {
        return;
    }

    const sections = [...new Set(links.map((link) => link.dataset.dashboardNavTarget))]
        .map((id) => document.getElementById(id))
        .filter(Boolean);

    if (!sections.length) {
        return;
    }

    const setActive = (id) => {
        links.forEach((link) => {
            const active = link.dataset.dashboardNavTarget === id;
            link.classList.toggle('on', active);
            if (active) link.setAttribute('aria-current', 'location');
            else link.removeAttribute('aria-current');
        });
    };

    const observer = new IntersectionObserver((entries) => {
        const visible = entries
            .filter((entry) => entry.isIntersecting)
            .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];

        if (visible) setActive(visible.target.id);
    }, {
        rootMargin: '-18% 0px -68% 0px',
        threshold: [0, 0.12, 0.3, 0.55],
    });

    sections.forEach((section) => observer.observe(section));
}

function initStudentDashboard() {
    initMobileMenu();
    initChildSwitcher();
    initDashboardNavigation();
    initThemeToggle();
    initTabs();
    initFileDropzones();
    initCharacterCounters();
    initToasts();
    initDemoPlayers();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initStudentDashboard, { once: true });
} else {
    initStudentDashboard();
}
