@extends('admin.layouts.app')
@section('title', 'دوره‌ها')

@section('content')
@php
  $seasons = ['spring' => 'بهار', 'summer' => 'تابستان', 'autumn' => 'پاییز', 'winter' => 'زمستان'];
  $types = ['free' => 'رایگان', 'paid' => 'پرداختی'];
  $deliveryTypes = ['spotplayer' => 'ویدیوی محافظت‌شده (SpotPlayer)', 'skyroom' => 'کلاس آنلاین (Skyroom)', 'onsite' => 'حضوری / ترکیبی'];
  $statuses = ['draft' => 'پیش‌نویس', 'published' => 'منتشرشده', 'archived' => 'بایگانی‌شده'];
  $featureLabels = [
    'recorded_video' => 'آموزش ویدیویی',
    'live_class' => 'کلاس آنلاین و جلسات زنده',
    'resources' => 'جزوه و منابع آموزشی',
    'assignments' => 'تکلیف و پروژه',
    'exams' => 'آزمون و کوییز',
    'projects' => 'پروژه و ارزیابی مرحله‌ای',
  ];
@endphp

<x-admin.page-head tone="edu" icon="book" title="دوره‌ها" desc="مدیریت محصول آموزشی، امکانات یادگیری، مدرس و انتشار">
  @if($canManage)
    <x-admin.btn icon="plus" :href="route('admin.courses.create')">ساخت دوره جدید</x-admin.btn>
  @endif
</x-admin.page-head>

<div class="adm-stats">
  <x-admin.stat tone="edu" icon="book" label="کل دوره‌ها" :value="number_format($stats['courses'])" :hint="$stats['published'].' دوره منتشرشده'"/>
  <x-admin.stat tone="edu" icon="users" label="دانش‌آموزان فعال" :value="number_format($stats['students'])" hint="ثبت‌نام‌های معتبر"/>
  <x-admin.stat tone="edu" icon="file" label="درس‌های منتشرشده" :value="number_format($stats['lessons'])" hint="در سرفصل‌های دوره‌ها"/>
  <x-admin.stat tone="edu" icon="edit" label="پیش‌نویس‌ها" :value="number_format($stats['drafts'])" hint="منتظر تکمیل و انتشار"/>
</div>

