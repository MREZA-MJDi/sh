import '../admin/course-editor.js';
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

  /* One course → classroom → session selector across teacher content forms. */
  $$('[data-learning-scope]').forEach(function (scope) {
    var course = $('[name="course_id"],[data-upload-course]', scope);
    var classroom = $('[name="classroom_id"],[data-upload-classroom]', scope);
    var session = $('[name="class_session_id"],[data-upload-session]', scope);
    if (!classroom || !session) return;

    var filter = function () {
      var courseId = course ? String(course.value || '') : '';
      var classroomId = String(classroom.value || '');

      Array.prototype.forEach.call(classroom.options, function (option) {
        var optionCourse = option.getAttribute('data-course-id');
        var visible = !option.value || !courseId || optionCourse === courseId;
        option.hidden = !visible;
        option.disabled = !visible;
      });
      if (classroom.selectedOptions[0]?.disabled) {
        classroom.value = '';
        classroomId = '';
      }

      Array.prototype.forEach.call(session.options, function (option) {
        var optionClassroom = option.getAttribute('data-classroom-id');
        var visible = !option.value || (classroomId && optionClassroom === classroomId);
        option.hidden = !visible;
        option.disabled = !visible;
      });
      if (session.selectedOptions[0]?.disabled) session.value = '';
    };

    course?.addEventListener('change', filter);
    classroom.addEventListener('change', filter);
    filter();
  });

  /* Theme and palette controls keep their accessible state in sync. */
  var tg = $('#themeToggle');
  var syncThemeButton = function () {
    if (!tg) return;
    var dark = root.dataset.theme === 'dark' || (!root.dataset.theme && window.matchMedia('(prefers-color-scheme: dark)').matches);
    tg.setAttribute('aria-pressed', dark ? 'true' : 'false');
    tg.setAttribute('aria-label', dark ? 'فعال‌سازی تم روشن' : 'فعال‌سازی تم تیره');
    tg.title = dark ? 'تم روشن' : 'تم تیره';
  };
  syncThemeButton();
  if (tg) tg.addEventListener('click', function () {
    var cur = root.dataset.theme || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    var next = cur === 'dark' ? 'light' : 'dark';
    root.dataset.theme = next;
    try { localStorage.setItem('th-theme', next); } catch (e) {}
    syncThemeButton();
  });
  var paletteButtons = $$('[data-pal]');
  var syncPaletteButtons = function () {
    var active = root.dataset.pal || '';
    paletteButtons.forEach(function (button) {
      var selected = (button.getAttribute('data-pal') || '') === active;
      button.setAttribute('aria-pressed', selected ? 'true' : 'false');
    });
  };
  syncPaletteButtons();
  paletteButtons.forEach(function (b) {
    b.addEventListener('click', function () {
      var p = b.getAttribute('data-pal') || '';
      if (p) root.dataset.pal = p; else delete root.dataset.pal;
      try { localStorage.setItem('th-pal', p); } catch (e) {}
      syncPaletteButtons();
    });
  });

  /* Mobile navigation drawer: focus, backdrop, Escape and route changes. */
  var navToggle = $('#teacherNavToggle');
  var navSidebar = $('#teacherSidebar');
  var navBackdrop = $('#teacherNavBackdrop');
  var teacherTopbar = $('[data-teacher-topbar]');
  var syncTeacherTopbarHeight = function () {
    if (teacherTopbar) root.style.setProperty('--teacher-topbar-height', teacherTopbar.offsetHeight + 'px');
  };
  syncTeacherTopbarHeight();
  if (window.ResizeObserver && teacherTopbar) new ResizeObserver(syncTeacherTopbarHeight).observe(teacherTopbar);
  else window.addEventListener('resize', syncTeacherTopbarHeight);
  var closeTeacherNav = function (restoreFocus) {
    if (!navSidebar || !navToggle || !navBackdrop) return;
    var wasOpen = navToggle.getAttribute('aria-expanded') === 'true';
    document.body.classList.remove('teacher-nav-open');
    navToggle.setAttribute('aria-expanded', 'false');
    navToggle.setAttribute('aria-label', 'باز کردن منوی اصلی');
    navBackdrop.hidden = true;
    if (restoreFocus && wasOpen) navToggle.focus();
  };
  var openTeacherNav = function () {
    if (!navSidebar || !navToggle || !navBackdrop) return;
    document.body.classList.add('teacher-nav-open');
    navToggle.setAttribute('aria-expanded', 'true');
    navToggle.setAttribute('aria-label', 'بستن منوی اصلی');
    navBackdrop.hidden = false;
    navSidebar.focus();
  };
  if (navToggle) navToggle.addEventListener('click', function () {
    if (navToggle.getAttribute('aria-expanded') === 'true') closeTeacherNav(true);
    else openTeacherNav();
  });
  if (navBackdrop) navBackdrop.addEventListener('click', function () { closeTeacherNav(true); });
  if (navSidebar) navSidebar.addEventListener('click', function (event) {
    if (event.target.closest('a')) closeTeacherNav(false);
  });
  window.addEventListener('resize', function () {
    if (window.innerWidth > 760) closeTeacherNav(false);
  });

  /* Only one topbar popover may stay open at a time. */
  var topbarMenus = $$('.teacher-menu');
  topbarMenus.forEach(function (menu) {
    menu.addEventListener('toggle', function () {
      if (!menu.open) return;
      topbarMenus.forEach(function (other) { if (other !== menu) other.open = false; });
    });
  });
  document.addEventListener('click', function (event) {
    topbarMenus.forEach(function (menu) {
      if (!menu.contains(event.target)) menu.open = false;
    });
  });

  /* Live filtering is explicitly scoped to the current server-rendered page. */
  var sr = $('#globalSearch');
  var searchClear = $('#teacherSearchClear');
  var searchStatus = $('#teacherSearchResults');
  var searchLinks = $('#teacherSearchLinks');
  var searchableSelector = '.row, .teacher-list-card, .teacher-review-row, .teacher-live-session-card, .teacher-inbox-item, .teacher-week-event';
  var searchableItems = $$(searchableSelector).filter(function (el) { return !el.parentElement.closest(searchableSelector); });
  var updatePageSearch = function () {
    if (!sr) return;
    var q = sr.value.trim().toLocaleLowerCase();
    var visible = 0;
    searchableItems.forEach(function (el) {
      var match = !q || el.textContent.toLocaleLowerCase().indexOf(q) !== -1;
      el.hidden = !match;
      if (match) visible++;
    });
    if (searchClear) searchClear.hidden = !q;
    if (searchStatus) searchStatus.textContent = q ? (visible ? visible + ' نتیجه در محتوای صفحه پیدا شد.' : 'نتیجه‌ای در محتوای صفحه پیدا نشد؛ بخش‌های پنل نیز بررسی شدند.') : '';
    if (searchLinks) {
      searchLinks.hidden = !q;
      $$('[data-search-route]', searchLinks).forEach(function (link) { link.hidden = !!q && link.textContent.toLocaleLowerCase().indexOf(q) === -1; });
    }
  };
  if (sr) sr.addEventListener('input', updatePageSearch);
  if (searchClear && sr) searchClear.addEventListener('click', function () {
    sr.value = '';
    updatePageSearch();
    sr.focus();
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
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      closeModals();
      topbarMenus.forEach(function (menu) { menu.open = false; });
      closeTeacherNav(true);
      return;
    }
    if (e.key === 'Tab' && navToggle && navSidebar && navToggle.getAttribute('aria-expanded') === 'true') {
      var focusable = $$('a[href],button:not([disabled]),input:not([disabled]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])', navSidebar)
        .filter(function (el) { return el.offsetWidth || el.offsetHeight || el.getClientRects().length; });
      if (!focusable.length) { e.preventDefault(); navSidebar.focus(); return; }
      var first = focusable[0], last = focusable[focusable.length - 1];
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    }
  });

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
  $$('form').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (f.getAttribute('data-busy')) { e.preventDefault(); return; }
      f.setAttribute('data-busy', '1');
      $('button[type="submit"],input[type="submit"]', f).forEach(function (button) {
        // A disabled submitter is omitted from the browser request, including its name/value.
        if (button !== e.submitter) button.disabled = true;
      });
    });
  });
  window.addEventListener('pageshow', function () {
    $$('form[data-busy]').forEach(function (f) {
      f.removeAttribute('data-busy');
      $$('button[type="submit"],input[type="submit"]', f).forEach(function (button) {
        button.disabled = false;
      });
    });
  });

  /* Persian calendar labels for the seven-day teacher dashboard. */
  (function () {
    var calendar = $('[data-teacher-week-calendar]');
    if (!calendar || !window.Intl || !Intl.DateTimeFormat) return;
    try {
      var weekdayFormatter = new Intl.DateTimeFormat('fa-IR', {
        weekday: 'long',
        timeZone: 'Asia/Tehran'
      });
      var dayFormatter = new Intl.DateTimeFormat('fa-IR-u-ca-persian', {
        day: 'numeric',
        timeZone: 'Asia/Tehran'
      });
      $$('[data-gregorian-date]', calendar).forEach(function (day) {
        var value = day.getAttribute('data-gregorian-date');
        if (!/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/.test(value || '')) return;
        var date = new Date(value + 'T12:00:00+03:30');
        if (Number.isNaN(date.getTime())) return;
        var weekday = day.querySelector('[data-weekday]');
        var dayNumber = day.querySelector('[data-day-number]');
        if (weekday) weekday.textContent = weekdayFormatter.format(date);
        if (dayNumber) dayNumber.textContent = dayFormatter.format(date);
      });
    } catch (e) {
      // Leave neutral placeholders visible if the browser lacks Persian ICU data.
    }
  })();

})();
