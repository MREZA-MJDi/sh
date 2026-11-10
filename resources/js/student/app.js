// کارتابل آکادمی شیخان — تعامل‌های سمت مرورگر (بدون بک‌اند)
const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];
const store = { set: (k, v) => { try { localStorage.setItem(k, v) } catch {} } };

// منوی موبایل
const side = $('.side'), scrim = $('.scrim');
const menu = open => { side?.classList.toggle('open', open); scrim?.classList.toggle('open', open); };
$$('[data-menu]').forEach(b => b.addEventListener('click', () => menu(true)));
scrim?.addEventListener('click', () => menu(false));
document.addEventListener('keydown', e => e.key === 'Escape' && menu(false));

// انتخاب فرزند (اولیاء)
$('[data-child]')?.addEventListener('change', e => {
    const u = new URL(location.href); u.searchParams.set('child', e.target.value); location.href = u;
});

// پوسته‌ی روشن/تیره
$('[data-theme-toggle]')?.addEventListener('click', () => {
    const cur = document.documentElement.dataset.theme || (matchMedia('(prefers-color-scheme:dark)').matches ? 'dark' : 'light');
    const next = cur === 'dark' ? 'light' : 'dark';
    document.documentElement.dataset.theme = next; store.set('theme', next);
});

// فیلتر تب‌ها: <div data-tabs data-target="#id"> <button data-f="graded"> … <tr data-item data-cat="graded">
$$('[data-tabs]').forEach(t => t.addEventListener('click', e => {
    const b = e.target.closest('[data-f]'); if (!b) return;
    $$('button', t).forEach(x => x.classList.toggle('on', x === b));
    const scope = t.dataset.target ? $(t.dataset.target) : document;
    $$('[data-item]', scope).forEach(i => i.hidden = !(b.dataset.f === 'all' || i.dataset.cat === b.dataset.f));
}));

// آپلود فایل با کشیدن و رها کردن
$$('[data-drop]').forEach(z => {
    const input = $('input[type=file]', z), list = $('[data-files]', z);
    const render = () => {
        list.innerHTML = '';
        [...input.files].forEach(f => {
            const li = document.createElement('li'), a = document.createElement('span'), b = document.createElement('span');
            b.className = 'mu'; a.textContent = f.name; b.textContent = (f.size / 1048576).toFixed(1).replace('.', '٫') + ' MB';
            li.append(a, b); list.append(li);
        });
    };
    input.addEventListener('change', render);
    ['dragenter', 'dragover'].forEach(ev => z.addEventListener(ev, e => { e.preventDefault(); z.classList.add('over'); }));
    ['dragleave', 'drop'].forEach(ev => z.addEventListener(ev, e => { e.preventDefault(); z.classList.remove('over'); }));
    z.addEventListener('drop', e => { input.files = e.dataTransfer.files; render(); });
});

// شمارنده‌ی کاراکتر
$$('textarea[data-count]').forEach(t => {
    const max = +t.dataset.count, c = document.createElement('div'); c.className = 'cnt'; t.after(c);
    const up = () => c.textContent = `${t.value.length.toLocaleString('fa')} / ${max.toLocaleString('fa')}`;
    t.maxLength = max; t.addEventListener('input', up); up();
});

// پیام‌های موقت
$$('[data-toast]').forEach(t => setTimeout(() => { t.style.opacity = 0; setTimeout(() => t.remove(), 400); }, 5000));

// پخش‌کننده‌ی نمایشی اسپات پلیر (در پروژه‌ی واقعی با SDK اسپات پلیر جایگزین شود)
$$('[data-player]').forEach(p => {
    const btn = $('[data-play]', p), bar = $('.bar i', p), time = $('[data-time]', p), wm = $('[data-wm]', p), total = +p.dataset.total || 3120;
    let t = 0, timer = null, speed = 1;
    const fmt = s => [Math.floor(s / 60), Math.floor(s % 60)].map(n => n.toLocaleString('fa', { minimumIntegerDigits: 2 })).join(':');
    const draw = () => { bar.style.width = (t / total * 100) + '%'; time.textContent = `${fmt(t)} / ${fmt(total)}`; };
    const toggle = () => {
        if (timer) { clearInterval(timer); timer = null; btn.textContent = '▶'; return; }
        btn.textContent = '❚❚';
        timer = setInterval(() => { t = Math.min(total, t + speed); draw(); if (t >= total) toggle(); }, 1000);
    };
    btn.addEventListener('click', toggle);
    $('.bar', p).addEventListener('click', e => { const r = e.currentTarget.getBoundingClientRect(); t = Math.max(0, Math.min(total, (r.right - e.clientX) / r.width * total)); draw(); });
    $('[data-speed]', p)?.addEventListener('change', e => speed = +e.target.value);
    setInterval(() => { wm.style.top = (8 + Math.random() * 78) + '%'; wm.style.insetInlineStart = (4 + Math.random() * 60) + '%'; }, 4000);
    draw();
});
