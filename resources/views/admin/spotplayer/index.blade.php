@extends('admin.layouts.app')
@section('title', 'ویدیوهای ضبط‌شده')

@push('head')
<style>
  .adm-video-filters details[open] { grid-column: 1 / -1 }
  @media (min-width: 1051px) {
    .adm-video-filters { grid-template-columns: minmax(200px, 1.6fr) minmax(130px, .8fr) auto auto auto }
  }
</style>
@endpush

@section('content')
@php
  $courseOptions = $courses->pluck('title', 'id')->all();
  $requestedCourse = request()->integer('course_id');
  $selectedCourse = array_key_exists($requestedCourse, $courseOptions) ? $requestedCourse : null;
  $licenseCourseOptions = $courses->filter(fn ($course) => filled($course->spotplayer_course_id))->pluck('title', 'id')->all();
  $studentOptions = $students->pluck('name', 'id')->all();
  $licenseStatus = [
    'active' => ['ok', 'فعال'],
    'waiting_contract' => ['warn', 'در انتظار قرارداد'],
    'queued' => ['info', 'در صف صدور'],
    'revoked' => ['mute', 'باطل‌شده'],
    'failed' => ['bad', 'ناموفق'],
  ];
@endphp

<x-admin.page-head tone="edu" icon="video" title="مدیریت ویدیو و SpotPlayer" desc="ویدیو را به یک دوره وصل کن و وضعیت نمایش آن را بررسی کن.">
  @unless($teacherView)
    @if($courseOptions)
      <x-admin.btn icon="plus" data-modal-open="spot-video-modal">افزودن ویدیو از SpotPlayer</x-admin.btn>
    @endif

    <x-admin.btn variant="line" :href="route('admin.courses.index')">رفتن به دوره‌ها</x-admin.btn>
  @endunless
</x-admin.page-head>
@unless($teacherView)
  <x-admin.panel title="وضعیت اتصال SpotPlayer" data-tone="edu">
    <x-slot:actions>
      <x-admin.badge :tone="$health?->available ? 'ok' : 'warn'">{{ $health?->available ? 'متصل' : 'اتصال فعال نیست' }}</x-admin.badge>
    </x-slot:actions>
    @if(!$health?->available)
      <p class="adm-muted">می‌توانی شناسهٔ ویدیو را ثبت کنی. پخش و صدور لایسنس بعد از فعال‌شدن اتصال در دسترس خواهند بود.</p>
    @endif
    <div class="adm-actions">
      <a class="adm-btn adm-btn-line adm-btn-sm" href="https://panel.spotplayer.ir/" target="_blank" rel="noopener noreferrer">پنل ناشران SpotPlayer</a>
      <a class="adm-btn adm-btn-line adm-btn-sm" href="#spotplayer-connection" data-open-spotplayer-settings>تنظیمات اتصال</a>
    </div>
  </x-admin.panel>
@endunless
@if(!$teacherView && !$courseOptions)
  <p class="adm-muted">برای افزودن ویدیو، «ویدیوهای آموزشی» را در امکانات یکی از دوره‌ها فعال کن.</p>
@endif