<div data-filter class="adm-stack">
  <x-admin.panel flush>
    <x-admin.toolbar placeholder="جستجوی دوره یا مدرس…">
      <x-admin.filter-select key="grade" placeholder="همه پایه‌ها" :options="$grades"/>
      <div class="adm-toolbar-end"><x-admin.seg :options="['on' => 'منتشرشده', 'off' => 'غیرمنتشرشده']"/></div>
    </x-admin.toolbar>
  </x-admin.panel>

  <div class="adm-grid adm-cols-3" data-filter-list>
    @forelse ($courses as $course)
      @php
        $features = [];
        foreach ($featureLabels as $key => $label) {
          $features[$key] = $course->featureEnabled($key);
        }
        $teacherName = $course->teacher?->name ?? 'مدرس تعیین نشده';
        $published = $course->status === 'published';
      @endphp
      <article class="adm-card" data-state="{{ $published ? 'on' : 'off' }}" data-grade="{{ $course->grade }}" data-name="{{ $course->title.' '.$teacherName }}">
        <div class="adm-card-cover">
          @if ($published)
            <x-admin.badge tone="ok">منتشرشده</x-admin.badge>
          @elseif ($course->status === 'archived')
            <x-admin.badge tone="mute">بایگانی‌شده</x-admin.badge>
          @else
            <x-admin.badge tone="warn">پیش‌نویس</x-admin.badge>
          @endif
          <x-admin.icon name="book"/>
        </div>
        <div class="adm-card-body">
          <h3>{{ $course->title }}</h3>
          <div class="adm-meta">
            <span><x-admin.icon name="user"/>{{ $teacherName }}</span>
            @if($course->grade)<span>{{ $course->grade }}</span>@endif
            @if($course->season)<span>{{ $seasons[$course->season] ?? $course->season }}</span>@endif
            @if($course->subject)<span>{{ $course->subject }}</span>@endif
            <span class="adm-num"><x-admin.icon name="users"/>{{ number_format($course->active_enrollments_count) }} دانش‌آموز</span>
            <span class="adm-num"><x-admin.icon name="cls"/>{{ number_format($course->active_classrooms_count) }} کلاس</span>
            <span class="adm-num"><x-admin.icon name="file"/>{{ number_format($course->sections_count) }} فصل</span>
          </div>
          <div class="adm-meta" style="margin-top:10px">
            @foreach($featureLabels as $key => $label)
              @if($features[$key])
                <span class="adm-chip">{{ $label }}</span>
              @endif
            @endforeach
          </div>
          <div class="adm-card-foot">
            @if ($course->type === 'free')
              <span class="adm-price is-free">رایگان</span>
            @else
              <span class="adm-price adm-num">{{ number_format((int) $course->price) }} تومان</span>
            @endif
            <span class="adm-muted">{{ $deliveryTypes[$course->delivery_type] ?? $course->delivery_type }}</span>
          </div>
          <div class="adm-actions">
            @if($canManage)
              <x-admin.btn size="sm" variant="soft" icon="book" :href="route('public.courses.show', ['slug' => $course->slug, 'preview' => 1])" target="_blank">پیش‌نمایش</x-admin.btn>
            @endif
            @if($canManageContent)
              <x-admin.btn size="sm" icon="book" :href="url('admin/courses/'.$course->id.'/content')">محتوا</x-admin.btn>
            @endif
            @if($canManage)
              <x-admin.btn variant="soft" size="sm" icon="edit" :href="route('admin.courses.edit', $course)">ویرایش صفحه دوره</x-admin.btn>
              <x-admin.btn variant="bad" size="sm" icon="trash" data-modal-open="course-del" :data-name="$course->title" :data-action="url('admin/courses/'.$course->id)" aria-label="حذف"/>
            @endif
          </div>
        </div>
      </article>
    @empty
      <x-admin.empty text="هنوز دوره‌ای ثبت نشده است."/>
    @endforelse
  </div>
  @if($courses->hasPages())
    <div class="adm-pager" aria-label="صفحه‌بندی دوره‌ها">
      <p class="adm-pager-info" aria-live="polite">
        نمایش {{ number_format($courses->firstItem()) }} تا {{ number_format($courses->lastItem()) }}
        از {{ number_format($courses->total()) }} دوره
      </p>

      <nav class="adm-pager-nav" aria-label="صفحه‌بندی دوره‌ها">
        @if ($courses->onFirstPage())
          <span class="is-off" aria-disabled="true">قبلی</span>
        @else
          <a href="{{ $courses->previousPageUrl() }}" rel="prev" aria-label="صفحه قبلی">قبلی</a>
        @endif

        @php
          $currentPage = $courses->currentPage();
          $lastPage = $courses->lastPage();
          $startPage = max(1, $currentPage - 1);
          $endPage = min($lastPage, $currentPage + 1);
          $pageUrls = $courses->getUrlRange($startPage, $endPage);
        @endphp

        @if ($startPage > 1)
          <a href="{{ $courses->url(1) }}" aria-label="رفتن به صفحه 1">1</a>
          @if ($startPage > 2)
            <span class="is-gap" aria-hidden="true">…</span>
          @endif
        @endif

        @foreach ($pageUrls as $page => $url)
          @if ($page === $currentPage)
            <span class="is-on" aria-current="page" aria-label="صفحه فعلی، صفحه {{ $page }}">{{ $page }}</span>
          @else
            <a href="{{ $url }}" aria-label="رفتن به صفحه {{ $page }}">{{ $page }}</a>
          @endif
        @endforeach

        @if ($endPage < $lastPage)
          @if ($endPage < $lastPage - 1)
            <span class="is-gap" aria-hidden="true">…</span>
          @endif
          <a href="{{ $courses->url($lastPage) }}" aria-label="رفتن به صفحه {{ $lastPage }}">{{ $lastPage }}</a>
        @endif

        @if ($courses->hasMorePages())
          <a href="{{ $courses->nextPageUrl() }}" rel="next" aria-label="صفحه بعدی">بعدی</a>
        @else
          <span class="is-off" aria-disabled="true">بعدی</span>
        @endif
      </nav>
    </div>
  @endif
</div>
@endsection

