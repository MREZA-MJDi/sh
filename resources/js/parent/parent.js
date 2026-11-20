import '../shared/iranian-locale.js';
/* فقط رفتار (action) + رندر نمودار از data-chart-config؛ هیچ داده‌ای در JS نیست. */
(function () {
  'use strict';
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var FA = '۰۱۲۳۴۵۶۷۸۹';
  var fa = function (n) { return Number(n).toLocaleString('fa-IR'); };
  var faStr = function (s) { return String(s).replace(/\d/g, function (d) { return FA[d]; }); };
  var root = document.documentElement;

  /* ---------- Toast ---------- */
  var toastEl = $('#t'), toastT;
  window.shToast = function (msg) {
    if (!toastEl) return;
    toastEl.textContent = msg; toastEl.classList.add('on');
    clearTimeout(toastT); toastT = setTimeout(function () { toastEl.classList.remove('on'); }, 2200);
  };
  document.addEventListener('click', function (e) {
    var t = e.target.closest('[data-toast]');
    if (t) { e.preventDefault(); window.shToast(t.getAttribute('data-toast')); }
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      parentMenus.forEach(function (menu) { menu.open = false; });
      closeNav(true);
      return;
    }
    if (e.key === 'Tab' && navToggle && navSidebar && navToggle.getAttribute('aria-expanded') === 'true') {
      var focusable = $('a[href],button:not([disabled]),input:not([disabled]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])', navSidebar)
        .filter(function (el) { return el.offsetWidth || el.offsetHeight || el.getClientRects().length; });
      if (!focusable.length) { e.preventDefault(); navSidebar.focus(); return; }
      var first = focusable[0], last = focusable[focusable.length - 1];
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    }
  });

  /* ==========================================================================
     SVG charts — رنگ‌ها از متغیرهای CSS می‌آیند تا با تم/پالت هماهنگ بمانند.
     محور زمان از راست به چپ است.
     ========================================================================== */
let chartId = 0;
const PAD = { l: 24, r: 8, t: 10, b: 24 };
function gridLines(w, h, min, max, y) {
    return [0, 1, 2, 3, 4].map(i => {
        const v = min + (max - min) * i / 4;
        return `<line class="gl" x1="${PAD.l}" x2="${w - PAD.r}" y1="${y(v)}" y2="${y(v)}"/>` +
            `<text class="ax" x="${PAD.l - 4}" y="${y(v) + 3}" text-anchor="end">${fa(v)}</text>`;
    }).join('');
}
/** Line chart. series: [{ name, c: '--var', data, area?, dash? }] */
function lineChart(labels, series, options = {}) {
    const id = ++chartId, n = labels.length, w = options.w || 360, h = options.h || 190;
    const values = series.reduce((all, item) => all.concat(item.data), []).filter(Number.isFinite);
    const min = Number.isFinite(options.min) ? options.min : Math.min(0, ...values);
    const rawMax = Number.isFinite(options.max) ? options.max : Math.max(20, ...values);
    const max = rawMax <= min ? min + 1 : rawMax;
    const iw = w - PAD.l - PAD.r, ih = h - PAD.t - PAD.b;
    const x = i => PAD.l + iw * (n < 2 ? 0.5 : 1 - i / (n - 1));
    const y = v => PAD.t + ih * (1 - (v - min) / (max - min));
    const xLabels = labels.map((l, i) => `<text class="ax" x="${x(i)}" y="${h - 6}" text-anchor="middle">${l}</text>`).join('');
    const lines = series.map((s, k) => {
        const pts = s.data.map((v, i) => Number.isFinite(v) ? [x(i), y(v), i] : null);
        const segments = [];
        let segment = [];
        pts.forEach(point => {
            if (point) segment.push(point);
            else if (segment.length) { segments.push(segment); segment = []; }
        });
        if (segment.length) segments.push(segment);
        const paths = segments.map((group, segmentIndex) => {
            const d = group.map((p, i) => `${i ? 'L' : 'M'}${p[0].toFixed(1)},${p[1].toFixed(1)}`).join(' ');
            const area = s.area && group.length > 1
                ? `<path d="${d} L${group[group.length - 1][0]},${y(min)} L${group[0][0]},${y(min)}Z" fill="url(#lg${id}${k})"/>`
                : '';
            return `${area}<path d="${d}" fill="none" stroke-width="${s.dash ? 2 : 3}" stroke-linecap="round" stroke-linejoin="round" ${s.dash ? 'stroke-dasharray="5 4"' : ''} style="stroke:var(${s.c})"/>`;
        }).join('');
        const gradient = s.area
            ? `<defs><linearGradient id="lg${id}${k}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" style="stop-color:var(${s.c});stop-opacity:.35"/><stop offset="1" style="stop-color:var(${s.c});stop-opacity:0"/></linearGradient></defs>`
            : '';
        const dots = s.dash ? '' : pts.map(p => p ? `<circle cx="${p[0]}" cy="${p[1]}" r="4" stroke-width="2" style="fill:var(--sf);stroke:var(${s.c})"><title>${s.name} ${labels[p[2]]}: ${fa(s.data[p[2]])}</title></circle>` : '').join('');
        return `${gradient}${paths}${dots}`;
    }).join('');
    return `<svg class="chart" viewBox="0 0 ${w} ${h}" role="img" aria-label="نمودار خطی">${gridLines(w, h, min, max, y)}${xLabels}${lines}</svg>`;
}
/** Bar chart, grouped or stacked. series: [{ name, c, data }] */
function barChart(labels, series, options = {}) {
    const w = options.w || 360, h = options.h || 190, stacked = !!options.stacked;
    const totals = labels.map((_, i) => series.reduce((sum, item) => sum + (Number(item.data[i]) || 0), 0));
    const values = series.reduce((all, item) => all.concat(item.data.map(Number)), []);
    const rawMax = Number.isFinite(options.max) ? options.max : Math.max(1, ...(stacked ? totals : values));
    const max = rawMax * 1.1;
    const iw = w - PAD.l - PAD.r, ih = h - PAD.t - PAD.b, slot = iw / labels.length;
    const bw = stacked ? slot * 0.5 : slot * 0.7 / series.length;
    const y = v => PAD.t + ih * (1 - v / max);
    const bars = labels.map((label, i) => {
        const cx = w - PAD.r - slot * (i + 0.5);
        let acc = 0;
        const rects = series.map((s, k) => {
            const v = s.data[i];
            const rect = stacked
                ? `<rect x="${cx - bw / 2}" y="${y(acc + v)}" width="${bw}" height="${y(acc) - y(acc + v)}"`
                : `<rect x="${cx - bw * series.length / 2 + k * bw}" y="${y(v)}" width="${bw - 2}" height="${y(0) - y(v)}"`;
            acc += v;
            return `${rect} rx="3" style="fill:var(${s.c})"><title>${s.name} — ${label}: ${fa(v)}</title></rect>`;
        }).join('');
        return `${rects}<text class="ax" x="${cx}" y="${h - 6}" text-anchor="middle">${label}</text>`;
    }).join('');
    return `<svg class="chart" viewBox="0 0 ${w} ${h}" role="img" aria-label="نمودار ستونی">${gridLines(w, h, 0, max, y)}${bars}</svg>`;
}
/** Donut chart with legend. parts: [{ l, v, c }] */
function donut(parts, big, small) {
    const R = 42, C = 2 * Math.PI * R, total = parts.reduce((a, p) => a + p.v, 0) || 1;
    let offset = 0;
    const arcs = parts.map(p => {
        const len = C * p.v / total;
        const arc = `<circle cx="60" cy="60" r="${R}" fill="none" stroke-width="16" transform="rotate(-90 60 60)" stroke-dasharray="${Math.max(len - 1.5, 0)} ${C}" stroke-dashoffset="${-offset}" style="stroke:var(${p.c})"><title>${p.l}: ${fa(p.v)}</title></circle>`;
        offset += len;
        return arc;
    }).join('');
    const items = parts.map(p => `<li><i style="background:var(${p.c})"></i>${p.l}<b>${fa(p.v)}</b></li>`).join('');
    return `<div class="donut"><svg viewBox="0 0 120 120" role="img" aria-label="نمودار دایره‌ای">${arcs}` +
        `<text x="60" y="62" text-anchor="middle" class="big">${big}</text><text x="60" y="78" text-anchor="middle" class="ax">${small}</text></svg><ul class="lg">${items}</ul></div>`;
}
/** Radar chart (scale 0–20). series: [{ name, c, data }] */
function radar(labels, series) {
    const cx = 110, cy = 100, R = 68, n = labels.length;
    const point = (i, r) => { const a = -Math.PI / 2 - 2 * Math.PI * i / n; return [cx + r * Math.cos(a), cy + r * Math.sin(a)]; };
    const ring = f => labels.map((_, i) => point(i, R * f).join(',')).join(' ');
    const rings = [0.25, 0.5, 0.75, 1].map(f => `<polygon class="gl" fill="none" points="${ring(f)}"/>`).join('');
    const axes = labels.map((l, i) => {
        const [x, y] = point(i, R), [tx, ty] = point(i, R + 14);
        return `<line class="gl" x1="${cx}" y1="${cy}" x2="${x}" y2="${y}"/><text class="ax" x="${tx}" y="${ty + 3}" text-anchor="middle">${l}</text>`;
    }).join('');
    const shapes = series.map(s => `<polygon points="${s.data.map((v, i) => point(i, R * v / 20).join(',')).join(' ')}" stroke-width="2" style="stroke:var(${s.c});fill:color-mix(in srgb,var(${s.c}) 18%,transparent)"><title>${s.name}</title></polygon>`).join('');
    return `<svg class="chart" viewBox="0 0 220 200" role="img" aria-label="نمودار راداری">${rings}${axes}${shapes}</svg>`;
}

  /* ---------- رندر نمودارها از data-chart / data-chart-config ---------- */
  var esc = function (s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  };
  var color = function (c) { return /^--[a-z0-9-]+$/i.test(c || '') ? c : '--pri'; };
  var nums = function (a) { return (a || []).map(Number); };
  var legendHtml = function (series) {
    return '<div class="leg">' + series.map(function (s) {
      return '<span><i style="background:var(' + s.c + ')"></i>' + s.name + '</span>';
    }).join('') + '</div>';
  };

  $$('[data-chart]').forEach(function (el) {
    var cfg; try { cfg = JSON.parse(el.getAttribute('data-chart-config') || '{}'); } catch (e) { return; }
    var type = el.getAttribute('data-chart'), o = cfg.options || {}, html = '';
    var labels = (cfg.labels || []).map(esc);
    var series = (cfg.series || []).map(function (s) {
      return { name: esc(s.name), c: color(s.c), data: nums(s.data), area: !!s.area, dash: !!s.dash };
    });
    var hasData = series.length && series.every(function (s) { return s.data.length === labels.length; });

    if (type === 'donut') {
      var parts = (cfg.parts || []).map(function (p) { return { l: esc(p.l), v: Number(p.v) || 0, c: color(p.c) }; });
      if (parts.some(function (p) { return p.v > 0; })) html = donut(parts, faStr(esc(cfg.big)), esc(cfg.small));
    } else if (type === 'line' && hasData && labels.length >= 1) {
      html = lineChart(labels, series, o) + legendHtml(series);
    } else if (type === 'bar' && hasData && labels.length >= 1) {
      html = barChart(labels, series, o) + legendHtml(series);
    } else if (type === 'radar' && hasData && labels.length >= 3) {
      html = radar(labels, series) + legendHtml(series);
    }
    if (html) {
      el.innerHTML = html;
    } else {
      el.innerHTML = '<p class="chart-empty" role="status">برای این نمودار هنوز داده کافی ثبت نشده است.</p>';
    }
  });

  /* ---------- ارقام فارسی برای هر عنصر با data-fa ---------- */
  function faDigits(el) {
    var w = document.createTreeWalker(el, NodeFilter.SHOW_TEXT, {
      acceptNode: function (n) {
        var p = n.parentNode && n.parentNode.nodeName;
        if (/^(script|style|textarea|option)$/i.test(p)) return NodeFilter.FILTER_REJECT;
        return /\d/.test(n.nodeValue) ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
      }
    });
    var list = [], n; while ((n = w.nextNode())) list.push(n);
    list.forEach(function (t) { t.nodeValue = faStr(t.nodeValue); });
  }
  $$('[data-fa]').forEach(faDigits);

  /* Accessible theme and palette state. */
  var tg = $('#themeToggle');
  var syncTheme = function () {
    if (!tg) return;
    var dark = root.dataset.theme === 'dark' || (!root.dataset.theme && window.matchMedia('(prefers-color-scheme: dark)').matches);
    tg.setAttribute('aria-pressed', dark ? 'true' : 'false');
    tg.setAttribute('aria-label', dark ? 'فعال‌سازی تم روشن' : 'فعال‌سازی تم تیره');
  };
  syncTheme();
  if (tg) tg.addEventListener('click', function () {
    var cur = root.dataset.theme || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    var next = cur === 'dark' ? 'light' : 'dark';
    root.dataset.theme = next;
    try { localStorage.setItem('pa-theme', next); } catch (e) {}
    syncTheme();
  });
  var paletteButtons = $('[data-pal]');
  var syncPalette = function () {
    paletteButtons.forEach(function (button) {
      button.setAttribute('aria-pressed', (button.getAttribute('data-pal') || '') === (root.dataset.pal || '') ? 'true' : 'false');
    });
  };
  syncPalette();
  paletteButtons.forEach(function (b) {
    b.addEventListener('click', function () {
      var p = b.getAttribute('data-pal') || '';
      if (p) root.dataset.pal = p; else delete root.dataset.pal;
      try { localStorage.setItem('pa-pal', p); } catch (e) {}
      syncPalette();
    });
  });

  /* Responsive parent drawer with focus management. */
  var navToggle = $('#parentNavToggle'), navSidebar = $('#parentSidebar'), navBackdrop = $('#parentNavBackdrop');
  var topbar = $('[data-parent-topbar]');
  var syncTopbarHeight = function () { if (topbar) root.style.setProperty('--parent-topbar-height', topbar.offsetHeight + 'px'); };
  syncTopbarHeight();
  if (window.ResizeObserver && topbar) new ResizeObserver(syncTopbarHeight).observe(topbar);
  else window.addEventListener('resize', syncTopbarHeight);
  var closeNav = function (restoreFocus) {
    if (!navToggle || !navSidebar || !navBackdrop) return;
    var wasOpen = navToggle.getAttribute('aria-expanded') === 'true';
    document.body.classList.remove('parent-nav-open');
    navToggle.setAttribute('aria-expanded', 'false');
    navToggle.setAttribute('aria-label', 'باز کردن منوی اصلی');
    navBackdrop.hidden = true;
    if (restoreFocus && wasOpen) navToggle.focus();
  };
  var openNav = function () {
    if (!navToggle || !navSidebar || !navBackdrop) return;
    document.body.classList.add('parent-nav-open');
    navToggle.setAttribute('aria-expanded', 'true');
    navToggle.setAttribute('aria-label', 'بستن منوی اصلی');
    navBackdrop.hidden = false;
    navSidebar.focus();
  };
  if (navToggle) navToggle.addEventListener('click', function () {
    if (navToggle.getAttribute('aria-expanded') === 'true') closeNav(true); else openNav();
  });
  if (navBackdrop) navBackdrop.addEventListener('click', function () { closeNav(true); });
  if (navSidebar) navSidebar.addEventListener('click', function (event) { if (event.target.closest('a')) closeNav(false); });
  window.addEventListener('resize', function () { if (window.innerWidth > 800) closeNav(false); });

  var parentMenus = $('.parent-menu');
  parentMenus.forEach(function (menu) {
    menu.addEventListener('toggle', function () {
      if (menu.open) parentMenus.forEach(function (other) { if (other !== menu) other.open = false; });
    });
  });
  document.addEventListener('click', function (event) {
    parentMenus.forEach(function (menu) { if (!menu.contains(event.target)) menu.open = false; });
  });

  /* Search visible content on the current page only. */
  var sr = $('#globalSearch'), searchClear = $('#parentSearchClear'), searchStatus = $('#parentSearchResults');
  var searchable = $('.row, .c, .st, .parent-resource-card, .teacher-inbox-item');
  var updateSearch = function () {
    if (!sr) return;
    var q = sr.value.trim().toLocaleLowerCase(), visible = 0;
    searchable.forEach(function (el) {
      var match = !q || el.textContent.toLocaleLowerCase().indexOf(q) !== -1;
      el.hidden = !match;
      if (match) visible++;
    });
    if (searchClear) searchClear.hidden = !q;
    if (searchStatus) searchStatus.textContent = q ? (visible ? visible + ' نتیجه در این صفحه پیدا شد.' : 'نتیجه‌ای در این صفحه پیدا نشد.') : '';
  };
  if (sr) sr.addEventListener('input', updateSearch);
  if (searchClear && sr) searchClear.addEventListener('click', function () { sr.value = ''; updateSearch(); sr.focus(); });

  /* ---------- فرم جلسه: انتخاب زمان الزامی است ---------- */
  $$('[data-require-choice]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (!$('input[type=radio]:checked', f)) { e.preventDefault(); window.shToast(f.getAttribute('data-require-choice')); }
    });
  });

  /* ---------- پیام‌ها: اسکرول به آخرین پیام ---------- */
  var ms = $('.msgs'); if (ms) ms.scrollTop = ms.scrollHeight;

  /* ---------- جلوگیری از ارسال دوباره‌ی فرم‌ها ---------- */
  $$('form').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (e.defaultPrevented) return;
      if (f.getAttribute('data-busy')) { e.preventDefault(); return; }
      f.setAttribute('data-busy', '1');
      setTimeout(function () { f.removeAttribute('data-busy'); }, 1500);
    });
  });
})();
