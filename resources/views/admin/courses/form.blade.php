@extends('admin.layouts.app')
@section('title', $isEditing ? 'ویرایش صفحه دوره' : 'ساخت دوره جدید')

@section('content')
@php
  $seasons = ['spring' => 'بهار', 'summer' => 'تابستان', 'autumn' => 'پاییز', 'winter' => 'زمستان'];
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
@endphp

<x-admin.page-head tone="edu" icon="book" :title="$isEditing ? 'ویرایش صفحه دوره' : 'ساخت دوره جدید'"
  desc="اطلاعات دوره، ویدیوها، درس‌ها و کلاس‌ها را همین‌جا کامل کن؛ لازم نیست بین چند صفحه جابه‌جا شوی.">
  @if($isEditing)
    <x-admin.btn variant="line" icon="external" :href="route('public.courses.show', ['slug' => $course->slug, 'preview' => 1])" target="_blank">پیش‌نمایش صفحه عمومی</x-admin.btn>
  @endif
  <x-admin.btn variant="line" :href="route('admin.courses.index')">بازگشت به دوره‌ها</x-admin.btn>
</x-admin.page-head>

<div class="adm-split" style="align-items:start">
  <main class="adm-stack">
    <form method="POST" action="{{ $formAction }}" class="adm-stack" enctype="multipart/form-data">
      @csrf
      @if($isEditing) @method('PATCH') @endif
      <input type="hidden" name="delivery_type" value="onsite">
      <input type="hidden" name="spotplayer_course_id" value="{{ old('spotplayer_course_id', $course->spotplayer_course_id) }}">

      <x-admin.panel title="۱. معرفی دوره" desc="این اطلاعات در صفحه‌ای که دانش‌آموز می‌بیند نمایش داده می‌شوند." data-tone="edu">
        <div class="adm-form">
          <x-admin.input label="عنوان اصلی دوره" name="title" :value="old('title', $course->title)" placeholder="مثلاً شیمی دوازدهم؛ آموزش مفهومی و مسئله‌محور" wide required/>
          <x-admin.input label="موضوع یا درس" name="subject" :value="old('subject', $course->subject)" placeholder="مثلاً شیمی، زیست‌شناسی، ریاضی"/>
          <x-admin.select label="پایه تحصیلی" name="academic_grade_id" :options="$grades->pluck('name', 'id')->all()" :selected="old('academic_grade_id', $course->academic_grade_id ?: \App\Models\AcademicGrade::resolveIdFromLegacyName($course->grade))" placeholder="انتخاب پایه" required/>
          <x-admin.select label="فصل دوره" name="season" :options="$seasons" :selected="old('season', $course->season)" placeholder="انتخاب فصل" required/>
          <x-admin.select label="مدرس دوره" name="teacher_id" :options="$teachers->pluck('name', 'id')->all()" :selected="old('teacher_id', $course->teacher_id)" placeholder="انتخاب مدرس"/>
          <x-admin.textarea label="توضیح کوتاه زیر عنوان" name="description" :value="old('description', $course->description)" wide rows="4" placeholder="این متن در سربرگ و معرفی دوره نمایش داده می‌شود."/>
          <div class="adm-field" style="grid-column:1/-1">
            <label class="adm-label" for="course-cover-image">تصویر اصلی دوره</label>
            <input id="course-cover-image" class="adm-input" type="file" name="cover_image" accept="image/jpeg,image/png,image/webp">
            <small class="adm-hint">JPG، PNG یا WebP؛ حداکثر ۵ مگابایت. این تصویر روی کارت دوره و سربرگ صفحه عمومی نمایش داده می‌شود.</small>
            @if($isEditing && $course->coverImageUrl)
              <div style="display:flex;align-items:center;gap:14px;margin-top:12px">
                <img src="{{ $course->coverImageUrl }}" alt="تصویر فعلی {{ $course->title }}" style="width:150px;aspect-ratio:16/9;object-fit:cover;border-radius:12px;border:1px solid var(--line)">
                <label class="adm-check"><input type="checkbox" name="remove_cover_image" value="1"> حذف تصویر فعلی</label>
              </div>
            @endif
          </div>
        </div>
      </x-admin.panel>

      <x-admin.panel title="۲. ثبت‌نام و قیمت" desc="قیمت و وضعیت انتشار دوره را مشخص کن." data-tone="shop">
        <div class="adm-form">
          <x-admin.select label="نوع دوره" name="type" :options="['free' => 'رایگان', 'paid' => 'پرداختی']" :selected="old('type', $course->type ?: 'free')" required/>
          <x-admin.input label="قیمت به تومان" name="price" :value="old('price', $course->price)" inputmode="numeric" type="number" min="0" max="1000000000" placeholder="برای دوره رایگان صفر"/>
          <x-admin.select label="وضعیت نمایش در سایت" name="status" :options="$statuses" :selected="old('status', $course->status ?: 'draft')" required/>
        </div>
      </x-admin.panel>

      <x-admin.panel title="۳. امکانات دوره" desc="فقط مواردی را روشن کن که واقعاً در این دوره ارائه می‌شوند." data-tone="edu">
        <div class="adm-form">
          @foreach($featureLabels as $key => $label)
            <x-admin.switch :label="$label" :name="'learning_features['.$key.']'" :checked="(bool) old('learning_features.'.$key, $course->featureEnabled($key))" wide/>
          @endforeach
        </div>
      </x-admin.panel>

      <div class="adm-actions">
        <button type="submit" class="adm-btn adm-btn-primary">{{ $isEditing ? 'ذخیره تغییرات صفحه دوره' : 'ساخت دوره و ادامه تکمیل صفحه' }}</button>
        @if($isEditing)
          <a class="adm-btn adm-btn-line" href="{{ route('public.courses.show', ['slug' => $course->slug, 'preview' => 1]) }}" target="_blank" rel="noopener">دیدن نتیجه در صفحه عمومی</a>
        @endif
      </div>
    </form>

    @if($isEditing)
      <x-admin.panel title="۴. ویدیوی معرفی دوره" desc="این ویدیو بالای صفحه دوره نمایش داده می‌شود. اگر اینترنت قطع شود، بارگذاری از همان‌جا ادامه پیدا می‌کند." data-tone="shop">
        @if($course->teaser_status === 'ready')
          <video controls preload="metadata" style="display:block;width:100%;max-height:360px;border-radius:14px;background:#0f172a" src="{{ route('admin.courses.teaser', $course) }}"></video>
        @elseif($course->teaser_status === 'processing')
          <x-admin.badge tone="warn">در حال پردازش تریلر</x-admin.badge>
          <p class="adm-muted">پس از پایان پردازش، پیش‌نمایش ویدیو در همین قسمت نمایش داده می‌شود.</p>
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
            <span class="adm-upload-icon"><x-admin.icon name="upload"/></span>
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
        <x-admin.panel title="۵. ویدیوهای درس‌ها" desc="ویدیو را انتخاب کن و در صورت تمایل به یک کلاس و جلسه وصلش کن. فایل در فضای امن نگهداری می‌شود." data-tone="edu">
          <div data-course-upload data-target="spotplayer_video" data-course-id="{{ $course->id }}"
            data-start-url="{{ route('admin.uploads.start') }}" data-chunk-url="{{ route('admin.uploads.chunk') }}"
            data-complete-url="{{ route('admin.uploads.complete') }}" data-cancel-url="{{ route('admin.uploads.cancel') }}" data-resume-url="{{ route('admin.uploads.resume') }}" data-status-url="{{ route('admin.uploads.status') }}" data-max-mb="{{ config('learning.max_lesson_mb') }}" data-chunk-mb="{{ config('learning.chunk_mb') }}">
            <div class="adm-form">
              <x-admin.input label="عنوان جلسه / ویدیو" name="video_title" placeholder="مثلاً جلسه اول: ساختار اتم" data-upload-title/>
              <x-admin.select label="گروه آموزشی (اختیاری)" name="classroom_id" :options="$classrooms->pluck('name', 'id')->all()" placeholder="کلاس را انتخاب کنید" data-upload-classroom/>
              <label class="adm-field">
                <span class="adm-label">جلسه مشخص</span>
                <select class="adm-select" name="class_session_id" data-upload-session>
                  <option value="">ابتدا جلسه را انتخاب کنید</option>
                  @foreach($classrooms as $classroom)
                    @foreach($classroom->sessions as $session)
                      <option value="{{ $session->id }}" data-classroom-id="{{ $classroom->id }}">{{ $session->title ?: 'جلسه' }} · {{ $classroom->name }} · {{ $session->starts_at?->format('Y/m/d H:i') }}</option>
                    @endforeach
                  @endforeach
                </select>
              </label>
              <x-admin.input label="شناسه خارجی (در صورت وجود)" name="external_id" placeholder="در صورت داشتن شناسه SpotPlayer" data-upload-external/>
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
                <div class="adm-grow"><b>{{ $video->title }}</b><small class="adm-muted">{{ $video->classroom?->name ?? 'در سطح کل دوره' }}@if($video->classSession) · {{ $video->classSession->title }}@endif · {{ $video->file_original_name ?: $video->external_id ?: 'فایل ثبت‌شده' }}</small>@if($video->processing_error)<small class="adm-muted">{{ $video->processing_error }}</small>@endif</div>
                @php
                  $videoState = match ($video->status) {
                    'active' => ['ok', 'آماده پخش'],
                    'processing' => ['warn', 'در حال پردازش'],
                    'failed' => ['bad', 'پردازش ناموفق'],
                    'waiting_contract' => ['warn', 'در انتظار قرارداد'],
                    default => ['mute', $video->status],
                  };
                @endphp
                <x-admin.badge :tone="$videoState[0]">{{ $videoState[1] }}</x-admin.badge>
              </div>
            @empty
              <x-admin.empty text="هنوز ویدیویی برای این دوره بارگذاری نشده است."/>
            @endforelse
          </div>
          <p class="adm-muted">فایل بارگذاری‌شده مستقیماً از پلیر داخلی پخش می‌شود و نیازی به شناسه یا لینک SpotPlayer ندارد. اگر فقط شناسه خارجی ثبت شود، تا زمان تکمیل اتصال رسمی، وضعیت در انتظار قرارداد می‌ماند.</p>
          @if($videos->hasPages())<div class="adm-pagination">{{ $videos->links() }}</div>@endif
        </x-admin.panel>
      @endif

      @include('admin.courses.partials.curriculum')

      <x-admin.panel title="۷. کلاس‌های این دوره" desc="کلاس یعنی گروهی از دانش‌آموزان که با یک مدرس و برنامه مشخص درس می‌خوانند." data-tone="edu">
        <div class="adm-grid adm-cols-2">
          @forelse($classrooms as $classroom)
            @php
              $next = $classroom->sessions->where('status', 'scheduled')->filter(fn ($item) => $item->starts_at?->isFuture())->sortBy('starts_at')->first();
              $scheduleDays = data_get($classroom->schedule, 'days', []);
              $scheduleLabel = collect($scheduleDays)->map(fn ($day) => \\App\\Support\\PersianDate::weekdayName($day))->join('، ');
            @endphp
            <article class="adm-card">
              <div class="adm-card-body">
                <div class="adm-card-title"><div><h3>{{ $classroom->name }}</h3><p>{{ $classroom->teacher?->name ?? 'مدرس مشخص نشده' }}</p></div><x-admin.badge tone="ok">فعال</x-admin.badge></div>
                <div class="adm-meta">
                  <span><x-admin.icon name="users"/>{{ number_format($classroom->students_count) }} دانش‌آموز</span>
                  <span><x-admin.icon name="calendar"/>{{ number_format($classroom->sessions_count) }} جلسه</span>
                </div>
                <div class="adm-kv">
                  <span>برنامه هفتگی: <b>{{ $scheduleLabel ?: 'شناور' }}</b></span>
                  <span>ساعت معمول: <b>{{ data_get($classroom->schedule, 'time') ?: 'تعیین نشده' }}</b></span>
                </div>
                @if($next)
                  <div class="adm-work-item" data-tone="track"><span><x-admin.icon name="clock"/></span><div><b>جلسه بعدی</b><small>{{ $next->title }} · {{ \\App\\Support\\PersianDate::format($next->starts_at) }}</small></div></div>
                @else
                  <p class="adm-muted">هنوز جلسه آینده‌ای برنامه‌ریزی نشده است.</p>
                @endif
                <div class="adm-actions">
                  <x-admin.btn size="sm" icon="calendar" :href="route('admin.classes.sessions.index', $classroom)">تقویم و جلسه‌ها</x-admin.btn>
                  <x-admin.btn size="sm" variant="soft" icon="check" :href="route('admin.attendance.index', ['class_id' => $classroom->id])">حضور و غیاب</x-admin.btn>
                </div>
              </div>
            </article>
          @empty
            <div class="adm-queue-empty"><x-admin.icon name="users"/><b>هنوز کلاسی برای این دوره نساخته‌ای</b><span>یک کلاس بساز تا دانش‌آموزان، مدرس و جلسه‌ها را کنار هم مدیریت کنی.</span></div>
          @endforelse
        </div>
        <x-slot:footer><x-admin.btn icon="plus" data-modal-open="course-class-modal">ساخت کلاس برای این دوره</x-admin.btn></x-slot:footer>
      </x-admin.panel>

      @if($course->featureEnabled('resources') && auth()->user()->hasPermissionTo('resources.manage'))
        <x-admin.panel title="۸. جزوه و فایل‌های آموزشی" desc="فایل‌هایی که اینجا اضافه می‌کنی در بخش منابع دوره برای دانش‌آموزان مجاز نمایش داده می‌شوند." data-tone="shop">
          <div class="adm-grid adm-cols-2">
            @forelse($resources as $resource)
              <article class="adm-work-item"><span><x-admin.icon name="file"/></span><div class="adm-grow"><b>{{ $resource->title }}</b><small>{{ $resource->kind === 'pdf' ? 'فایل PDF' : ($resource->kind === 'book' ? 'کتاب یا جزوه' : 'فایل آموزشی') }} · {{ $resource->classroom?->name ?? 'برای همه کلاس‌های دوره' }}</small></div><x-admin.badge :tone="$resource->status === 'published' ? 'ok' : 'warn'">{{ $resource->status === 'published' ? 'نمایش در سایت' : 'پیش‌نویس' }}</x-admin.badge></article>
            @empty
              <div class="adm-curriculum-empty">هنوز فایل آموزشی اضافه نشده است.</div>
            @endforelse
          </div>
          <x-slot:footer><x-admin.btn icon="upload" data-modal-open="course-resource-modal">افزودن جزوه یا فایل</x-admin.btn></x-slot:footer>
        </x-admin.panel>
      @endif
    @else
      <x-admin.panel title="ادامه ساخت دوره" desc="اول اطلاعات اصلی را ذخیره کن. بعد همین صفحه برایت باز می‌شود تا ویدیو، درس‌ها و کلاس‌ها را اضافه کنی.">
        <p class="adm-muted">نیازی به ساخت همه چیز در یک مرحله نیست؛ می‌توانی دوره را پیش‌نویس ذخیره کنی و هر زمان خواستی تکمیلش کنی.</p>
      </x-admin.panel>
    @endif
  </main>

  <aside class="adm-stack">
    <x-admin.panel title="جایگاه اطلاعات در صفحه عمومی" desc="راهنمای ساده برای اینکه بدانید هر ورودی کجا دیده می‌شود." data-tone="edu">
      <div class="adm-side-list">
        <div><span class="adm-stat-ico"><x-admin.icon name="type"/></span><div class="adm-grow"><b>عنوان و توضیح کوتاه</b><small class="adm-muted">بالای صفحه عمومی، زیر مسیر راهنما</small></div></div>
        <div><span class="adm-stat-ico"><x-admin.icon name="user"/></span><div class="adm-grow"><b>مدرس و موضوع</b><small class="adm-muted">کنار مشخصات دوره و بخش معرفی مدرس</small></div></div>
        <div><span class="adm-stat-ico"><x-admin.icon name="video"/></span><div class="adm-grow"><b>تریلر</b><small class="adm-muted">بخش معرفی و پیش‌نمایش ویدیویی دوره</small></div></div>
        <div><span class="adm-stat-ico"><x-admin.icon name="list"/></span><div class="adm-grow"><b>فصل‌ها و متن درس‌ها</b><small class="adm-muted">بخش سرفصل‌های آموزشی</small></div></div>
        <div><span class="adm-stat-ico"><x-admin.icon name="wallet"/></span><div class="adm-grow"><b>نوع و قیمت</b><small class="adm-muted">کارت ثبت‌نام کنار صفحه دوره</small></div></div>
      </div>
    </x-admin.panel>
    @if($isEditing)
      <x-admin.panel title="پیش‌نمایش صفحه دوره" desc="قبل از انتشار، نتیجه را مثل دانش‌آموز ببین.">
        <x-admin.btn icon="external" :href="route('public.courses.show', ['slug' => $course->slug, 'preview' => 1])" target="_blank">باز کردن پیش‌نمایش عمومی</x-admin.btn>
      </x-admin.panel>
    @endif
  </aside>
