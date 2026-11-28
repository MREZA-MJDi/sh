@props(['kind', 'cover' => null, 'session' => null, 'classLink' => false])

<aside class="editor-preview wide" data-editor-preview="{{ $kind }}" data-cover="{{ $cover }}"
  data-title="{{ $session?->title ?: $session?->classroom?->name }}"
  data-start="{{ $session?->starts_at?->format('Y-m-d\TH:i') }}"
  data-end="{{ $session?->ends_at?->format('Y-m-d\TH:i') }}"
  data-status="{{ $session?->status }}" data-class-link="{{ (int) $classLink }}"
  aria-label="پیش‌نمایش برای دانش‌آموز">
  <p class="editor-preview-label">پیش‌نمایش برای دانش‌آموز</p>
  <div class="editor-preview-hero">
    @if($kind === 'course')
      <img data-preview="cover" alt="" @if($cover) src="{{ $cover }}" @else hidden @endif>
    @endif
    <span data-preview="meta"></span>
    <h3 data-preview="title"></h3>
  </div>
  <div class="editor-preview-body">
    <p data-preview="description"></p>
    <span data-preview="status"></span>
    <span class="editor-preview-action" data-preview="action"></span>
  </div>
</aside>

@pushOnce('head')
<style>
  .editor-course-layout { display:grid; gap:1.25rem; align-items:start; }
  @media (min-width: 1100px) {
    .editor-course-layout { grid-template-columns:minmax(0, 3fr) minmax(0, 2fr); }
    .editor-course-layout > .editor-preview { position:sticky; top:1rem; }
  }
  .editor-preview { grid-column:1 / -1; min-width:0; overflow:hidden; border:1px solid #cbd5e1; border-radius:1rem; background:#f8fafc; color:#172b47; overflow-wrap:anywhere; }
  .editor-course-layout > .editor-preview { grid-column:auto; }
  .editor-preview-label, .editor-preview-hero, .editor-preview-body { margin:0; padding:.85rem 1rem; }
  .editor-preview-label { font-size:.82rem; font-weight:700; }
  .editor-preview-hero { background:linear-gradient(120deg, #542a96, #126b75); color:#fff; }
  .editor-preview-hero img { width:100%; aspect-ratio:16 / 9; object-fit:cover; border-radius:.6rem; margin-bottom:.65rem; }
  .editor-preview [hidden], .editor-preview span:empty, .editor-preview p:empty { display:none; }
  .editor-preview-hero h3 { color:inherit; margin:.3rem 0 0; font-size:1.15rem; }
  .editor-preview-body p { white-space:pre-wrap; max-height:12rem; overflow:auto; margin:0 0 .65rem; }
  .editor-preview-action { display:block; width:fit-content; margin-top:.65rem; padding:.35rem .75rem; border-radius:.5rem; background:#155e63; color:#fff; }
</style>
@endPushOnce

@pushOnce('scripts')
<script>
(() => {
  const initialize = () => {
    const number = new Intl.NumberFormat('fa-IR');
    const date = new Intl.DateTimeFormat('fa-IR', {
      dateStyle: 'medium', timeStyle: 'short', timeZone: 'UTC',
    });

    document.querySelectorAll('[data-editor-preview]').forEach((preview) => {
      const form = preview.closest('form');
      if (!form) return;

      const field = (name) => form.elements.namedItem(name);
      const value = (name) => field(name)?.value?.trim() ?? '';
      const label = (name) => value(name) ? field(name)?.selectedOptions?.[0]?.textContent.trim() : '';
      const slots = Object.fromEntries(Array.from(preview.querySelectorAll('[data-preview]'), (el) => [el.dataset.preview, el]));
      const set = (slot, text) => { slots[slot].textContent = text ?? ''; };
      const formatDate = (value) => {
        const parsed = new Date(value + 'Z');
        return Number.isNaN(parsed.getTime()) ? '' : date.format(parsed);
      };

      let previousFile;
      let objectUrl;
      const refresh = () => {
        const kind = preview.dataset.editorPreview;
        if (kind === 'course') {
          set('title', value('title') || 'عنوان دوره');
          set('description', value('description'));
          set('meta', [value('subject'), label('academic_grade_id'), label('teacher_id')].filter(Boolean).join(' · '));
          const paid = value('type') === 'paid';
          const price = Number(value('price'));
          set('status', paid ? (price > 0 ? number.format(price) + ' تومان' : 'قیمت را وارد کنید') : 'رایگان');
          set('action', value('status') === 'published'
            ? (paid ? 'خرید دوره' : 'ثبت‌نام رایگان')
            : 'وضعیت انتشار: ' + (label('status') || 'پیش‌نویس'));

          const file = field('cover_image')?.files?.[0];
          if (file !== previousFile) {
            if (objectUrl) URL.revokeObjectURL(objectUrl);
            objectUrl = file?.type.startsWith('image/') ? URL.createObjectURL(file) : undefined;
            previousFile = file;
          }

          const source = objectUrl || (field('remove_cover_image')?.checked ? '' : preview.dataset.cover);
          slots.cover.hidden = !source;
          if (!source) slots.cover.removeAttribute('src');
          else if (slots.cover.getAttribute('src') !== source) slots.cover.src = source;
          return;
        }

        if (kind === 'session') {
          const online = !field('session_type') || value('session_type') === 'online';
          const status = value('status') || preview.dataset.status;
          const start = formatDate(value('starts_at') || preview.dataset.start);
          const end = formatDate(value('ends_at') || preview.dataset.end);
          set('title', value('title') || preview.dataset.title || 'موضوع جلسه');
          set('meta', online ? 'جلسه آنلاین' : 'جلسه حضوری');
          set('description', [start && 'شروع: ' + start, end && 'پایان: ' + end].filter(Boolean).join(' · '));
          set('status', label('status'));
          const link = value('meeting_url');
          const hasLink = /^https?:\/\/[^\s]+$/i.test(link)
            || (!link && value('meeting_provider') === 'skyroom' && preview.dataset.classLink === '1');
          set('action', online && status !== 'cancelled' && status !== 'completed'
            ? (hasLink ? 'ورود به کلاس در زمان برگزاری' : 'لینک ورود هنوز آماده نیست')
            : '');
          return;
        }

        const lesson = kind === 'lesson';
        set('title', value('title') || (lesson ? 'عنوان درس' : 'عنوان فصل'));
        set('meta', [lesson ? 'درس' : 'فصل', value('sort_order') && 'ترتیب ' + number.format(Number(value('sort_order')))].filter(Boolean).join(' · '));
        set('description', value(lesson ? 'body' : 'description'));
        set('status', [label('status'), lesson && value('duration_minutes') && number.format(Number(value('duration_minutes'))) + ' دقیقه'].filter(Boolean).join(' · '));
      };

      form.addEventListener('input', refresh);
      form.addEventListener('change', refresh);
      form.addEventListener('reset', () => requestAnimationFrame(refresh));
      const dialog = form.closest('dialog');
      if (dialog) {
        new MutationObserver(() => requestAnimationFrame(refresh))
          .observe(dialog, { attributes: true, attributeFilter: ['open'] });
      }
      refresh();
    });
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, { once: true });
  else initialize();
})();
</script>
@endPushOnce
