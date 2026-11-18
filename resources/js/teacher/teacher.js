import '../shared/iranian-locale.js';
/* فقط رفتار (action)؛ هیچ داده‌ای در JS نیست. */
(function () {
  'use strict';
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var FA = '۰۱۲۳۴۵۶۷۸۹';
  var fa = function (n) { return String(n).replace(/\d/g, function (d) { return FA[d]; }); };
  var root = document.documentElement;

  /* Toast: هر عنصری با data-toast="متن" */
  var toastEl = $('#t'), toastT;
  window.shToast = function (msg) {
    if (!toastEl) return;
    toastEl.textContent = msg; toastEl.classList.add('on');
    clearTimeout(toastT); toastT = setTimeout(function () { toastEl.classList.remove('on'); }, 2200);
  };

  /* ارقام فارسی برای هر عنصر با data-fa */
  function faDigits(el) {
    var w = document.createTreeWalker(el, NodeFilter.SHOW_TEXT, {
      acceptNode: function (n) {
        var p = n.parentNode && n.parentNode.nodeName;
        if (/^(SCRIPT|STYLE|TEXTAREA|OPTION)$/.test(p)) return NodeFilter.FILTER_REJECT;
        return /\d/.test(n.nodeValue) ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
      }
    });
    var list = [], n; while ((n = w.nextNode())) list.push(n);
    list.forEach(function (t) { t.nodeValue = fa(t.nodeValue); });
  }
  $$('[data-fa]').forEach(faDigits);

  /* تم تیره/روشن و پالت رنگ */
  var tg = $('#themeToggle');
  if (tg) tg.addEventListener('click', function () {
    var cur = root.dataset.theme || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    var next = cur === 'dark' ? 'light' : 'dark';
    root.dataset.theme = next;
    try { localStorage.setItem('th-theme', next); } catch (e) {}
  });
  $$('[data-pal]').forEach(function (b) {
    b.addEventListener('click', function () {
      var p = b.getAttribute('data-pal');
      if (p) root.dataset.pal = p; else delete root.dataset.pal;
      try { localStorage.setItem('th-pal', p); } catch (e) {}
    });
  });

  /* جست‌وجوی زنده بین ردیف‌های صفحه */
  var sr = $('#globalSearch');
  if (sr) sr.addEventListener('input', function () {
    var q = sr.value.trim().toLowerCase();
    $$('main .row').forEach(function (el) { el.hidden = !!q && el.textContent.toLowerCase().indexOf(q) === -1; });
  });

  /* ---------- Modal افزودن/ویرایش ---------- */
  function closeModals() { $$('.md.on').forEach(function (m) { m.classList.remove('on'); }); }
  function openModal(btn) {
    var box = $('[data-modal-box="' + btn.getAttribute('data-modal') + '"]'); if (!box) return;
    var f = $('form', box), method = (btn.getAttribute('data-method') || 'POST').toUpperCase(), vals = {};
    try { vals = JSON.parse(btn.getAttribute('data-values') || '{}'); } catch (e) {}
    f.reset();
    f.setAttribute('action', btn.getAttribute('data-action') || '');
    var m = $('input[name="_method"]', f); if (m) { m.value = method; m.disabled = method === 'POST'; }
    Object.keys(vals).forEach(function (k) { var el = f.elements[k]; if (el && vals[k] != null) el.value = vals[k]; });
    $('[data-title]', box).textContent = method === 'POST' ? 'افزودن' : 'ویرایش';
    closeModals(); box.classList.add('on');
    var first = f.querySelector('input:not([type=hidden]),select'); if (first) first.focus();
  }

  /* ---------- حذف دومرحله‌ای ---------- */
  function disarm(b) {
    if (!b.hasAttribute('data-armed')) return;
    b.removeAttribute('data-armed'); b.textContent = b.getAttribute('data-label'); clearTimeout(b._t);
  }

  document.addEventListener('click', function (e) {
    var t = e.target.closest('[data-toast]');
    if (t) { e.preventDefault(); window.shToast(t.getAttribute('data-toast')); return; }

    var mo = e.target.closest('[data-modal]');
    if (mo) { e.preventDefault(); openModal(mo); return; }
    if (e.target.closest('[data-close]')) { closeModals(); return; }
    if (e.target.classList && e.target.classList.contains('md')) { closeModals(); return; }

    var d = e.target.closest('[data-delete]');
    $$('[data-delete][data-armed]').forEach(function (b) { if (b !== d) disarm(b); });
    if (d) {
      if (d.hasAttribute('data-armed')) { clearTimeout(d._t); d.closest('form').submit(); return; }
      d.setAttribute('data-label', d.textContent); d.setAttribute('data-armed', '1'); d.textContent = 'تأیید حذف';
      d._t = setTimeout(function () { disarm(d); }, 4000);
    }
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModals(); });

  /* ---------- حضور و غیاب: چرخش وضعیت حاضر ← غایب ← تأخیر ---------- */
  var rc = $('[data-rollcall]');
  if (rc) {
    var L = ['حاضر', 'غایب', 'تأخیر'], K = ['gn', 'rd', 'am'];
    var recount = function () {
      var c = [0, 0, 0];
      $$('input[name^="status["]', rc).forEach(function (i) { c[+i.value]++; });
      $$('[data-count]', rc).forEach(function (el) { el.textContent = fa(c[+el.getAttribute('data-count')]); });
    };
    rc.addEventListener('click', function (e) {
      var b = e.target.closest('[data-status]'); if (!b) return;
      var i = b.parentNode.querySelector('input[type=hidden]'), v = (+i.value + 1) % 3;
      i.value = v; b.textContent = L[v]; b.className = 'st-btn bd ' + K[v]; recount();
    });
  }

  /* ---------- کلاس آنلاین: انتخاب سرویس ---------- */
  $$('[data-autosubmit]').forEach(function (f) {
    f.addEventListener('change', function (e) {
      var r = e.target; if (r.name !== 'provider') return;
      var lb = $('[data-provider-label]'); if (lb) lb.textContent = r.getAttribute('data-label') || '';
      f.submit();
    });
  });

  /* ---------- آپلود فیلم: کشیدن و رها کردن + فعال‌سازی دکمه ---------- */
  $$('[data-upload]').forEach(function (f) {
    var inp = $('input[type=file]', f), drop = $('.drop', f), txt = $('[data-drop-text]', f), btn = $('button[type=submit]', f);
    var show = function () {
      var has = inp.files && inp.files.length;
      if (txt && has) txt.textContent = inp.files[0].name;
      btn.disabled = !has;
    };
    inp.addEventListener('change', show);
    ['dragover', 'dragenter'].forEach(function (ev) { drop.addEventListener(ev, function (e) { e.preventDefault(); }); });
    drop.addEventListener('drop', function (e) {
      e.preventDefault();
      if (e.dataTransfer && e.dataTransfer.files.length) { inp.files = e.dataTransfer.files; show(); }
    });
  });

  /* ---------- پیام‌ها: اسکرول به آخرین پیام ---------- */
  var ms = $('.msgs'); if (ms) ms.scrollTop = ms.scrollHeight;

  /* جلوگیری از ارسال دوباره؛ قفل تا زمان بازگشت صفحه حفظ می‌شود. */
  $('form').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (f.getAttribute('data-busy')) { e.preventDefault(); return; }
      f.setAttribute('data-busy', '1');
      $('button[type="submit"],input[type="submit"]', f).forEach(function (button) {
        button.disabled = true;
      });
    });
  });
  window.addEventListener('pageshow', function () {
    $('form[data-busy]').forEach(function (f) {
      f.removeAttribute('data-busy');
      $('button[type="submit"],input[type="submit"]', f).forEach(function (button) {
        button.disabled = false;
      });
    });
  });
})();
