@extends('admin.layouts.app')
@section('title', 'دوره‌ها')

@section('content')
@php
  $seasons = ['spring' => 'بهار', 'summer' => 'تابستان', 'autumn' => 'پاییز', 'winter' => 'زمستان'];
  $statuses = [
    'draft' => ['warn', 'پیش‌نویس'],
    'published' => ['ok', 'منتشرشده'],
    'archived' => ['mute', 'بایگانی‌شده'],
  ];
  $featureLabels = [
    'recorded_video' => 'ویدیوی آموزشی',
    'live_class' => 'کلاس آنلاین',
    'resources' => 'جزوه و منابع',
    'assignments' => 'تکلیف',
    'exams' => 'آزمون',
    'projects' => 'پروژه',
  ];
@endphp

<x-admin.page-head tone="edu" icon="book" title="دوره‌های آموزشی" desc="دوره‌ها را پیدا کن، محتوایشان را کامل کن و وضعیت انتشار را مدیریت کن.">
  @if($canManage)
    <x-admin.btn icon="plus" :href="route('admin.courses.create')">ساخت دوره</x-admin.btn>
  @endif
</x-admin.page-head>

<section class="adm-course-intro" data-tone="edu">
  <div class="adm-course-intro-copy">
    <span class="adm-course-kicker">مدیریت آموزش</span>
    <h2>هر دوره از همین‌جا جلو می‌رود.</h2>
    <p>برای ادامه کار، دوره را باز کن؛ اطلاعات، فصل‌ها، ویدیوها و کلاس‌های مرتبط در دسترس تو هستند.</p>
  </div>
  <div class="adm-course-intro-progress" aria-label="خلاصه وضعیت دوره‌ها">
    <div><strong>{{ number_format($stats['published']) }}</strong><span>دوره منتشرشده</span></div>
    <div><strong>{{ number_format($stats['drafts']) }}</strong><span>نیازمند تکمیل</span></div>
  </div>
</section>

<div class="adm-course-overview">
  <x-admin.stat tone="edu" icon="book" label="همه دوره‌ها" :value="number_format($stats['courses'])" hint="در محدوده دسترسی تو"/>
  <x-admin.stat tone="edu" icon="users" label="دانش‌آموزان فعال" :value="number_format($stats['students'])" hint="با دسترسی معتبر به دوره"/>
  <x-admin.stat tone="edu" icon="file" label="درس‌های منتشرشده" :value="number_format($stats['lessons'])" hint="قابل مشاهده برای دانش‌آموزان"/>
</div>

<section class="adm-panel adm-course-filters" data-tone="edu">
  <form method="GET" action="{{ route('admin.courses.index') }}" class="adm-course-filter-form">
    <label class="adm-course-search">
      <x-admin.icon name="search"/>
      <input class="adm-input" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="نام دوره، درس یا مدرس را جستجو کن" aria-label="جستجوی دوره‌ها">
    </label>
    <select class="adm-select" name="grade" aria-label="پایه تحصیلی">
      <option value="">همه پایه‌ها</option>
      @foreach($grades as $grade => $label)
        <option value="{{ $grade }}" @selected(($filters['grade'] ?? '') === $grade)>{{ $label }}</option>
      @endforeach
    </select>
    <select class="adm-select" name="status" aria-label="وضعیت انتشار">
      <option value="">همه وضعیت‌ها</option>
      @foreach($statuses as $key => $status)
        <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $status[1] }}</option>
      @endforeach
    </select>
    <button class="adm-btn adm-btn-primary adm-btn-sm" type="submit"><x-admin.icon name="search"/>اعمال فیلتر</button>
    @if(collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty())
      <a class="adm-btn adm-btn-line adm-btn-sm" href="{{ route('admin.courses.index') }}">پاک‌کردن</a>
    @endif
  </form>
  <div class="adm-course-results">
    <span>{{ number_format($courses->total()) }} دوره پیدا شد</span>
    <span>برای دیدن جزئیات و ادامه کار، کارت هر دوره را باز کن.</span>
  </div>
</section>