</div>
@endsection

@push('modals')
@if($isEditing)
  <x-admin.modal :demo="false" id="course-class-modal" title="ساخت کلاس جدید" size="lg" :action="route('admin.classes.store')">
    <input type="hidden" name="course_id" value="{{ $course->id }}">
    <input type="hidden" name="status" value="active">
    <x-admin.input label="نام کلاس" name="name" wide required placeholder="مثلاً کلاس آنلاین شیمی دوازدهم"/>
    <x-admin.select label="مدرس کلاس" name="teacher_id" :options="$teachers->pluck('name', 'id')->all()" :selected="$course->teacher_id" placeholder="انتخاب مدرس" required/>
    <x-admin.input label="لینک جلسه (اختیاری)" name="meeting_url" wide placeholder="اگر لینک ثابتی داری اینجا وارد کن"/>
    <x-admin.legend title="برنامه هفتگی"/>
    <x-admin.chips label="روزهای برگزاری" name="schedule_days" :options="['sat' => 'شنبه', 'sun' => 'یکشنبه', 'mon' => 'دوشنبه', 'tue' => 'سه‌شنبه', 'wed' => 'چهارشنبه', 'thu' => 'پنجشنبه', 'fri' => 'جمعه']"/>
    <x-admin.input label="ساعت شروع معمول" name="schedule_time" type="time"/>
    <x-admin.input label="مدت هر جلسه" name="duration_minutes" type="number" min="15" max="300" :value="90" addon="دقیقه"/>
    <x-admin.legend title="دانش‌آموزان کلاس"/>
    <x-admin.chips label="انتخاب دانش‌آموزان" name="student_ids" :options="$students->pluck('name', 'id')->all()" scroll hint="می‌توانی فعلاً خالی بگذاری و بعداً اعضا را اضافه کنی."/>
  </x-admin.modal>

  @if($course->featureEnabled('resources') && auth()->user()->hasPermissionTo('resources.manage'))
    <x-admin.modal :demo="false" id="course-resource-modal" title="افزودن جزوه یا فایل" size="lg" :action="route('admin.resources.store')">
      <input type="hidden" name="course_id" value="{{ $course->id }}">
      <input type="hidden" name="is_free" value="1">
      <input type="hidden" name="downloadable" value="1">
      <x-admin.input label="نام فایل" name="title" wide required placeholder="مثلاً جزوه فصل اول"/>
      <x-admin.textarea label="توضیح کوتاه برای دانش‌آموز" name="description" wide/>
      <x-admin.select label="نوع فایل" name="kind" :options="['pdf' => 'PDF', 'book' => 'کتاب یا جزوه', 'other' => 'سایر فایل‌ها']" :selected="'pdf'" required/>
      <label class="adm-upload adm-upload-wide">
        <input type="file" name="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
        <span class="adm-upload-icon"><x-admin.icon name="upload"/></span>
        <b>فایل را اینجا رها کن یا انتخاب کن</b>
        <small data-upload-name>PDF، Word، تصویر یا ZIP · حداکثر ۳۰ مگابایت</small>
      </label>
      <x-admin.select label="مخصوص کدام کلاس؟" name="classroom_id" :options="$classrooms->pluck('name', 'id')->all()" placeholder="برای همه کلاس‌های دوره"/>
      <x-admin.select label="چه کسانی ببینند؟" name="visibility" :options="['enrolled' => 'دانش‌آموزان ثبت‌نام‌شده', 'authenticated' => 'کاربران واردشده', 'public' => 'همه بازدیدکنندگان']" :selected="'enrolled'" required/>
      <x-admin.input label="زمان نمایش (اختیاری)" name="release_at" type="datetime-local"/>
      <x-admin.select label="وضعیت" name="status" :options="['draft' => 'پیش‌نویس', 'published' => 'نمایش در سایت', 'archived' => 'بایگانی']" :selected="'published'" required/>
    </x-admin.modal>
  @endif
@endif
@endpush
