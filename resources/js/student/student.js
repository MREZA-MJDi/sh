import '../shared/iranian-locale.js';
/* فقط رفتار (action)؛ هیچ داده‌ای در JS نیست. */
(function () {
  'use strict';
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var FA = '۰۱۲۳۴۵۶۷۸۹';
  var fa = function (n) { return String(n).replace(/\d/g, function (d) { return FA[d]; }); };

  /* Toast: هر عنصری با data-toast="متن" */
  var toastEl = $('#toast'), toastT;
  window.shToast = function (msg) {
    if (!toastEl) return;
    toastEl.textContent = msg; toastEl.classList.add('show');
    clearTimeout(toastT); toastT = setTimeout(function () { toastEl.classList.remove('show'); }, 2400);
  };
  document.addEventListener('click', function (e) {
    var t = e.target.closest('[data-toast]');
    if (t) { e.preventDefault(); window.shToast(t.getAttribute('data-toast')); }
    var c = e.target.closest('[data-confirm]');
    if (c && !window.confirm(c.getAttribute('data-confirm'))) e.preventDefault();
  });

  /* تم تیره/روشن */
  var root = document.documentElement, tg = $('#themeToggle');
  if (tg) tg.addEventListener('click', function () {
    var cur = root.getAttribute('data-theme') ||
      (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    var next = cur === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    try { localStorage.setItem('sh-theme', next); } catch (e) {}
  });

  /* انیمیشن نوار پیشرفت و نمودار */
  function grow(sel, prop) {
    $$(sel).forEach(function (el) {
      var v = el.style[prop]; if (!v) return;
      el.style[prop] = '0';
      requestAnimationFrame(function () { requestAnimationFrame(function () { el.style[prop] = v; }); });
    });
  }
  grow('.prog i', 'width');
  grow('.col div', 'height');

  /* جست‌وجوی زنده در محتوای همین صفحه */
  var s = $('#globalSearch');
  if (s) s.addEventListener('input', function () {
    var q = s.value.trim().toLowerCase();
    $$('.page .trk, .page .tbl tr:not(:first-child), .page .cc, .page .note, .page .medal, .page .portal-action-card, .page .portal-exam-card').forEach(function (el) {
      el.hidden = !!q && el.textContent.toLowerCase().indexOf(q) === -1;
    });
  });

  /* فرم‌ها: جلوگیری از ارسال مجدد تا پایان درخواست و بازگشت صفحه. */
  $$('form').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (e.defaultPrevented || f.target === '_blank') return;
      if (f.dataset.busy) { e.preventDefault(); return; }
      f.dataset.busy = '1';
      $$('button[type="submit"],input[type="submit"]', f).forEach(function (button) {
        // Preserve the clicked submitter so name/value fields remain in the request.
        if (button !== e.submitter) button.disabled = true;
      });
    });
  });
  window.addEventListener('pageshow', function () {
    $$('form[data-busy]').forEach(function (f) {
      delete f.dataset.busy;
      $$('button[type="submit"],input[type="submit"]', f).forEach(function (button) {
        button.disabled = false;
      });
    });
  });

  /* چت: اسکرول به آخرین پیام */
  var ms = $('.msgs'); if (ms) ms.scrollTop = ms.scrollHeight;

  /* آزمون: تایمر معکوس؛ با صفر شدن فرم خودکار با go=finish ارسال می‌شود */
  var ex = $('#exam'), te = $('#examTimer');
  if (ex && te) {
    var deadline = Date.now() + Math.max(0, Number(ex.dataset.seconds) || 0) * 1000;
    var tick = function () {
      var left = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));
      var m = Math.floor(left / 60), sec = left % 60;
      te.textContent = fa(String(m).padStart(2, '0') + ':' + String(sec).padStart(2, '0'));
      if (left <= 0) {
        clearInterval(iv);
        var h = document.createElement('input');
        h.type = 'hidden'; h.name = 'go'; h.value = 'finish'; ex.appendChild(h);
        if (!ex.dataset.busy) { ex.dataset.busy = '1'; ex.submit(); }
        return;
      }
    };
    var iv = setInterval(tick, 1000); tick();
  }
})();
