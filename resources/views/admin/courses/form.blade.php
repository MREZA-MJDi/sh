@extends('admin.layouts.app')
@section('title', $isEditing ? 'ویرایش صفحه دوره' : 'ساخت دوره جدید')

@push('head')
<style>#course-enrollment .adm-field[hidden] { display: none }</style>
@endpush

@section('content')
@php
  $seasons = \App\Models\Course::SEASONS;
  $statuses = ['draft' => 'پیش‌نویس', 'published' => 'انتشار در سایت', 'archived' => 'بایگانی'];
  $featureLabels = [
    'recorded_video' => 'ویدیوهای آموزشی',
    'live_class' => 'کلاس آنلاین',
    'resources' => 'جزوه و فایل‌های آموزشی',
    'assignments' => 'تکلیف و تمرین',
    'exams' => 'آزمون و کوییز',
    'projects' => 'پروژه و ارزیابی مرحله‌ای',
  ];
  $formAction = $isEditing ? route('admin.courses.update', $course) : route('admin.courses.store');
  $classTeacherOptions = $teachers->pluck('name', 'id')->all();
  $classDayOptions = ['sat' => 'شنبه', 'sun' => 'یکشنبه', 'mon' => 'دوشنبه', 'tue' => 'سه‌شنبه', 'wed' => 'چهارشنبه', 'thu' => 'پنجشنبه', 'fri' => 'جمعه'];
  $classStatusOptions = ['active' => 'فعال', 'paused' => 'متوقف', 'archived' => 'بایگانی'];
@endphp

<x-admin.page-head tone="edu" icon="book" :title="$isEditing ? 'ویرایش صفحه دوره' : 'ساخت دوره جدید'"
  desc="اول اطلاعات اصلی را ذخیره کن؛ بعد ویدیوها، فصل‌ها و کلاس‌ها را از همین صفحه تکمیل کن.">
  @if($isEditing)
    <x-admin.btn variant="line" icon="link" :href="route('public.courses.show', ['slug' => $course->slug, 'preview' => 1])" target="_blank">پیش‌نمایش صفحه عمومی</x-admin.btn>
  @endif
  <x-admin.btn variant="line" :href="route('admin.courses.index')">بازگشت به دوره‌ها</x-admin.btn>
</x-admin.page-head>

@if($isEditing)
  <nav class="adm-actions" aria-label="بخش‌های دوره">
    <a class="adm-btn adm-btn-line" href="#course-basics">مشخصات دوره</a>
    <a class="adm-btn adm-btn-line" href="#course-media">ویدیو و تریلر</a>
    <a class="adm-btn adm-btn-line" href="#course-lessons">درس‌ها</a>
    <a class="adm-btn adm-btn-line" href="#course-classes">کلاس‌ها</a>
    @if($course->featureEnabled('resources') && auth()->user()->hasPermissionTo('resources.manage'))
      <a class="adm-btn adm-btn-line" href="#course-files">جزوه و فایل</a>
    @endif
  </nav>
@else
  <p class="adm-muted">نام دوره را وارد کن و پیش‌نویس را بساز. ویدیو، درس و کلاس را بعد از ذخیره اضافه کن.</p>
@endif

