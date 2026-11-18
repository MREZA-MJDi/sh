@extends('admin.layouts.app')
@section('title', 'SpotPlayer')

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

<x-admin.page-head tone="edu" icon="video" title="یکپارچه‌سازی SpotPlayer" desc="مدیریت ویدیوهای دوره، درخواست لایسنس و وضعیت اتصال امن">
  @if(!$teacherView)
    <form method="POST" action="{{ route('admin.spotplayer.sync-courses') }}">@csrf<button type="submit" class="adm-btn adm-btn-line">درخواست همگام‌سازی دوره‌ها</button></form>
  @endif
  <x-admin.btn icon="plus" data-modal-open="spot-video-modal">ثبت ویدیو</x-admin.btn>
</x-admin.page-head>

@if(!$teacherView)
  <div class="adm-stats">
    <x-admin.stat tone="edu" icon="video" label="ویدیوهای ثبت‌شده" :value="number_format($videos->total())" hint="فایل خصوصی یا شناسه خارجی"/>
    <x-admin.stat tone="edu" icon="lock" label="لایسنس‌ها" :value="number_format($licenses->total())" hint="وضعیت محلی و خارجی"/>
    <x-admin.stat tone="edu" icon="refresh" label="عملیات در انتظار" :value="number_format($operations->whereIn('status', ['waiting_contract', 'queued', 'pending'])->count())" hint="تا دریافت قرارداد اجرا نمی‌شوند"/>
    <x-admin.stat tone="edu" icon="activity" label="وضعیت سرویس" :value="$health?->available ? 'متصل' : 'در انتظار'" :hint="$health?->message ?? 'اتصال فقط پس از دریافت قرارداد فعال می‌شود'"/>
  </div>

  <x-admin.panel title="وضعیت اتصال" desc="{{ $health?->message }}" data-tone="shop">
    <div class="adm-row-between">
      <b>وضعیت درگاه SpotPlayer</b>
      <x-admin.badge :tone="$health?->available ? 'ok' : 'warn'">{{ $health?->state ?? 'contract_missing' }}</x-admin.badge>
    </div>
    <p class="adm-muted">شیخان در حال حاضر لایسنس موفق یا لینک پخش جعلی تولید نمی‌کند. درخواست‌ها به‌صورت امن ثبت می‌شوند و بعد از دریافت مستندات، کلید و دسترسی واقعی از ارائه‌دهنده دریافت خواهد شد.</p>
  </x-admin.panel>
@endif

<x-admin.panel title="کتابخانه ویدیوها" desc="ویدیوها در فضای خصوصی ثبت می‌شوند؛ فایل خام در پنل دانش‌آموز قابل دریافت نیست." flush>
  <div class="adm-table-wrap">
    <table class="adm-table">
      <thead><tr><th>ویدیو</th><th>دوره / کلاس</th><th>وضعیت</th><th>فایل</th><th></th></tr></thead>
      <tbody>
        @forelse($videos as $video)
          <tr>
            <td><b>{{ $video->title }}</b><small>شناسه داخلی #{{ $video->id }} · {{ $video->external_id ?: 'شناسه خارجی ثبت نشده' }}</small></td>
            <td>{{ $video->course?->title ?? '—' }}<small>{{ $video->classroom?->name ?? 'مشترک در سطح دوره' }}</small></td>
            <td>
              @php
                $videoStatus = match ($video->status) {
                  'active' => ['ok', 'آماده پخش'],
                  'processing' => ['warn', 'در حال پردازش'],
                  'failed' => ['bad', 'پردازش ناموفق'],
                  'waiting_contract' => ['warn', 'در انتظار قرارداد'],
                  default => ['warn', $video->status],
                };
              @endphp
              <x-admin.badge :tone="$videoStatus[0]">{{ $videoStatus[1] }}</x-admin.badge>
              @if($video->processing_error)<small>{{ $video->processing_error }}</small>@endif
            </td>
            <td>
              @if($video->file_path)
                <a href="{{ route('admin.spotplayer.videos.download', $video) }}">{{ $video->status === 'active' ? 'دریافت فایل پردازش‌شده' : 'دریافت فایل مبدأ' }}</a>
                <small>{{ number_format(($video->file_size_bytes ?? 0) / 1048576, 1) }} MB</small>
              @else
                <span class="adm-muted">فقط شناسه خارجی</span>
              @endif
            </td>
            <td>
              <span class="adm-muted">{{ $video->staged_at?->format('Y/m/d H:i') ?? '—' }}</span>
              @if($video->status === 'failed' && $video->file_path)
                <form method="POST" action="{{ route('admin.spotplayer.videos.retry-processing', $video) }}" style="margin-top:8px">
                  @csrf
                  <button type="submit" class="adm-btn adm-btn-line adm-btn-sm">تلاش مجدد پردازش</button>
                </form>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="5">هنوز ویدیویی برای این محدوده ثبت نشده است.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <x-slot:footer>{{ $videos->links() }}</x-slot:footer>
