@extends('admin.layouts.app')
@section('title', 'گروه‌های آموزشی')

@section('content')
@php
  $teacherOptions = $teachers->pluck('name', 'id')->all();
  $courseOptions = $courses->pluck('title', 'id')->all();
  $studentOptions = $students->pluck('name', 'id')->all();
  $days = ['sat' => 'شنبه', 'sun' => 'یکشنبه', 'mon' => 'دوشنبه', 'tue' => 'سه‌شنبه', 'wed' => 'چهارشنبه', 'thu' => 'پنجشنبه', 'fri' => 'جمعه'];
  $statusOptions = ['active' => 'فعال', 'paused' => 'متوقف', 'archived' => 'بایگانی'];
@endphp

<x-admin.page-head tone="edu" icon="cls" title="گروه‌های آموزشی" desc="هر گروه، مدرس، دانش‌آموزان و برنامه خودش را دارد. برای مدیریت جلسه‌ها، گروه را باز کن.">
  <x-admin.btn variant="line" icon="cal" :href="route('admin.online-classes.index')">همه جلسات آنلاین</x-admin.btn>
  @if($canManage)
    <x-admin.btn icon="plus" data-modal-open="class-modal">گروه جدید</x-admin.btn>
  @endif
</x-admin.page-head>

<div class="adm-stats">
  <x-admin.stat tone="edu" icon="cls" label="کل گروه‌ها" :value="number_format($stats['total'])"/>
  <x-admin.stat tone="edu" icon="check" label="گروه‌های فعال" :value="number_format($stats['active'])"/>
  <x-admin.stat tone="edu" icon="users" label="دانش‌آموزان یکتا" :value="number_format($stats['students'])" hint="عضو گروه‌های قابل مشاهده"/>
  <x-admin.stat tone="edu" icon="teacher" label="بدون مدرس" :value="number_format($stats['without_teacher'])"/>
</div>

