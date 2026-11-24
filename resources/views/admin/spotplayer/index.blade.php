@extends('admin.layouts.app')
@section('title', 'ویدیوهای ضبط‌شده')

@section('content')
@php
  $courseOptions = $courses->pluck('title', 'id')->all();
  $licenseCourseOptions = $courses->filter(fn ($course) => filled($course->spotplayer_course_id))->pluck('title', 'id')->all();
  $classroomOptions = $classrooms->pluck('name', 'id')->all();
  $studentOptions = $students->pluck('name', 'id')->all();
  $licenseStatus = [
    'active' => ['ok', 'فعال'],
    'waiting_contract' => ['warn', 'در انتظار قرارداد'],
    'queued' => ['info', 'در صف صدور'],
    'revoked' => ['mute', 'باطل‌شده'],
    'failed' => ['bad', 'ناموفق'],
  ];
@endphp

<x-admin.page-head tone="edu" icon="video" title="ویدیوهای ضبط‌شده" desc="همه ویدیوهای دوره‌ها را از یک‌جا پیدا کن و وضعیت آماده‌سازی آن‌ها را ببین.">
  @unless($teacherView)
    <x-admin.btn icon="plus" :href="route('admin.courses.index')">بارگذاری ویدیو در یک دوره</x-admin.btn>
  @endunless
</x-admin.page-head>

<div class="adm-video-overview" data-tone="edu">
  <x-admin.stat tone="edu" icon="video" label="همه ویدیوها" :value="number_format($videoStats['total'])" hint="در محدوده دسترسی تو"/>
  <x-admin.stat tone="edu" icon="refresh" label="در حال آماده‌سازی" :value="number_format($videoStats['processing'])" hint="پس از پردازش قابل پخش می‌شوند"/>
  <x-admin.stat tone="edu" icon="alert" label="نیازمند پیگیری" :value="number_format($videoStats['failed'])" hint="پردازش ناموفق"/>
  @unless($teacherView)
    <x-admin.stat tone="edu" icon="check" label="منتظر تأیید" :value="number_format($videoStats['review'])" hint="ویدیوهای ارسال‌شده از طرف مدرس"/>
  @endunless
</div>

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
    <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">اعمال فیلتر</button>
    @if(collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty())
      <a href="{{ route('admin.spotplayer.index') }}" class="adm-btn adm-btn-line adm-btn-sm">پاک‌کردن</a>
    @endif
  </form>
  <div class="adm-video-results"><span>{{ number_format($videos->total()) }} ویدیو</span><span>برای افزودن فایل جدید، از صفحه همان دوره اقدام کن.</span></div>
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
            @if($video->file_path)
              <x-admin.btn size="sm" variant="line" :href="route('admin.spotplayer.videos.download', $video)">{{ $video->status === 'active' ? 'دریافت فایل پردازش‌شده' : 'دریافت فایل مبدأ' }}</x-admin.btn>
            @endif
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
  <details class="adm-video-advanced">
  <summary><span><b>تنظیمات پیشرفته و SpotPlayer</b><small>لایسنس‌ها، وضعیت اتصال و ابزارهای مدیریتی؛ در کارهای روزمره نیازی به این بخش نیست.</small></span><x-admin.icon name="chev"/></summary>
  <div class="adm-video-advanced-body">
  <div class="adm-video-connection">
    <div><b>وضعیت اتصال SpotPlayer</b><p>{{ $health?->message ?? 'وضعیت اتصال سرویس در دسترس نیست.' }}</p></div>
    <x-admin.badge :tone="$health?->available ? 'ok' : 'warn'">{{ $health?->available ? 'متصل' : 'در انتظار قرارداد' }}</x-admin.badge>
  </div>
  <div class="adm-actions">
    <form method="POST" action="{{ route('admin.spotplayer.sync-courses') }}">@csrf<button type="submit" class="adm-btn adm-btn-line adm-btn-sm">درخواست همگام‌سازی دوره‌ها</button></form>
    <button type="button" class="adm-btn adm-btn-line adm-btn-sm" data-modal-open="spot-video-modal">ثبت شناسه ویدیوی موجود در SpotPlayer</button>
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
      <x-admin.switch label="واترمارک اطلاعات دانش‌آموز" name="watermark_enabled" :checked="$settings?->watermark_enabled ?? true"/>
      <div class="wide"><button type="submit" class="adm-btn adm-btn-primary">ذخیره تنظیمات محلی</button></div>
    </form>
  </x-admin.panel>
  </div>
</details>
@endif
@endsection

@push('modals')
<x-admin.modal :demo="false" id="spot-video-modal" title="ثبت شناسه ویدیوی SpotPlayer" size="lg" :action="route('admin.spotplayer.videos.store')">
  <x-admin.legend title="اطلاعات ویدیو"/>
  <x-admin.input label="عنوان ویدیو" name="title" wide required/>
  <x-admin.select label="دوره" name="course_id" :options="$courseOptions" required/>
  <x-admin.select label="کلاس / گروه (اختیاری)" name="classroom_id" :options="$classroomOptions" placeholder="در سطح کل دوره"/>
  <x-admin.input label="شناسه ویدیوی موجود در SpotPlayer" name="external_id" placeholder="شناسه خارجی" required/>
  <x-admin.select label="وضعیت محلی" name="status" :options="['waiting_contract' => 'در انتظار قرارداد']" selected="waiting_contract"/>
  <p class="adm-muted" style="grid-column:1/-1">این فرم فقط برای ثبت شناسه ویدیوی موجود در SpotPlayer است. برای بارگذاری فایل جدید، از صفحه ویرایش همان دوره و آپلود تکه‌ای استفاده کنید تا امکان ادامه آپلود و پردازش امن حفظ شود.</p>
</x-admin.modal>
@endpush