</x-admin.panel>

@if(!$teacherView)
  <x-admin.panel title="درخواست لایسنس دانش‌آموز" desc="برای دانش‌آموز دارای ثبت‌نام معتبر درخواست محلی ثبت می‌شود؛ اگر شناسه خارجی یا API هنوز آماده نباشد، وضعیت در انتظار می‌ماند." data-tone="edu">
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

  <x-admin.panel title="لایسنس‌های صادرشده / در انتظار" flush>
    <div class="adm-table-wrap">
      <table class="adm-table">
        <thead><tr><th>دانش‌آموز</th><th>دوره</th><th>وضعیت</th><th>تاریخ صدور</th><th>عملیات</th></tr></thead>
        <tbody>
          @forelse($licenses as $license)
            <tr>
              <td>{{ $license->student?->name ?? '—' }}</td>
              <td>{{ $license->course?->title ?? '—' }}</td>
              <td>
                @php $status = $licenseStatus[$license->status] ?? ['mute', $license->status]; @endphp
                <x-admin.badge :tone="$status[0]">{{ $status[1] }}</x-admin.badge>
                @if($license->error_message)<small>{{ $license->error_message }}</small>@endif
              </td>
              <td>{{ $license->issued_at?->format('Y/m/d H:i') ?? '—' }}</td>
              <td>
                @if($license->status === 'active' && $license->external_license_id)
                  <form method="POST" action="{{ route('admin.spotplayer.licenses.revoke', $license) }}" class="adm-actions">
                    @csrf
                    <input type="text" name="reason" class="adm-input" placeholder="دلیل ابطال" required maxlength="300">
                    <button type="submit" class="adm-btn adm-btn-bad adm-btn-sm">درخواست ابطال</button>
                  </form>
                @else
                  <span class="adm-muted">نیازی به اقدام نیست</span>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="5">هنوز لایسنسی ثبت نشده است.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <x-slot:footer>{{ $licenses->links() }}</x-slot:footer>
  </x-admin.panel>

  <x-admin.panel title="تاریخچه عملیات یکپارچه‌سازی" flush>
    <div class="adm-table-wrap"><table class="adm-table">
      <thead><tr><th>عملیات</th><th>شناسه پیگیری</th><th>وضعیت</th><th>زمان</th></tr></thead>
      <tbody>
        @forelse($operations as $operation)
          <tr>
            <td>{{ $operation->action }}</td>
            <td class="adm-num">{{ $operation->correlation_id }}</td>
            <td><x-admin.badge tone="{{ $operation->status === 'completed' ? 'ok' : 'warn' }}">{{ $operation->status }}</x-admin.badge></td>
            <td>{{ $operation->created_at?->format('Y/m/d H:i') }}</td>
          </tr>
        @empty
          <tr><td colspan="4">عملیاتی ثبت نشده است.</td></tr>
        @endforelse
      </tbody>
    </table></div>
  </x-admin.panel>

  <x-admin.panel title="تنظیمات محلی SpotPlayer" desc="کلید API رمزنگاری‌شده ذخیره می‌شود؛ فعال‌سازی اتصال تا دریافت قرارداد عمداً خاموش است." data-tone="shop">
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
@endif
@endsection

@push('modals')
<x-admin.modal :demo="false" id="spot-video-modal" title="ثبت ویدیوی SpotPlayer" size="lg" :action="route('admin.spotplayer.videos.store')">
  <x-admin.legend title="اطلاعات ویدیو"/>
  <x-admin.input label="عنوان ویدیو" name="title" wide required/>
  <x-admin.select label="دوره" name="course_id" :options="$courseOptions" required/>
  <x-admin.select label="کلاس / گروه (اختیاری)" name="classroom_id" :options="$classroomOptions" placeholder="در سطح کل دوره"/>
  <x-admin.input label="شناسه ویدیوی موجود در SpotPlayer" name="external_id" placeholder="شناسه خارجی" required/>
  <x-admin.select label="وضعیت محلی" name="status" :options="['waiting_contract' => 'در انتظار قرارداد']" selected="waiting_contract"/>
  <p class="adm-muted" style="grid-column:1/-1">این فرم فقط برای ثبت شناسه ویدیوی موجود در SpotPlayer است. برای بارگذاری فایل جدید، از صفحه ویرایش همان دوره و آپلود تکه‌ای استفاده کنید تا امکان ادامه آپلود و پردازش امن حفظ شود.</p>
</x-admin.modal>
@endpush