<div class="adm-stack">
  <x-admin.panel title="پیدا کردن گروه آموزشی" desc="نام کلاس، دوره یا مدرس را جستجو کن؛ نتایج با فیلترهای انتخابی از سرور دریافت می‌شوند." flush data-tone="edu">
    <form method="GET" action="{{ route('admin.classes.index') }}" class="adm-course-filter-form" style="padding:16px">
      <label class="adm-course-search">
        <x-admin.icon name="search"/>
        <input class="adm-input" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="نام گروه، دوره یا مدرس" aria-label="جستجوی گروه‌های آموزشی">
      </label>
      @unless($teacherView)
        <select class="adm-select" name="teacher_id" aria-label="مدرس">
          <option value="">همه مدرسان</option>
          @foreach($teachers as $teacher)
            <option value="{{ $teacher->id }}" @selected((string) ($filters['teacher_id'] ?? '') === (string) $teacher->id)>{{ $teacher->name }}</option>
          @endforeach
        </select>
      @endunless
      <select class="adm-select" name="status" aria-label="وضعیت گروه">
        <option value="">همه وضعیت‌ها</option>
        @foreach($statusOptions as $key => $label)
          <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
        @endforeach
      </select>
      <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">اعمال فیلتر</button>
      @if(collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty())
        <a href="{{ route('admin.classes.index') }}" class="adm-btn adm-btn-line adm-btn-sm">پاک‌کردن</a>
      @endif
    </form>
    <div class="adm-course-results" style="padding:0 16px 14px"><span>{{ number_format($classrooms->total()) }} گروه آموزشی</span><span>برای دیدن جلسه‌ها، برنامه گروه را باز کن.</span></div>
  </x-admin.panel>

  <div class="adm-grid adm-cols-3">
    @forelse($classrooms as $classroom)
      @php
        $schedule = $classroom->schedule ?? [];
        $selectedDays = data_get($schedule, 'days', []);
        $dayLabel = collect($selectedDays)->map(fn ($day) => $days[$day] ?? $day)->join(' و ');
        $teacherName = $classroom->teacher?->name ?? 'مدرس تعیین نشده';
        $courseName = $classroom->course?->title ?? 'کلاس مستقل';
        $studentIds = $classroom->students->pluck('id')->map(fn ($id) => (string) $id)->all();
        $fill = [
          'name' => $classroom->name,
          'course_id' => (string) ($classroom->course_id ?? ''),
          'teacher_id' => (string) ($classroom->teacher_id ?? ''),
          'meeting_url' => $classroom->meeting_url,
          'status' => $classroom->status,
          'schedule_days' => $selectedDays,
          'schedule_time' => data_get($schedule, 'time'),
          'duration_minutes' => data_get($schedule, 'duration_minutes', 90),
          'student_ids' => $studentIds,
        ];
      @endphp
      <article class="adm-card is-class" data-state="{{ $classroom->status }}" data-teacher="{{ $classroom->teacher_id }}" data-name="{{ $classroom->name.' '.$courseName.' '.$teacherName }}">
        <div class="adm-card-body">
          <div class="adm-card-title">
            <div><h3>{{ $classroom->name }}</h3><p>{{ $courseName }}</p></div>
            @if($classroom->status === 'active')<x-admin.badge tone="ok">فعال</x-admin.badge>
            @elseif($classroom->status === 'paused')<x-admin.badge tone="warn">متوقف</x-admin.badge>
            @else<x-admin.badge tone="mute">بایگانی</x-admin.badge>@endif
          </div>

          <div class="adm-kv">
            <span>مدرس: <b>{{ $teacherName }}</b></span>
            <span>{{ $dayLabel ?: 'بدون برنامه هفتگی' }} · <span class="adm-num">{{ data_get($schedule, 'time') ?: '—' }}</span></span>
          </div>
          <div class="adm-kv">
            <span>مدت هر جلسه: <b class="adm-num">{{ data_get($schedule, 'duration_minutes', 90) }} دقیقه</b></span>
            <span>جلسات ثبت‌شده: <b class="adm-num">{{ $classroom->sessions_count }}</b></span>
          </div>
          <div class="adm-card-foot">
            <div class="adm-faces">
              @foreach($classroom->students->take(4) as $student)
                <x-admin.avatar :name="$student->name"/>
              @endforeach
            </div>
            <x-admin.badge tone="info" class="is-plain"><span class="adm-num">{{ number_format($classroom->students_count) }}</span> عضو</x-admin.badge>
          </div>
          @if($classroom->skyroom_status === 'waiting_contract')
            <small class="adm-muted">اتاق آنلاین هنوز فعال نشده؛ زمان‌بندی جلسه‌ها در دسترس است</small>
          @elseif($classroom->skyroom_status === 'ready')
            <small class="adm-muted">اتاق آنلاین آماده است</small>
          @elseif($classroom->skyroom_status === 'failed')
            <small class="adm-muted">آماده‌سازی اتاق آنلاین ناموفق بود</small>
          @endif

          <div class="adm-actions">
            <x-admin.btn size="sm" icon="cal" :href="route('admin.classes.sessions.index', $classroom)">برنامه جلسات</x-admin.btn>
            @if($canManage)
              <x-admin.btn variant="soft" size="sm" icon="edit" data-modal-open="class-modal" data-title="ویرایش کلاس" :data-fill="json_encode($fill, JSON_UNESCAPED_UNICODE)" :data-action="route('admin.classes.update', $classroom)" data-method="PATCH">ویرایش</x-admin.btn>
              <x-admin.btn variant="bad" size="sm" icon="trash" data-modal-open="class-del" :data-name="$classroom->name" :data-action="route('admin.classes.destroy', $classroom)" aria-label="حذف"/>
            @endif
          </div>
          @if($canManage && !in_array($classroom->skyroom_status, ['waiting_contract', 'ready'], true))
            <form method="POST" action="{{ route('admin.classes.skyroom-room', $classroom) }}" style="margin-top:8px">
              @csrf
              <button type="submit" class="adm-btn adm-btn-line adm-btn-sm" style="width:100%">تلاش برای آماده‌سازی اتاق آنلاین</button>
            </form>
          @endif
        </div>
      </article>
    @empty
      <div class="adm-course-empty">
        <span><x-admin.icon name="cls"/></span>
        <h3>{{ collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty() ? 'گروهی با این مشخصات پیدا نشد' : 'هنوز گروه آموزشی نساخته‌ای' }}</h3>
        <p>{{ collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty() ? 'فیلترها را پاک کن یا عبارت دیگری جستجو کن.' : 'گروه آموزشی، مدرس و دانش‌آموزان را کنار هم قرار می‌دهد و برنامه جلسات را برایشان مشخص می‌کند.' }}</p>
        @if(collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty())
          <a class="adm-btn adm-btn-line" href="{{ route('admin.classes.index') }}">نمایش همه گروه‌ها</a>
        @elseif($canManage)
          <button type="button" class="adm-btn adm-btn-primary" data-modal-open="class-modal">ساخت اولین گروه</button>
        @endif
      </div>
    @endforelse
  </div>
  @if($classrooms->hasPages())
    <div class="adm-pagination">{{ $classrooms->links() }}</div>
  @endif
</div>
@endsection

@push('modals')
@if($canManage)
<x-admin.modal :demo="false" id="class-modal" title="گروه آموزشی جدید" size="lg" :action="route('admin.classes.store')">
  @include('admin.classes.partials.class-fields', [
    'teacherOptions' => $teacherOptions,
    'courseOptions' => $courseOptions,
    'days' => $days,
    'statusOptions' => $statusOptions,
    'studentOptions' => $studentOptions,
    'teacherView' => $teacherView,
  ])
</x-admin.modal>
<x-admin.confirm :demo="false" id="class-del" title="حذف کلاس" text="کلاسی که سابقه حضور، تکلیف، آزمون، عضو یا جلسه داشته باشد حذف نمی‌شود و باید بایگانی شود."/>
@endif
@endpush