<x-admin.panel title="کتابخانه ویدیوها" desc="فایل‌های بارگذاری‌شده و ویدیوهای متصل به SpotPlayer در این فهرست دیده می‌شوند." flush data-tone="edu">
  <form method="GET" action="{{ route('admin.spotplayer.index') }}" class="adm-video-filters">
    <label class="adm-video-search"><x-admin.icon name="search"/><input class="adm-input" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="نام ویدیو، دوره یا کلاس را جستجو کن" aria-label="جستجوی ویدیوها"></label>
    <select class="adm-select" name="status" aria-label="وضعیت پردازش">
      <option value="">همه وضعیت‌ها</option>
      <option value="active" @selected(($filters['status'] ?? '') === 'active')>آماده پخش</option>
      <option value="processing" @selected(($filters['status'] ?? '') === 'processing')>در حال پردازش</option>
      <option value="failed" @selected(($filters['status'] ?? '') === 'failed')>پردازش ناموفق</option>
      <option value="waiting_contract" @selected(($filters['status'] ?? '') === 'waiting_contract')>در انتظار آماده‌سازی</option>
    </select>
    <details class="adm-history-panel" @if(filled($filters['source'] ?? null) || filled($filters['approval'] ?? null)) open @endif>
      <summary>فیلترهای بیشتر <x-admin.icon name="chev"/></summary>
      <div class="adm-form">
    <select class="adm-select" name="source" aria-label="منبع ویدیو">
      <option value="">همه منابع</option>
      <option value="local" @selected(($filters['source'] ?? '') === 'local')>فایل بارگذاری‌شده</option>
      <option value="external" @selected(($filters['source'] ?? '') === 'external')>شناسه SpotPlayer</option>
    </select>
    <select class="adm-select" name="approval" aria-label="وضعیت تأیید">
      <option value="">همه وضعیت‌های تأیید</option>
      <option value="pending" @selected(($filters['approval'] ?? '') === 'pending')>منتظر تأیید</option>
      <option value="approved" @selected(($filters['approval'] ?? '') === 'approved')>تأییدشده</option>
      <option value="rejected" @selected(($filters['approval'] ?? '') === 'rejected')>نیازمند اصلاح</option>
    </select>
      </div>
    </details>
    <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">جست‌وجو</button>
    @if(collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty())
      <a href="{{ route('admin.spotplayer.index') }}" class="adm-btn adm-btn-line adm-btn-sm">پاک‌کردن</a>
    @endif
  </form>
  <div class="adm-video-results"><span>{{ number_format($videos->total()) }} ویدیو</span></div>
  <div class="adm-grid adm-cols-2">
    @forelse($videos as $video)
      @php
        $videoStatus = match ($video->status) {
          'active' => ['ok', 'آماده پخش'],
          'processing' => ['warn', 'در حال پردازش'],
          'failed' => ['bad', 'پردازش ناموفق'],
          'waiting_contract' => ['warn', 'در انتظار قرارداد'],
          default => ['mute', $video->status],
        };
      @endphp
      <article class="adm-card">
        <div class="adm-card-body">
          <div class="adm-card-title">
            <div><h3>{{ $video->title }}</h3><p>{{ $video->course?->title ?? 'دوره نامشخص' }} · شناسه داخلی #{{ $video->id }}</p></div>
            <x-admin.badge :tone="$videoStatus[0]">{{ $videoStatus[1] }}</x-admin.badge>
          </div>
          <div class="adm-meta">
            <span><x-admin.icon name="users"/>{{ $video->classroom?->name ?? 'در سطح کل دوره' }}</span>
            @if($video->classSession)<span><x-admin.icon name="cal"/>{{ $video->classSession->title }}</span>@endif
            <span><x-admin.icon name="clock"/>{{ ($video->staged_at ? \App\Support\PersianDate::format($video->staged_at, 'Y/m/d H:i') : 'زمان ثبت نامشخص') }}</span>
          </div>
          <div class="adm-card-foot">
            <span class="adm-video-source">{{ $video->file_path ? 'فایل بارگذاری‌شده' : 'شناسه SpotPlayer' }}</span>
            @if($video->approval_status === 'pending')
              <x-admin.badge tone="warn">در انتظار تأیید مدیر</x-admin.badge>
            @elseif($video->approval_status === 'rejected')
              <x-admin.badge tone="bad">نیازمند اصلاح</x-admin.badge>
            @else
              <x-admin.badge tone="ok">تأیید انتشار</x-admin.badge>
            @endif
            @if($video->file_path)
              <span class="adm-muted">{{ number_format(($video->file_size_bytes ?? 0) / 1048576, 1) }} مگابایت</span>
            @else
              <span class="adm-muted">فقط شناسه خارجی</span>
            @endif
          </div>
          @if($video->review_note)<p class="adm-panel-kicker">یادداشت بررسی: {{ $video->review_note }}</p>@endif
          @if($video->processing_error)<p class="adm-panel-kicker">خطای پردازش: {{ $video->processing_error }}</p>@endif
          <div class="adm-actions">
            @if($video->status === 'failed' && $video->file_path)
              <form method="POST" action="{{ route('admin.spotplayer.videos.retry-processing', $video) }}">
                @csrf
                <button type="submit" class="adm-btn adm-btn-line adm-btn-sm">تلاش مجدد پردازش</button>
              </form>
            @endif
          </div>
          @if(!$teacherView && $video->approval_status === 'pending')
            <div class="adm-integration-review">
              <form method="POST" action="{{ route('admin.spotplayer.videos.approve', $video) }}">
                @csrf
                <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">تأیید ویدیو</button>
              </form>
              <form method="POST" action="{{ route('admin.spotplayer.videos.reject', $video) }}" class="adm-stack">
                @csrf
                <textarea class="adm-input" name="reason" rows="2" minlength="5" maxlength="2000" required placeholder="برای بازگشت ویدیو، دلیل اصلاح را بنویسید"></textarea>
                <button type="submit" class="adm-btn adm-btn-line adm-btn-sm">رد و درخواست اصلاح</button>
              </form>
            </div>
          @endif
        </div>
      </article>
    @empty
      <x-admin.empty icon="video" text="هنوز ویدیویی برای این محدوده ثبت نشده است."/>
    @endforelse
  </div>
  <x-slot:footer>{{ $videos->links() }}</x-slot:footer>