<div class="adm-stack">
  <div class="adm-stack">
    <form method="POST" action="{{ $formAction }}" class="adm-stack" enctype="multipart/form-data">
      @csrf
      @if($isEditing) @method('PATCH') @endif
      <input type="hidden" name="delivery_type" value="{{ $course->delivery_type ?: 'onsite' }}">
      <input type="hidden" name="spotplayer_course_id" value="{{ old('spotplayer_course_id', $course->spotplayer_course_id) }}">

      <div class="editor-course-layout">
        <div class="adm-stack">
      <x-admin.panel id="course-basics" title="مشخصات دوره" desc="این اطلاعات، سربرگ صفحه عمومی دوره را می‌سازند و به دانش‌آموز نمایش داده می‌شوند." data-tone="edu">
        <div class="adm-form">
          <x-admin.input label="عنوان اصلی دوره" name="title" :value="old('title', $course->title)" placeholder="مثلاً شیمی دوازدهم؛ آموزش مفهومی و مسئله‌محور" wide required/>
          <details class="adm-history-panel wide" @if($errors->hasAny(['subject','academic_grade_id','season','academic_year','teacher_id'])) open @endif>
            <summary>دسته‌بندی و مدرس <x-admin.icon name="chev"/></summary>
            <div class="adm-form">
          <x-admin.input label="درس تخصصی" name="subject" :value="old('subject', $course->subject)" placeholder="مثلاً شیمی، زیست‌شناسی، ریاضی"/>
          <x-admin.select label="پایه تحصیلی" name="academic_grade_id" :options="$grades->pluck('name', 'id')->all()" :selected="old('academic_grade_id', $course->academic_grade_id ?: \App\Models\AcademicGrade::resolveIdFromLegacyName($course->grade))" placeholder="انتخاب پایه"/>
          <x-admin.select label="فصل دوره" name="season" :options="$seasons" :selected="old('season', $course->season)" placeholder="انتخاب فصل"/>
          <x-admin.input label="سال شروع سال تحصیلی (شمسی)" name="academic_year" type="number" min="1300" max="1500" :value="old('academic_year', $course->academic_year)" placeholder="مثلاً ۱۴۰۵"/>
          <x-admin.select label="مدرس دوره" name="teacher_id" :options="$teachers->pluck('name', 'id')->all()" :selected="old('teacher_id', $course->teacher_id)" placeholder="انتخاب مدرس"/>
            </div>
          </details>
          <x-admin.textarea label="توضیح کوتاه زیر عنوان" name="description" :value="old('description', $course->description)" wide rows="4" placeholder="این متن در سربرگ و معرفی دوره نمایش داده می‌شود."/>
          <div class="adm-field wide">
            <span class="adm-label">تصویر روی کارت و صفحه دوره</span>
            <label class="adm-upload adm-upload-wide">
              <input id="course-cover-image" type="file" name="cover_image" aria-describedby="course-cover-guidance @error('cover_image') course-cover-error @enderror" @error('cover_image') aria-invalid="true" @enderror accept="image/jpeg,image/png,image/webp" data-course-cover-input data-preview-ratio="16/9" data-preview-fit="cover">
              <span class="adm-upload-icon"><x-admin.icon name="file"/></span>
              <b>تصویر دوره را اینجا رها کن یا انتخاب کن</b>
              <small id="course-cover-guidance" data-upload-name>JPG، PNG یا WebP · حداکثر ۵ مگابایت</small>
            </label>
            @error('cover_image') <span class="adm-err" id="course-cover-error">{{ $message }}</span> @enderror
            @if($isEditing && $course->coverImageUrl)
              <div class="adm-current-cover">
                <img src="{{ $course->coverImageUrl }}" alt="تصویر فعلی {{ $course->title }}" loading="lazy">
                <label class="adm-check"><input type="checkbox" name="remove_cover_image" value="1"> حذف تصویر فعلی</label>
              </div>
            @endif
          </div>
        </div>
      </x-admin.panel>

      <x-admin.panel id="course-enrollment" title="قیمت و انتشار" desc="قیمت و وضعیت انتشار دوره را مشخص کن." data-tone="shop">
        <div class="adm-form">
          <x-admin.select label="نوع دوره" name="type" :options="['free' => 'رایگان', 'paid' => 'پرداختی']" :selected="old('type', $course->type ?: 'free')" required/>
          <x-admin.input label="قیمت به تومان" name="price" :value="old('price', $course->price)" inputmode="numeric" type="number" min="0" max="1000000000" placeholder="برای دوره رایگان صفر"/>
          <x-admin.select label="وضعیت نمایش در سایت" name="status" :options="$statuses" :selected="old('status', $course->status ?: 'draft')" required/>
          <x-admin.switch label="نمایش در بخش «دوره‌های پیشنهادی» صفحه اصلی" name="is_recommended" :checked="(bool) old('is_recommended', $course->is_recommended)" hint="اگر فعال باشد، این دوره در بخش دوره‌های پیشنهادی آکادمی شیخان در لندینگ نمایش داده می‌شود (حداکثر ۳ دوره)." wide/>
        </div>
      </x-admin.panel>

      <details class="adm-history-panel" id="course-feature-settings" @if($errors->has('learning_features.*')) open @endif>
        <summary>امکانات آموزشی دوره <x-admin.icon name="chev"/></summary>
      <x-admin.panel id="course-features" desc="امکاناتی را انتخاب کن که در این دوره ارائه می‌شوند." data-tone="edu">
        <div class="adm-form">
          @foreach($featureLabels as $key => $label)
            <x-admin.switch :label="$label" :name="'learning_features['.$key.']'" :checked="(bool) old('learning_features.'.$key, $course->featureEnabled($key))" wide/>
          @endforeach
        </div>
      </x-admin.panel>

      </details>

        </div>
        <x-learning.editor-preview kind="course" :cover="$course->coverImageUrl"/>
      </div>

      <div class="adm-actions">
        <button type="submit" class="adm-btn adm-btn-primary">{{ $isEditing ? 'ذخیره تغییرات صفحه دوره' : 'ساخت دوره' }}</button>
        @if($isEditing)
          <a class="adm-btn adm-btn-line" href="{{ route('public.courses.show', ['slug' => $course->slug, 'preview' => 1]) }}" target="_blank" rel="noopener">دیدن نتیجه در صفحه عمومی</a>
        @endif
      </div>
    </form>

    @if($isEditing)
      <details class="adm-history-panel" id="course-media">
        <summary>ویدیو و تریلر <x-admin.icon name="chev"/></summary>
      <x-admin.panel id="course-teaser" title="تریلر دوره" desc="کاربران پس از ورود، این ویدیو را بالای صفحه دوره می‌بینند." data-tone="shop">
        @if($course->teaser_status === 'ready')
          <div class="learning-video-frame" style="position:relative">
            <video controls controlsList="nodownload nofullscreen noremoteplayback" disablepictureinpicture playsinline preload="none" style="display:block;width:100%;max-height:360px;border-radius:14px;background:#0f172a" @if($course->teaser_poster_url) poster="{{ $course->teaser_poster_url }}" @endif></video>
            <x-learning.video-watermark :source="route('admin.courses.teaser', $course)"/>
          </div>
        @elseif($course->teaser_status === 'processing')
          <x-admin.badge tone="warn">در حال پردازش تریلر</x-admin.badge>
          <p class="adm-muted">پس از پایان پردازش، فهرست را به‌روزرسانی کن تا پیش‌نمایش را ببینی.</p>
        @elseif($course->teaser_status === 'failed')
          <x-admin.badge tone="bad">پردازش ناموفق</x-admin.badge>
          <p class="adm-muted">{{ $course->teaser_error ?: 'پردازش تریلر کامل نشد؛ فایل را دوباره بارگذاری کنید.' }}</p>
        @endif
        <div data-course-upload data-target="teaser" data-course-id="{{ $course->id }}"
          data-start-url="{{ route('admin.uploads.start') }}" data-chunk-url="{{ route('admin.uploads.chunk') }}"
          data-complete-url="{{ route('admin.uploads.complete') }}" data-cancel-url="{{ route('admin.uploads.cancel') }}" data-resume-url="{{ route('admin.uploads.resume') }}" data-status-url="{{ route('admin.uploads.status') }}"
          data-max-mb="{{ config('learning.max_teaser_mb') }}" data-chunk-mb="{{ config('learning.chunk_mb') }}">
          <label class="adm-upload adm-upload-wide">
            <input type="file" accept="video/mp4,video/quicktime,video/webm,video/x-matroska,video/x-m4v" data-upload-file>
            <span class="adm-upload-icon"><x-admin.icon name="plus"/></span>
            <b>ویدیوی معرفی را اینجا رها کن یا انتخاب کن</b>
            <small data-upload-name>MP4، MOV یا WebM · حداکثر {{ config('learning.max_teaser_mb') }} مگابایت</small>
          </label>
          <div class="adm-actions adm-upload-actions">
            <button type="button" class="adm-btn adm-btn-primary" data-upload-start>بارگذاری تریلر</button>
            @if($course->teaser_status !== 'none')
              <form method="POST" action="{{ route('admin.courses.teaser.destroy', $course) }}" onsubmit="return confirm('تریلر فعلی حذف شود؟')">
                @csrf @method('DELETE')
                <button type="submit" class="adm-btn adm-btn-line">حذف تریلر</button>
              </form>
            @endif
          </div>
          <div class="adm-progress" data-upload-progress hidden><div class="adm-progress-bar" data-upload-bar></div></div>
          <p class="adm-muted" data-upload-status role="status" aria-live="polite">هنوز فایلی انتخاب نشده است.</p>
        </div>
      </x-admin.panel>

      @if($course->featureEnabled('recorded_video'))
        <x-admin.panel id="course-videos" title="ویدیوهای آموزشی" desc="ویدیو را انتخاب کن. اگر برای یک کلاس یا جلسه خاص است، آن را هم مشخص کن." data-tone="edu">
          <div class="adm-actions">
            @can('integrations.manage')
              <x-admin.btn variant="line" :href="route('admin.spotplayer.index', ['course_id' => $course->id])">ثبت ویدیو از SpotPlayer</x-admin.btn>
            @endcan
          </div>
          <div data-course-upload data-target="spotplayer_video" data-course-id="{{ $course->id }}"
            data-start-url="{{ route('admin.uploads.start') }}" data-chunk-url="{{ route('admin.uploads.chunk') }}"
            data-complete-url="{{ route('admin.uploads.complete') }}" data-cancel-url="{{ route('admin.uploads.cancel') }}" data-resume-url="{{ route('admin.uploads.resume') }}" data-status-url="{{ route('admin.uploads.status') }}" data-max-mb="{{ config('learning.max_lesson_mb') }}" data-chunk-mb="{{ config('learning.chunk_mb') }}">
            <div class="adm-form">
              <x-admin.input label="عنوان جلسه / ویدیو" name="video_title" placeholder="مثلاً جلسه اول: ساختار اتم" data-upload-title/>
              <x-admin.select label="گروه آموزشی (اختیاری)" name="classroom_id" :options="$classrooms->pluck('name', 'id')->all()" placeholder="کلاس را انتخاب کنید" data-upload-classroom/>
              <label class="adm-field">
                <span class="adm-label">جلسه مشخص</span>
                <select class="adm-select" name="class_session_id" data-upload-session>
                  <option value="">بدون اتصال به جلسه</option>
                  @foreach($classrooms as $classroom)
                    @foreach($classroom->sessions as $session)
                      <option value="{{ $session->id }}" data-classroom-id="{{ $classroom->id }}">{{ $session->title ?: 'جلسه' }} · {{ $classroom->name }} · {{ $session->starts_at?->format('Y/m/d H:i') }}</option>
                    @endforeach
                  @endforeach
                </select>
              </label>
              <label class="adm-upload adm-upload-wide">
                <input type="file" accept="video/mp4,video/quicktime,video/webm,video/x-matroska,video/x-m4v" data-upload-file>
                <span class="adm-upload-icon"><x-admin.icon name="video"/></span>
                <b>ویدیوی درس را اینجا رها کن یا انتخاب کن</b>
                <small data-upload-name>MP4، MOV یا WebM · حداکثر {{ config('learning.max_lesson_mb') }} مگابایت</small>
              </label>
            </div>
            <div class="adm-actions" style="margin-top:12px">
              <button type="button" class="adm-btn adm-btn-primary" data-upload-start>بارگذاری ویدیو</button>
            </div>
            <div class="adm-progress" data-upload-progress hidden><div class="adm-progress-bar" data-upload-bar></div></div>
            <p class="adm-muted" data-upload-status role="status" aria-live="polite">بعد از انتخاب، پیش‌نمایش و نام فایل را می‌بینی. اگر اینترنت قطع شود، لازم نیست از اول شروع کنی.</p>
          </div>

          <div class="adm-divider"></div>
          <h3>ویدیوهای ثبت‌شده</h3>
          <div class="adm-side-list">
            @forelse($videos as $video)
              <div>
                <span class="adm-stat-ico" data-tone="edu"><x-admin.icon name="video"/></span>
                <div class="adm-grow"><b>{{ $video->title }}</b><small class="adm-muted">{{ $video->classroom?->name ?? 'در سطح کل دوره' }}@if($video->classSession) · {{ $video->classSession->title }}@endif · {{ $video->file_original_name ?: $video->external_id ?: 'فایل ثبت‌شده' }}</small>@if($video->processing_error)<small class="adm-muted">آماده‌سازی این ویدیو کامل نشد؛ می‌توانی دوباره تلاش کنی.</small>@endif</div>
                @php
                  $videoState = match ($video->status) {
                    'active' => ['ok', 'آماده پخش'],
                    'processing' => ['warn', 'در حال پردازش'],
                    'failed' => ['bad', 'پردازش ناموفق'],
                    'waiting_contract' => ['warn', 'در انتظار قرارداد SpotPlayer'],
                    default => ['mute', 'در انتظار بررسی'],
                  };
                @endphp
                <x-admin.badge :tone="$videoState[0]">{{ $videoState[1] }}</x-admin.badge>
              </div>
            @empty
              <x-admin.empty text="هنوز ویدیویی برای این دوره بارگذاری نشده است."/>
            @endforelse
          </div>
          <p class="adm-muted">فایل آموزشی پس از پردازش در صفحهٔ دوره قابل پخش می‌شود. اگر فایل را در SpotPlayer بارگذاری کرده‌ای، فقط شناسهٔ آن را ثبت کن.</p>
          @if($videos->hasPages())<div class="adm-pagination">{{ $videos->links() }}</div>@endif
        </x-admin.panel>
      @else
        <p class="adm-muted" style="padding:16px">برای افزودن ویدیوهای درس، <a href="#course-feature-settings">ویدیوهای آموزشی را در امکانات دوره فعال کن</a> و تغییرات را ذخیره کن.</p>
      @endif

      </details>

      <details class="adm-history-panel" id="course-lessons">
        <summary>درس‌ها و سرفصل‌ها <x-admin.icon name="chev"/></summary>
        @include('admin.courses.partials.curriculum')
      </details>

      <details class="adm-history-panel" id="course-classes">
        <summary>کلاس‌ها و جلسه‌ها <x-admin.icon name="chev"/></summary>
      <x-admin.panel title="۷. گروه‌های آموزشی این درس" desc="کلاس یعنی گروهی از دانش‌آموزان که با یک مدرس و برنامه مشخص درس می‌خوانند." data-tone="edu">
        <div class="adm-grid adm-cols-2">
          @forelse($classrooms as $classroom)
            @php
              $next = $classroom->sessions->first();
              $scheduleDays = data_get($classroom->schedule, 'days', []);
              $scheduleLabel = collect($scheduleDays)->map(fn ($day) => \App\Support\PersianDate::weekdayName($day))->join('، ');
              $classFill = [
                'name' => $classroom->name,
                'course_id' => (string) $course->id,
                'teacher_id' => (string) $classroom->teacher_id,
                'meeting_url' => $classroom->meeting_url,
                'status' => $classroom->status,
                'schedule_days' => $scheduleDays,
                'schedule_time' => data_get($classroom->schedule, 'time'),
                'duration_minutes' => data_get($classroom->schedule, 'duration_minutes', 90),
              ];
            @endphp
            <article class="adm-card">
              <div class="adm-card-body">
                <div class="adm-card-title"><div><h3>{{ $classroom->name }}</h3><p>{{ $classroom->teacher?->name ?? 'مدرس مشخص نشده' }}</p></div><x-admin.badge tone="ok">فعال</x-admin.badge></div>
                <div class="adm-meta">
                  <span><x-admin.icon name="users"/>{{ number_format($classroom->students_count) }} دانش‌آموز</span>
                  <span><x-admin.icon name="cal"/>{{ number_format($classroom->sessions_count) }} جلسه</span>
                </div>
                <div class="adm-kv">
                  <span>برنامه هفتگی: <b>{{ $scheduleLabel ?: 'شناور' }}</b></span>
                  <span>ساعت معمول: <b>{{ data_get($classroom->schedule, 'time') ?: 'تعیین نشده' }}</b></span>
                </div>
                @if($next)
                  <div class="adm-work-item" data-tone="track"><span><x-admin.icon name="clock"/></span><div><b>جلسه بعدی</b><small>{{ $next->title }} · {{ \App\Support\PersianDate::format($next->starts_at) }}</small></div></div>
                @else
                  <p class="adm-muted">هنوز جلسه آینده‌ای برنامه‌ریزی نشده است.</p>
                @endif
                <div class="adm-actions">
                  <x-admin.btn size="sm" variant="soft" icon="edit" data-modal-open="course-class-edit-{{ $classroom->id }}" data-title="ویرایش کلاس" :data-fill="json_encode($classFill, JSON_UNESCAPED_UNICODE)" :data-action="route('admin.classes.update', $classroom)" data-method="PATCH">ویرایش کلاس</x-admin.btn>
                  <x-admin.btn size="sm" icon="plus" data-modal-open="quick-session-{{ $classroom->id }}">جلسه جدید</x-admin.btn>
                  <x-admin.btn size="sm" variant="soft" icon="cal" :href="route('admin.classes.sessions.index', $classroom)">تقویم کلاس</x-admin.btn>
                  <x-admin.btn size="sm" variant="line" icon="check" :href="route('admin.attendance.index', ['class_id' => $classroom->id])">حضور و غیاب</x-admin.btn>
                </div>
              </div>
            </article>
          @empty
            <div class="adm-queue-empty"><x-admin.icon name="users"/><b>هنوز کلاسی برای این دوره نساخته‌ای</b><span>یک کلاس بساز تا دانش‌آموزان، مدرس و جلسه‌ها را کنار هم مدیریت کنی.</span></div>
          @endforelse
        </div>
        <x-slot:footer><x-admin.btn icon="plus" data-modal-open="course-class-modal">ساخت کلاس برای این دوره</x-admin.btn></x-slot:footer>
      </x-admin.panel>

      </details>

      @if($course->featureEnabled('resources') && auth()->user()->hasPermissionTo('resources.manage'))
        <details class="adm-history-panel" id="course-files">
          <summary>جزوه و فایل‌های آموزشی <x-admin.icon name="chev"/></summary>
        <x-admin.panel id="course-resources" title="۸. جزوه و فایل‌های آموزشی" desc="فایل‌هایی که اینجا اضافه می‌کنی در بخش منابع دوره برای دانش‌آموزان مجاز نمایش داده می‌شوند." data-tone="shop">
          <div class="adm-grid adm-cols-2">
            @forelse($resources as $resource)
              <article class="adm-work-item"><span><x-admin.icon name="file"/></span><div class="adm-grow"><b>{{ $resource->title }}</b><small>{{ $resource->kind === 'pdf' ? 'فایل PDF' : ($resource->kind === 'book' ? 'کتاب یا جزوه' : 'فایل آموزشی') }} · {{ $resource->classroom?->name ?? 'برای همه کلاس‌های دوره' }}</small></div><x-admin.badge :tone="$resource->status === 'published' ? 'ok' : 'warn'">{{ $resource->status === 'published' ? 'نمایش در سایت' : 'پیش‌نویس' }}</x-admin.badge></article>
            @empty
              <div class="adm-curriculum-empty">هنوز فایل آموزشی اضافه نشده است.</div>
            @endforelse
          </div>
          <x-slot:footer><x-admin.btn icon="plus" data-modal-open="course-resource-modal">افزودن جزوه یا فایل</x-admin.btn></x-slot:footer>
        </x-admin.panel>
        </details>
      @endif
    @endif
  </div>