<div class="adm-course-grid" data-tone="edu">
  @forelse($courses as $course)
    @php
      $teacherName = $course->teacher?->name ?? 'مدرس تعیین نشده';
      $coverImageUrl = $course->coverImageUrl ?: $course->teaserPosterUrl;
      $status = $statuses[$course->status] ?? ['mute', $course->status];
      $enabledFeatures = collect($featureLabels)->filter(fn ($label, $key) => $course->featureEnabled($key))->values()->take(3);
      $primaryAction = $canManageContent
        ? url('admin/courses/'.$course->id.'/content')
        : route('admin.courses.index');
    @endphp
    <article class="adm-course-card">
      <div class="adm-course-cover">
        @if($coverImageUrl)
          <img src="{{ $coverImageUrl }}" alt="تصویر {{ $course->title }}" loading="lazy">
        @else
          <div class="adm-course-cover-placeholder"><x-admin.icon name="book"/></div>
        @endif
        <x-admin.badge :tone="$status[0]">{{ $status[1] }}</x-admin.badge>
        <span class="adm-course-grade">{{ $course->grade ?: 'پایه تعیین نشده' }}</span>
      </div>
      <div class="adm-course-card-body">
        <div class="adm-course-title-row">
          <div>
            <h3>{{ $course->title }}</h3>
            <p>{{ $course->subject ?: 'موضوع ثبت نشده' }} · {{ $teacherName }}</p>
          </div>
          <span class="adm-course-season">{{ $seasons[$course->season] ?? 'بدون فصل' }}</span>
        </div>
        <div class="adm-course-facts">
          <span><x-admin.icon name="users"/>{{ number_format($course->active_enrollments_count) }} دانش‌آموز</span>
          <span><x-admin.icon name="file"/>{{ number_format($course->sections_count) }} فصل</span>
          <span><x-admin.icon name="users"/>{{ number_format($course->active_classrooms_count) }} کلاس</span>
        </div>
        @if($enabledFeatures->isNotEmpty())
          <div class="adm-course-features">
            @foreach($enabledFeatures as $feature)
              <span>{{ $feature }}</span>
            @endforeach
            @if(collect($featureLabels)->filter(fn ($label, $key) => $course->featureEnabled($key))->count() > 3)
              <span>+{{ collect($featureLabels)->filter(fn ($label, $key) => $course->featureEnabled($key))->count() - 3 }} مورد</span>
            @endif
          </div>
        @endif
        <div class="adm-course-card-foot">
          <strong class="{{ $course->type === 'free' ? 'is-free' : '' }}">
            {{ $course->type === 'free' ? 'رایگان' : number_format((int) $course->price).' تومان' }}
          </strong>
          <div class="adm-course-card-actions">
            @if($canManageContent)
              <a class="adm-btn adm-btn-primary adm-btn-sm" href="{{ $primaryAction }}">
                {{ $course->status === 'draft' ? 'تکمیل دوره' : 'مدیریت محتوا' }}
                <x-admin.icon name="chev"/>
              </a>
            @endif
            @if($canManage)
              <a class="adm-btn adm-btn-line adm-btn-sm" href="{{ route('admin.courses.edit', $course) }}" aria-label="ویرایش {{ $course->title }}"><x-admin.icon name="edit"/>ویرایش</a>
            @endif
          </div>
        </div>
      </div>
    </article>
  @empty
    <div class="adm-course-empty">
      <span><x-admin.icon name="book"/></span>
      <h3>{{ collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty() ? 'دوره‌ای با این مشخصات پیدا نشد' : 'هنوز دوره‌ای ساخته نشده است' }}</h3>
      <p>{{ collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty() ? 'فیلترها را پاک کن یا عبارت دیگری جستجو کن.' : 'اولین دوره را بساز و بعد محتوای آموزشی و کلاس‌هایش را اضافه کن.' }}</p>
      @if(collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty())
        <a class="adm-btn adm-btn-line" href="{{ route('admin.courses.index') }}">نمایش همه دوره‌ها</a>
      @elseif($canManage)
        <x-admin.btn icon="plus" :href="route('admin.courses.create')">ساخت اولین دوره</x-admin.btn>
      @endif
    </div>
  @endforelse
</div>

@if($courses->hasPages())
  <div class="adm-course-pagination">{{ $courses->links() }}</div>
@endif
@endsection