</x-admin.panel>

@if(!$teacherView)
  <details id="spotplayer-connection" class="adm-video-advanced" @if($errors->hasAny(['account_id','api_key','max_devices','mode','student_id'])) open @endif>
  <summary><span><b>تنظیمات SpotPlayer و دسترسی دانش‌آموزان</b><small>لایسنس‌ها، وضعیت اتصال و ابزارهای مدیریتی؛ در کارهای روزمره نیازی به این بخش نیست.</small></span><x-admin.icon name="chev"/></summary>
  <div class="adm-video-advanced-body">
  <div class="adm-video-connection">
    <div><b>وضعیت اتصال SpotPlayer</b><p>{{ $health?->message ?? 'وضعیت اتصال سرویس در دسترس نیست.' }}</p></div>
    <x-admin.badge :tone="$health?->available ? 'ok' : 'warn'">{{ $health?->available ? 'متصل' : 'در انتظار قرارداد' }}</x-admin.badge>
  </div>
  <div class="adm-actions">
    <form method="POST" action="{{ route('admin.spotplayer.sync-courses') }}">@csrf<button type="submit" class="adm-btn adm-btn-line adm-btn-sm">درخواست همگام‌سازی دوره‌ها</button></form>
  </div>
  <x-admin.panel title="درخواست لایسنس دانش‌آموز" desc="فقط برای دانش‌آموز دارای دسترسی معتبر به دوره درخواست ثبت کن." data-tone="edu">
    @if(count($licenseCourseOptions) && count($studentOptions))
      <form method="POST" action="{{ route('admin.spotplayer.licenses.store') }}" class="adm-form">
        @csrf
        <x-admin.select label="دوره ویدیویی" name="course_id" :options="$licenseCourseOptions" required/>
        <x-admin.select label="دانش‌آموز" name="student_id" :options="$studentOptions" required/>
        <div class="wide"><button type="submit" class="adm-btn adm-btn-primary">ثبت درخواست لایسنس</button></div>
      </form>
    @else
      <p class="adm-muted">برای صدور درخواست، ابتدا قابلیت ویدیویی دوره را فعال کنید؛ شناسه خارجی و وب‌سرویس می‌توانند بعداً تکمیل شوند. دانش‌آموز باید ثبت‌نام معتبر داشته باشد.</p>
    @endif
  </x-admin.panel>

  <x-admin.panel title="لایسنس‌های صادرشده / در انتظار" flush data-tone="edu">
    <div class="adm-grid adm-cols-2">
      @forelse($licenses as $license)
        @php $status = $licenseStatus[$license->status] ?? ['mute', $license->status]; @endphp
        <article class="adm-card">
          <div class="adm-card-body">
            <div class="adm-card-title">
              <div><h3>{{ $license->student?->name ?? 'دانش‌آموز نامشخص' }}</h3><p>{{ $license->course?->title ?? 'دوره نامشخص' }}</p></div>
              <x-admin.badge :tone="$status[0]">{{ $status[1] }}</x-admin.badge>
            </div>
            <div class="adm-meta"><span>تاریخ صدور: {{ ($license->issued_at ? \App\Support\PersianDate::format($license->issued_at, 'Y/m/d H:i') : 'هنوز صادر نشده') }}</span></div>
            @if($license->error_message)<p class="adm-panel-kicker">{{ $license->error_message }}</p>@endif
            @if($license->status === 'active' && $license->external_license_id)
              <form method="POST" action="{{ route('admin.spotplayer.licenses.revoke', $license) }}" class="adm-integration-revoke">
                @csrf
                <input type="text" name="reason" class="adm-input" placeholder="دلیل درخواست ابطال" required maxlength="300">
                <button type="submit" class="adm-btn adm-btn-bad adm-btn-sm">درخواست ابطال لایسنس</button>
              </form>
            @else
              <span class="adm-muted">در حال حاضر اقدامی لازم نیست.</span>
            @endif
          </div>
        </article>
      @empty
        <x-admin.empty icon="lock" text="هنوز لایسنسی ثبت نشده است."/>
      @endforelse
    </div>
    <x-slot:footer>{{ $licenses->links() }}</x-slot:footer>
  </x-admin.panel>

  <x-admin.panel title="تاریخچه عملیات یکپارچه‌سازی" flush data-tone="shop">
    <div class="adm-table-wrap"><table class="adm-table">
      <thead><tr><th>عملیات</th><th>شناسه پیگیری</th><th>وضعیت</th><th>زمان</th></tr></thead>
      <tbody>
        @forelse($operations as $operation)
          <tr>
            <td>{{ $operation->action }}</td>
            <td class="adm-num">{{ $operation->correlation_id }}</td>
            <td><x-admin.badge tone="{{ $operation->status === 'completed' ? 'ok' : 'warn' }}">{{ $operation->status }}</x-admin.badge></td>
            <td>{{ \App\Support\PersianDate::format($operation->created_at, 'Y/m/d H:i') }}</td>
          </tr>
        @empty
          <tr><td colspan="4">عملیاتی ثبت نشده است.</td></tr>
        @endforelse
      </tbody>
    </table></div>
  </x-admin.panel>

  <x-admin.panel title="تنظیمات اتصال" desc="کلید API رمزنگاری‌شده ذخیره می‌شود؛ اتصال خارجی تا دریافت قرارداد رسمی فعال نخواهد شد." data-tone="shop">
    <form method="POST" action="{{ route('admin.spotplayer.settings') }}" class="adm-form">
      @csrf
      @method('PATCH')
      <x-admin.input label="شناسه حساب" name="account_id" :value="$settings?->account_id"/>
      <x-admin.field label="کلید API (برای حفظ مقدار فعلی خالی بگذارید)" name="api_key">
        <input class="adm-input" type="password" name="api_key" value="" autocomplete="new-password">
      </x-admin.field>
      <x-admin.input label="حداکثر دستگاه" name="max_devices" type="number" min="1" max="99" :value="$settings?->max_devices ?? 2"/>
      <x-admin.select label="محیط" name="mode" :options="['test' => 'آزمایشی', 'production' => 'عملیاتی']" :selected="$settings?->mode ?? 'test'"/>
      <x-admin.switch label="صدور خودکار لایسنس پس از خرید تأییدشده" name="api_enabled" :checked="$settings?->api_enabled ?? false"/>
      <x-admin.switch label="واترمارک اطلاعات دانش‌آموز" name="watermark_enabled" :checked="$settings?->watermark_enabled ?? true"/>
      <div class="wide"><button type="submit" class="adm-btn adm-btn-primary">ذخیره تنظیمات محلی</button></div>
    </form>
  </x-admin.panel>
  </div>
