@extends('admin.layouts.app')
@section('title', 'کلاس‌ها')

@section('content')
@php
  $teacherOptions = $teachers->pluck('name', 'id')->all();
  $courseOptions = $courses->pluck('title', 'id')->all();
  $studentOptions = $students->pluck('name', 'id')->all();
  $days = ['sat' => 'شنبه', 'sun' => 'یکشنبه', 'mon' => 'دوشنبه', 'tue' => 'سه‌شنبه', 'wed' => 'چهارشنبه', 'thu' => 'پنجشنبه', 'fri' => 'جمعه'];
  $statusOptions = ['active' => 'فعال', 'paused' => 'متوقف', 'archived' => 'بایگانی'];
@endphp

<x-admin.page-head tone="edu" icon="cls" title="کلاس‌ها" desc="هر کلاس یک گروه آموزشی از یک دوره است؛ مدرس، اعضا و جلسات خودش را دارد">
  @if($canManage)
    <x-admin.btn icon="plus" data-modal-open="class-modal">کلاس جدید</x-admin.btn>
  @endif
</x-admin.page-head>

<div class="adm-stats">
  <x-admin.stat tone="edu" icon="cls" label="کل گروه‌ها" :value="number_format($stats['total'])"/>
  <x-admin.stat tone="edu" icon="check" label="گروه‌های فعال" :value="number_format($stats['active'])"/>
  <x-admin.stat tone="edu" icon="users" label="دانش‌آموزان یکتا" :value="number_format($stats['students'])" hint="عضو گروه‌های قابل مشاهده"/>
  <x-admin.stat tone="edu" icon="teacher" label="بدون مدرس" :value="number_format($stats['without_teacher'])"/>
</div>

<div data-filter class="adm-stack">
  <x-admin.panel flush>
    <x-admin.toolbar placeholder="جستجوی کلاس، دوره یا مدرس…">
      <x-admin.filter-select key="teacher" placeholder="همه مدرسان" :options="$teacherOptions"/>
      <div class="adm-toolbar-end"><x-admin.seg :options="['active' => 'فعال', 'paused' => 'متوقف', 'archived' => 'بایگانی']"/></div>
    </x-admin.toolbar>
  </x-admin.panel>

  <div class="adm-grid adm-cols-3" data-filter-list>
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
            <small class="adm-muted">Skyroom: در انتظار قرارداد · زمان‌بندی جلسات فعال است</small>
          @elseif($classroom->skyroom_status === 'ready')
            <small class="adm-muted">Skyroom: اتاق آماده</small>
          @elseif($classroom->skyroom_status === 'failed')
            <small class="adm-muted">Skyroom: خطا در آخرین تلاش اتصال</small>
          @endif

          <div class="adm-actions">
            <x-admin.btn size="sm" icon="calendar" :href="route('admin.classes.sessions.index', $classroom)">جلسات</x-admin.btn>
            @if($canManage)
              <x-admin.btn variant="soft" size="sm" icon="edit" data-modal-open="class-modal" data-title="ویرایش کلاس" :data-fill="json_encode($fill, JSON_UNESCAPED_UNICODE)" :data-action="route('admin.classes.update', $classroom)" data-method="PATCH">ویرایش</x-admin.btn>
              <x-admin.btn variant="bad" size="sm" icon="trash" data-modal-open="class-del" :data-name="$classroom->name" :data-action="route('admin.classes.destroy', $classroom)" aria-label="حذف"/>
            @endif
          </div>
          @if($canManage)
            <form method="POST" action="{{ route('admin.classes.skyroom-room', $classroom) }}" style="margin-top:8px">
              @csrf
              <button type="submit" class="adm-btn adm-btn-line adm-btn-sm" style="width:100%">بررسی / ساخت اتاق Skyroom</button>
            </form>
          @endif
        </div>
      </article>
    @empty
      <x-admin.empty text="برای این محدوده دسترسی هنوز کلاسی ثبت نشده است."/>
    @endforelse
  </div>
  @if($classrooms->hasPages())
    <div class="adm-pagination">{{ $classrooms->links() }}</div>
  @endif
</div>
@endsection

@push('modals')
@if($canManage)
<x-admin.modal :demo="false" id="class-modal" title="کلاس جدید" size="lg" :action="route('admin.classes.store')">
  <x-admin.legend title="اطلاعات گروه آموزشی"/>
  <x-admin.input label="نام کلاس / گروه" name="name" wide required/>
  <x-admin.select label="دوره مرتبط" name="course_id" :options="$courseOptions" :required="$teacherView" placeholder="کلاس مستقل / بدون دوره"/>
  @unless($teacherView)
    <x-admin.select label="مدرس مسئول" name="teacher_id" :options="$teacherOptions" required/>
  @endunless
  <x-admin.select label="وضعیت کلاس" name="status" :options="$statusOptions" required/>
  <x-admin.input label="لینک پیش‌فرض جلسه (اختیاری)" name="meeting_url" placeholder="لینک امن جلسه یا اتاق Skyroom" wide/>

  <x-admin.legend title="برنامه هفتگی"/>
  <x-admin.chips label="روزهای برگزاری" name="schedule_days" :options="$days" hint="برای کلاس‌های بدون برنامه ثابت خالی بگذارید"/>
  <x-admin.input label="ساعت شروع معمول" name="schedule_time" type="time"/>
  <x-admin.input label="مدت هر جلسه" name="duration_minutes" type="number" min="15" max="300" addon="دقیقه"/>

  <x-admin.legend title="اعضای کلاس"/>
  <x-admin.chips label="دانش‌آموزان فعال" name="student_ids" :options="$studentOptions" scroll hint="افزودن یا حذف عضو، تاریخچه عضویت را حفظ می‌کند"/>
  <p class="adm-muted" style="grid-column:1/-1">ثبت کلاس و اعضا مستقل از فعال‌شدن Skyroom است. جلسات را پس از ساخت گروه از صفحه «جلسات» زمان‌بندی کنید.</p>
</x-admin.modal>
<x-admin.confirm :demo="false" id="class-del" title="حذف کلاس" text="کلاسی که سابقه حضور، تکلیف، آزمون، عضو یا جلسه داشته باشد حذف نمی‌شود و باید بایگانی شود."/>
@endif
@endpush
