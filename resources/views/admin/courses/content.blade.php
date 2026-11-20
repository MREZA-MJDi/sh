@extends('admin.layouts.app')
@section('title', 'محتوای دوره')

@section('content')
@php
  $featureLabels = [
    'recorded_video' => 'ویدیوی ضبط‌شده',
    'live_class' => 'کلاس آنلاین',
    'resources' => 'منابع آموزشی',
    'assignments' => 'تکلیف و تمرین',
    'exams' => 'آزمون و کوییز',
    'projects' => 'پروژه و ارزیابی مرحله‌ای',
  ];
  $features = [];
  foreach ($featureLabels as $key => $label) {
    $features[$key] = $course->featureEnabled($key);
  }
  $lessonCount = $course->sections->sum(fn ($section) => $section->lessons->count());
@endphp

<x-admin.page-head tone="edu" icon="book" :title="'محتوای دوره: '.$course->title" desc="ساختار واقعی دوره، فصل‌ها، درس‌ها و گروه‌های آموزشی"
  :back-url="route('admin.courses.index')" back-label="دوره‌ها">
  <x-admin.btn icon="plus" data-modal-open="section-modal">افزودن بخش جدید</x-admin.btn>
  <x-admin.btn variant="line" icon="external" :href="route('public.courses.show', $course->slug)" target="_blank">مشاهده صفحه عمومی</x-admin.btn>
</x-admin.page-head>

<div class="adm-stats">
  <x-admin.stat tone="edu" icon="cls" label="فصل‌ها" :value="number_format($course->sections->count())" :hint="number_format($lessonCount).' درس'"/>
  <x-admin.stat tone="edu" icon="users" label="گروه‌های فعال" :value="number_format($course->active_classrooms_count)" hint="کلاس‌های متصل به این دوره"/>
  <x-admin.stat tone="shop" icon="lock" label="ثبت‌نام معتبر" :value="number_format($course->active_enrollments_count)" hint="مجوزهای فعال دوره"/>
  <x-admin.stat tone="edu" icon="calendar" label="جلسات زمان‌بندی‌شده" :value="number_format($course->classrooms->sum('sessions_count'))" hint="در تمام گروه‌ها"/>
</div>

<div class="adm-split">
  <section class="adm-stack" data-tone="edu" aria-label="ساختار آموزشی دوره">
    <x-admin.panel title="امکانات فعال این دوره" desc="امکانات از تنظیمات اصلی دوره کنترل می‌شوند؛ اینجا فقط وضعیت واقعی آن‌ها نمایش داده می‌شود." flush>
      <div class="adm-side-list">
        @foreach($featureLabels as $key => $label)
          <div>
            <span class="adm-stat-ico" data-tone="{{ $features[$key] ? 'edu' : 'muted' }}" style="width:34px;height:34px;border-radius:10px">
              <x-admin.icon :name="$features[$key] ? 'check' : 'minus'"/>
            </span>
            <div class="adm-grow"><b>{{ $label }}</b></div>
            @if($features[$key])<x-admin.badge tone="ok">فعال</x-admin.badge>@else<x-admin.badge tone="warn">غیرفعال</x-admin.badge>@endif
          </div>
        @endforeach
      </div>
      <x-slot:footer>
        <x-admin.btn variant="line" size="sm" :href="route('admin.courses.index')">ویرایش امکانات در تنظیمات دوره</x-admin.btn>
      </x-slot:footer>
    </x-admin.panel>

    @include('admin.courses.partials.curriculum')

  </section>

  <aside class="adm-stack">
    <x-admin.panel title="گروه‌های آموزشی / کلاس‌ها" desc="هر کلاس یک گروه اجرایی برای مدرس، دانش‌آموزان و جلسات خودش است." flush data-tone="edu">
      <x-slot:actions><x-admin.btn size="sm" variant="line" :href="route('admin.classes.index')">مدیریت همه کلاس‌ها</x-admin.btn></x-slot:actions>
      <div class="adm-side-list">
        @forelse($course->classrooms as $classroom)
          <div>
            <span class="adm-stat-ico" data-tone="edu" style="width:38px;height:38px;border-radius:11px"><x-admin.icon name="users"/></span>
            <div class="adm-grow">
              <b>{{ $classroom->name }}</b>
              <small class="adm-muted">{{ $classroom->teacher?->name ?? 'مدرس تعیین نشده' }} · {{ $classroom->students_count }} دانش‌آموز · {{ $classroom->sessions_count }} جلسه</small>
              @if($classroom->skyroom_status === 'waiting_contract')
                <small class="adm-muted">اتصال Skyroom در انتظار قرارداد</small>
              @elseif($classroom->skyroom_status === 'ready')
                <small class="adm-muted">اتاق Skyroom متصل است</small>
              @endif
            </div>
            <x-admin.btn size="sm" variant="soft" :href="route('admin.classes.sessions.index', $classroom)">جلسات</x-admin.btn>
          </div>
        @empty
          <x-admin.empty text="هنوز گروهی برای این دوره ساخته نشده است."/>
        @endforelse
      </div>
    </x-admin.panel>

    @unless($teacherView)
    <x-admin.panel title="وضعیت یکپارچه‌سازی" desc="اتصال خارجی مستقل از ثبت‌نام و ساختار آموزشی نگهداری می‌شود." data-tone="shop">
      <div class="adm-stack">
        <div class="adm-row-between">
          <b>SpotPlayer</b>
          <x-admin.badge :tone="in_array($course->integration_status, ['active', 'ready'], true) ? 'ok' : 'warn'">{{ $course->integration_status ?: 'ثبت نشده' }}</x-admin.badge>
        </div>
        @if($course->integration_note)<p class="adm-muted">{{ $course->integration_note }}</p>@endif
        <x-admin.btn size="sm" variant="line" :href="route('admin.spotplayer.index')">مدیریت ویدیو و لایسنس‌ها</x-admin.btn>
        <div class="adm-divider"></div>
        <p class="adm-muted">مدیریت برنامه و اجرای جلسات از مسیر مستقل کلاس‌ها انجام می‌شود؛ این صفحه روی ساختار دوره، گروه‌های آموزشی و محتوای آموزشی متمرکز است.</p>
        <x-admin.btn size="sm" variant="line" :href="route('admin.quiz24.index')">مدیریت Quiz24 و همگام‌سازی نتایج</x-admin.btn>
      </div>
    </x-admin.panel>
    @endunless

    <x-admin.panel title="فروش دوره" desc="قیمت فعلی دوره در صفحه خرید استفاده می‌شود." data-tone="shop">
      <b class="adm-price">{{ $course->type === 'free' ? 'رایگان' : number_format((int) $course->price).' تومان' }}</b>
      <p class="adm-muted">در این نسخه، فروش در سطح خود دوره انجام می‌شود. بسته‌های دارای مجوزهای جزئی باید به مدل مستقل بسته و مجوز دسترسی متصل شوند؛ این صفحه دیگر پلن‌های نمایشی نشان نمی‌دهد.</p>
      <x-admin.btn size="sm" variant="line" :href="route('admin.courses.index')">ویرایش قیمت و نوع فروش</x-admin.btn>
    </x-admin.panel>
  </aside>
</div>
@endsection