</details>
@endif
@endsection

@push('modals')
@if(!$teacherView && $courseOptions)
<x-admin.modal :demo="false" id="spot-video-modal" title="افزودن ویدیو از SpotPlayer" :action="route('admin.spotplayer.videos.store')">
  <x-admin.input label="عنوان ویدیو" name="title" wide required/>
  <x-admin.select label="دوره" name="course_id" :options="$courseOptions" :selected="$selectedCourse" placeholder="انتخاب دوره" required/>
  <x-admin.field label="کلاس (اختیاری)" name="classroom_id">
    <select name="classroom_id" class="adm-select">
      <option value="">کل دوره</option>
      @foreach($classrooms as $classroom)
        <option value="{{ $classroom->id }}" data-course-id="{{ $classroom->course_id }}" @selected((string) old('classroom_id') === (string) $classroom->id)>{{ $classroom->name }}</option>
      @endforeach
    </select>
  </x-admin.field>
  <x-admin.input label="شناسه ویدیوی موجود در SpotPlayer" name="external_id" placeholder="شناسه خارجی" required/>
  <input type="hidden" name="status" value="waiting_contract">
  <p class="adm-muted wide">شناسه را از صفحه همان ویدیو در SpotPlayer کپی کن. کلاس را فقط وقتی انتخاب کن که این ویدیو مخصوص یک کلاس باشد.</p>
</x-admin.modal>
@endif
@endpush

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const settings = document.getElementById('spotplayer-connection');
    document.querySelector('[data-open-spotplayer-settings]')?.addEventListener('click', () => {
      if (settings) settings.open = true;
    });
    if (settings && location.hash === '#spotplayer-connection') settings.open = true;

    const modal = document.getElementById('spot-video-modal');
    if (!modal) return;
    const course = modal.querySelector('[name="course_id"]');
    const classroom = modal.querySelector('[name="classroom_id"]');
    const syncClassrooms = () => {
      Array.from(classroom.options).slice(1).forEach((option) => {
        option.disabled = option.hidden = option.dataset.courseId !== course.value;
      });
      if (classroom.selectedOptions[0]?.disabled) classroom.value = '';
    };
    course.addEventListener('change', syncClassrooms);
    syncClassrooms();
    @if($selectedCourse || (old('status') === 'waiting_contract' && $errors->hasAny(['title','course_id','classroom_id','external_id'])))
      document.querySelector('[data-modal-open="spot-video-modal"]')?.click();
    @endif
  });
</script>
@endpush