</div>
@endsection

@push('scripts')
<script>
  (() => {
    const courseType = document.querySelector('#course-enrollment [name="type"]');
    const price = document.querySelector('#course-enrollment [name="price"]');
    if (courseType && price) {
      const syncPrice = () => {
        const paid = courseType.value === 'paid';
        price.closest('.adm-field').hidden = !paid;
        price.required = paid;
      };
      courseType.addEventListener('change', syncPrice);
      syncPrice();
    }

    const openSection = () => {
      const hash = window.location.hash.slice(1);
      if (!hash.startsWith('course-')) return;
      const section = document.getElementById(hash);
      if (section instanceof HTMLDetailsElement) section.open = true;
    };
    openSection();
    window.addEventListener('hashchange', openSection);
    document.querySelectorAll('a[href^="#course-"]').forEach((link) => {
      link.addEventListener('click', () => {
        const section = document.getElementById(link.hash.slice(1));
        if (section instanceof HTMLDetailsElement) section.open = true;
      });
    });
  })();
</script>
@endpush

@push('modals')
@if($isEditing)
  <x-admin.modal :demo="false" id="course-class-modal" title="ساخت کلاس جدید" size="lg" :action="route('admin.classes.store')">
    @include('admin.classes.partials.class-fields', [
      'fixedCourseId' => $course->id,
      'teacherOptions' => $classTeacherOptions,
      'days' => $classDayOptions,
      'statusOptions' => $classStatusOptions,
      'studentOptions' => $students->pluck('name', 'id')->all(),
    ])
  </x-admin.modal>

  @if($course->featureEnabled('resources') && auth()->user()->hasPermissionTo('resources.manage'))
    <x-admin.modal :demo="false" id="course-resource-modal" title="افزودن جزوه یا فایل" size="lg" :action="route('admin.resources.store')">
      <input type="hidden" name="course_id" value="{{ $course->id }}">
      <x-admin.input label="نام فایل" name="title" wide required placeholder="مثلاً جزوه فصل اول"/>
      <x-admin.textarea label="توضیح کوتاه برای دانش‌آموز" name="description" wide/>
      <x-admin.select label="نوع فایل" name="kind" :options="['pdf' => 'PDF', 'book' => 'کتاب یا جزوه', 'other' => 'سایر فایل‌ها']" :selected="'pdf'" required/>
      <label class="adm-upload adm-upload-wide">
        <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" required>
        <span class="adm-upload-icon"><x-admin.icon name="plus"/></span>
        <b>فایل را اینجا رها کن یا انتخاب کن</b>
        <small data-upload-name>PDF، Word، تصویر یا ZIP · حداکثر ۳۰ مگابایت</small>
      </label>
      <x-admin.select label="مخصوص کدام کلاس؟" name="classroom_id" :options="$classrooms->pluck('name', 'id')->all()" placeholder="برای همه کلاس‌های دوره" data-resource-classroom/>
      <x-admin.switch label="این فایل رایگان است" name="is_free" :checked="true"/>
      <x-admin.input label="قیمت (تومان)" name="price" type="number" min="0" :value="0"/>
      <x-admin.select label="چه کسانی ببینند؟" name="visibility" :options="['enrolled' => 'دانش‌آموزان ثبت‌نام‌شده', 'public' => 'همه بازدیدکنندگان']" :selected="'enrolled'" data-resource-visibility required/>
      <p class="adm-muted adm-grid-note">فایلی که به یک کلاس مشخص وصل شود فقط برای اعضای مجاز همان کلاس نمایش داده می‌شود.</p>
      <x-admin.input label="زمان نمایش (اختیاری)" name="release_at" type="datetime-local"/>
      <x-admin.select label="وضعیت" name="status" :options="['draft' => 'پیش‌نویس', 'published' => 'نمایش در سایت', 'archived' => 'بایگانی']" :selected="'published'" required/>
    </x-admin.modal>
  @endif

  @foreach($classrooms as $classroom)
    @php
      $classScheduleDays = data_get($classroom->schedule, 'days', []);
      $classFill = [
        'name' => $classroom->name,
        'course_id' => (string) $course->id,
        'teacher_id' => (string) $classroom->teacher_id,
        'meeting_url' => $classroom->meeting_url,
        'status' => $classroom->status,
        'schedule_days' => $classScheduleDays,
        'schedule_time' => data_get($classroom->schedule, 'time'),
        'duration_minutes' => data_get($classroom->schedule, 'duration_minutes', 90),
      ];
    @endphp
    <x-admin.modal :demo="false" :id="'course-class-edit-'.$classroom->id" title="ویرایش کلاس" size="lg" :action="route('admin.classes.update', $classroom)" method="PATCH">
      @include('admin.classes.partials.class-fields', [
        'fixedCourseId' => $course->id,
        'teacherOptions' => $classTeacherOptions,
        'days' => $classDayOptions,
        'statusOptions' => $classStatusOptions,
      ])
    </x-admin.modal>
    <x-admin.modal :demo="false" :id="'quick-session-'.$classroom->id" title="برنامه‌ریزی جلسه برای کلاس" size="lg" :action="route('admin.classes.sessions.store', $classroom)">
      @include('admin.classes.partials.session-fields', ['sessionDefaultDate' => now()->setTimeFromTimeString(data_get($classroom->schedule, 'time') ?: '09:00')->format('Y-m-d\\TH:i')])
    </x-admin.modal>
  @endforeach
@endif
@endpush
