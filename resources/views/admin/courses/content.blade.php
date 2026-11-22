@extends('admin.layouts.app')
@section('title', 'محتوای دوره')

@section('content')
@php
  $featureLabels = [
    'recorded_video' => 'ویدیوهای ضبط‌شده',
    'live_class' => 'جلسات آنلاین',
    'resources' => 'جزوه و منابع',
    'assignments' => 'تکلیف و تمرین',
    'exams' => 'آزمون و کوییز',
    'projects' => 'پروژه و ارزیابی',
  ];
  $enabledFeatures = collect($featureLabels)->filter(fn ($label, $key) => $course->featureEnabled($key));
  $lessonCount = $course->sections->sum(fn ($section) => $section->lessons->count());
  $publishedLessons = $course->sections->sum(fn ($section) => $section->lessons->where('status', 'published')->count());
@endphp

<x-admin.page-head tone="edu" icon="book" :title="$course->title" desc="ساختار آموزشی دوره را مدیریت کن؛ فصل و درس اضافه کن و کلاس‌های مرتبط را ببین."
  :back-url="route('admin.courses.index')" back-label="همه دوره‌ها">
  <x-admin.btn variant="line" icon="external" :href="route('public.courses.show', ['slug' => $course->slug, 'preview' => 1])" target="_blank">پیش‌نمایش دانش‌آموز</x-admin.btn>
  @unless($teacherView)
    <x-admin.btn icon="edit" :href="route('admin.courses.edit', $course)">ویرایش مشخصات دوره</x-admin.btn>
  @endunless
</x-admin.page-head>

<section class="adm-course-workspace" data-tone="edu">
  <div class="adm-course-workspace-copy">
    <div class="adm-course-workspace-status">
      <x-admin.badge :tone="$course->status === 'published' ? 'ok' : 'warn'">{{ $course->status === 'published' ? 'دوره منتشر شده' : 'دوره هنوز پیش‌نویس است' }}</x-admin.badge>
      <span>{{ $course->grade ?: 'پایه تعیین نشده' }} · {{ $course->subject ?: 'موضوع ثبت نشده' }}</span>
    </div>
    <h2>محتوای آموزشی این دوره</h2>
    <p>از فصل‌ها شروع کن؛ داخل هر فصل درس‌ها را به ترتیب دانش‌آموز بچین. تغییرات این بخش مستقیماً در ساختار دوره اعمال می‌شوند.</p>
  </div>
  <div class="adm-course-workspace-stats">
    <div><strong>{{ number_format($course->sections->count()) }}</strong><span>فصل</span></div>
    <div><strong>{{ number_format($lessonCount) }}</strong><span>درس</span></div>
    <div><strong>{{ number_format($publishedLessons) }}</strong><span>درس منتشرشده</span></div>
  </div>
</section>

<div class="adm-course-content-layout">
  <main class="adm-course-content-main">
    @include('admin.courses.partials.curriculum')
  </main>

  <aside class="adm-course-content-aside">
    <section class="adm-course-side-card" data-tone="edu">
      <div class="adm-course-side-heading">
        <span class="adm-course-side-icon"><x-admin.icon name="users"/></span>
        <div><h3>کلاس‌های این دوره</h3><p>گروه‌هایی که این محتوا را دریافت می‌کنند.</p></div>
      </div>
      <div class="adm-course-class-list">
        @forelse($course->classrooms as $classroom)
          <article class="adm-course-class-item">
            <div class="adm-course-class-top">
              <b>{{ $classroom->name }}</b>
              <x-admin.badge :tone="$classroom->status === 'active' ? 'ok' : 'mute'">{{ $classroom->status === 'active' ? 'فعال' : 'متوقف' }}</x-admin.badge>
            </div>
            <small>{{ $classroom->teacher?->name ?? 'مدرس تعیین نشده' }}</small>
            <div class="adm-course-class-facts">
              <span>{{ number_format($classroom->students_count) }} دانش‌آموز</span>
              <span>{{ number_format($classroom->sessions_count) }} جلسه</span>
            </div>
            <a class="adm-course-class-link" href="{{ route('admin.classes.sessions.index', $classroom) }}">دیدن برنامه کلاس <x-admin.icon name="chev"/></a>
          </article>
        @empty
          <div class="adm-course-side-empty">
            <x-admin.icon name="users"/>
            <b>هنوز کلاسی به این دوره وصل نشده</b>
            <p>برای برنامه‌ریزی جلسه و حضور و غیاب، ابتدا یک گروه آموزشی بساز.</p>
            @unless($teacherView)
              <a class="adm-btn adm-btn-primary adm-btn-sm" href="{{ route('admin.courses.edit', $course) }}">ساخت کلاس</a>
            @endunless
          </div>
        @endforelse
      </div>
      <div class="adm-course-side-footer">
        <a class="adm-btn adm-btn-line adm-btn-sm" href="{{ route('admin.classes.index') }}">مدیریت همه کلاس‌ها</a>
      </div>
    </section>

    <section class="adm-course-side-card" data-tone="shop">
      <div class="adm-course-side-heading">
        <span class="adm-course-side-icon"><x-admin.icon name="video"/></span>
        <div><h3>ویدیوهای ضبط‌شده</h3><p>فایل‌های دوره و وضعیت آماده‌سازی آن‌ها.</p></div>
      </div>
      <p class="adm-course-side-description">برای بارگذاری فایل ویدیویی، به صفحه ویرایش دوره برو. برای مرور همه ویدیوها از کتابخانه استفاده کن.</p>
      <div class="adm-course-side-footer">
        @unless($teacherView)
          <a class="adm-btn adm-btn-primary adm-btn-sm" href="{{ route('admin.courses.edit', $course) }}">مدیریت ویدیوهای این دوره</a>
        @endunless
        <a class="adm-btn adm-btn-line adm-btn-sm" href="{{ route('admin.spotplayer.index') }}">کتابخانه ویدیوها</a>
      </div>
    </section>

    <details class="adm-course-side-details">
      <summary><span><b>امکانات فعال دوره</b><small>{{ $enabledFeatures->count() }} قابلیت فعال</small></span><x-admin.icon name="chev"/></summary>
      <div class="adm-course-feature-list">
        @foreach($featureLabels as $key => $label)
          <div>
            <span>{{ $label }}</span>
            @if($course->featureEnabled($key))
              <x-admin.badge tone="ok">فعال</x-admin.badge>
            @else
              <x-admin.badge tone="mute">غیرفعال</x-admin.badge>
            @endif
          </div>
        @endforeach
      </div>
      @unless($teacherView)
        <a class="adm-course-side-edit" href="{{ route('admin.courses.edit', $course) }}">تغییر امکانات دوره</a>
      @endunless
    </details>
  </aside>
</div>
@endsection
