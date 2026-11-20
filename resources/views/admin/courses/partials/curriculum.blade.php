<x-admin.panel title="فصل‌ها و درس‌های دوره" desc="ترتیب این بخش‌ها همان ترتیبی است که دانش‌آموز در صفحه دوره می‌بیند." flush data-tone="edu">
  <div class="adm-curriculum">
    @forelse($course->sections as $section)
      <section class="adm-chapter">
        <div class="adm-chapter-head">
          <span class="adm-chapter-no adm-num">{{ $loop->iteration }}</span>
          <div class="adm-grow">
            <h3>{{ $section->title }}</h3>
            <small class="adm-muted">{{ number_format($section->lessons->count()) }} درس · {{ $section->status === 'published' ? 'قابل نمایش در سایت' : ($section->status === 'draft' ? 'پیش‌نویس' : 'بایگانی') }}</small>
            @if($section->description)<p class="adm-muted">{{ $section->description }}</p>@endif
          </div>
          <div class="adm-actions">
            <x-admin.btn size="sm" icon="plus" data-modal-open="lesson-modal" :data-action="route('admin.courses.lessons.store', $section)">افزودن درس</x-admin.btn>
            <x-admin.btn size="sm" variant="soft" icon="edit" data-modal-open="section-modal" data-title="ویرایش فصل" :data-fill="json_encode(['title' => $section->title, 'description' => $section->description, 'sort_order' => $section->sort_order, 'status' => $section->status], JSON_UNESCAPED_UNICODE)" :data-action="route('admin.courses.sections.update', $section)" data-method="PATCH">ویرایش</x-admin.btn>
            <x-admin.btn size="sm" variant="bad" icon="trash" data-modal-open="section-delete" :data-name="$section->title" :data-action="route('admin.courses.sections.destroy', $section)" aria-label="حذف فصل"/>
          </div>
        </div>
        <div class="adm-chapter-body">
          @forelse($section->lessons as $lesson)
            <article class="adm-lesson">
              <span class="adm-lesson-ico"><x-admin.icon name="file"/></span>
              <div class="adm-grow">
                <b>{{ $lesson->title }}</b>
                <small class="adm-muted">{{ $lesson->duration_minutes ? number_format($lesson->duration_minutes).' دقیقه' : 'مدت‌زمان ثبت نشده' }} · {{ $lesson->status === 'published' ? 'قابل نمایش در سایت' : ($lesson->status === 'draft' ? 'پیش‌نویس' : 'بایگانی') }}</small>
                @if(filled($lesson->body))<p class="adm-muted">{{ \Illuminate\Support\Str::limit(strip_tags($lesson->body), 150) }}</p>@endif
              </div>
              <div class="adm-actions">
                <x-admin.btn size="sm" variant="soft" icon="edit" data-modal-open="lesson-modal" data-title="ویرایش درس" :data-fill="json_encode(['title' => $lesson->title, 'body' => $lesson->body, 'duration_minutes' => $lesson->duration_minutes, 'sort_order' => $lesson->sort_order, 'status' => $lesson->status], JSON_UNESCAPED_UNICODE)" :data-action="route('admin.courses.lessons.update', $lesson)" data-method="PATCH">ویرایش</x-admin.btn>
                <x-admin.btn size="sm" variant="bad" icon="trash" data-modal-open="lesson-delete" :data-name="$lesson->title" :data-action="route('admin.courses.lessons.destroy', $lesson)" aria-label="حذف درس"/>
              </div>
            </article>
          @empty
            <div class="adm-curriculum-empty">هنوز درسی در این فصل نیست. با «افزودن درس» شروع کن.</div>
          @endforelse
        </div>
      </section>
    @empty
      <div class="adm-queue-empty"><x-admin.icon name="book"/><b>هنوز فصلی ساخته نشده</b><span>اولین فصل را اضافه کن تا درس‌های دوره را مرتب کنی.</span></div>
    @endforelse
  </div>
  <x-slot:footer>
    <x-admin.btn icon="plus" data-modal-open="section-modal">افزودن فصل جدید</x-admin.btn>
  </x-slot:footer>
</x-admin.panel>

@push('modals')
<x-admin.modal :demo="false" id="section-modal" title="فصل جدید" size="md" :action="route('admin.courses.sections.store', $course)">
  <x-admin.input label="نام فصل" name="title" wide required placeholder="مثلاً فصل اول: مبانی و مفاهیم"/>
  <x-admin.textarea label="توضیح کوتاه فصل" name="description" wide/>
  <x-admin.input label="ترتیب نمایش" name="sort_order" type="number" min="0"/>
  <x-admin.select label="وضعیت نمایش" name="status" :options="['draft' => 'پیش‌نویس', 'published' => 'نمایش در سایت', 'archived' => 'بایگانی']" :selected="'draft'" required/>
</x-admin.modal>

<x-admin.modal :demo="false" id="lesson-modal" title="درس جدید" size="lg" :action="url('admin/course-sections/0/lessons')">
  <x-admin.input label="عنوان درس" name="title" wide required placeholder="مثلاً جلسه اول: آشنایی با موضوع"/>
  <x-admin.textarea label="متن درس یا توضیح برای دانش‌آموز" name="body" wide rows="6"/>
  <x-admin.input label="مدت‌زمان (دقیقه)" name="duration_minutes" type="number" min="0" max="600"/>
  <x-admin.input label="ترتیب نمایش" name="sort_order" type="number" min="0"/>
  <x-admin.select label="وضعیت نمایش" name="status" :options="['draft' => 'پیش‌نویس', 'published' => 'نمایش در سایت', 'archived' => 'بایگانی']" :selected="'draft'" required/>
</x-admin.modal>

<x-admin.confirm :demo="false" id="section-delete" title="حذف فصل" text="اگر دانش‌آموزی پیشرفت ثبت‌شده داشته باشد، فصل حذف نمی‌شود؛ آن را بایگانی کن."/>
<x-admin.confirm :demo="false" id="lesson-delete" title="حذف درس" text="اگر برای این درس سابقه پیشرفت ثبت شده باشد، درس حذف نمی‌شود؛ آن را بایگانی کن."/>
@endpush
