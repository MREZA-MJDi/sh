@extends('admin.layouts.app')
@section('title', 'جلسات کلاس')

@section('content')
@php
  $statusLabels = ['scheduled' => 'برنامه‌ریزی‌شده', 'completed' => 'برگزارشده', 'cancelled' => 'لغوشده'];
  $statusTones = ['scheduled' => 'info', 'completed' => 'ok', 'cancelled' => 'warn'];
  $typeLabels = ['online' => 'آنلاین', 'onsite' => 'حضوری'];
@endphp

<x-admin.page-head tone="edu" icon="cal" :title="'برنامه کلاس '.$classroom->name" :desc="($classroom->course?->title ?? 'کلاس مستقل').' · '.($classroom->teacher?->name ?? 'مدرس تعیین نشده')"
   :back-url="$teacherView ? route('admin.classes.index') : ($classroom->course ? route('admin.courses.edit', $classroom->course) : route('admin.classes.index'))" :back-label="$teacherView ? 'گروه‌های آموزشی' : ($classroom->course ? 'بازگشت به دوره' : 'گروه‌های آموزشی')">
  @if($canManage)
    <x-admin.btn icon="plus" data-modal-open="session-modal">برنامه‌ریزی جلسه</x-admin.btn>
  @endif
</x-admin.page-head>

@if($classroom->skyroom_status === 'waiting_contract')
  <x-admin.panel title="برنامه‌ریزی فعال است؛ اتاق آنلاین هنوز آماده نیست" desc="می‌توانی جلسه‌ها را از همین‌جا زمان‌بندی کنی. لینک ورود پس از فعال‌شدن سرویس نمایش داده می‌شود؛ لینک دستی کلاس هم قابل استفاده است." data-tone="shop"/>
@endif

@php $nextMeetingLink = $nextSession?->meeting_url ?: $classroom->meeting_url; @endphp
<section class="adm-session-focus" data-tone="edu">
  <div class="adm-session-focus-copy">
    <span class="adm-session-focus-kicker"><x-admin.icon name="clock"/> نوبت بعدی کلاس</span>
    @if($nextSession)
      <h2>{{ $nextSession->title }}</h2>
      <p>{{ \App\Support\PersianDate::format($nextSession->starts_at, 'l، d F · H:i') }} · {{ $nextSession->ends_at ? \App\Support\PersianDate::format($nextSession->ends_at, 'H:i') : 'پایان نامشخص' }}</p>
      <div class="adm-actions">
        @if($nextMeetingLink && $nextSession->session_type === 'online')
          <a class="adm-btn adm-btn-primary" href="{{ $nextMeetingLink }}" target="_blank" rel="noopener noreferrer"><x-admin.icon name="live"/>ورود به اتاق جلسه</a>
        @elseif($nextSession->session_type === 'online')
          <span class="adm-session-no-link">لینک ورود هنوز آماده نیست</span>
        @endif
        @canany(['attendance.manage.all', 'attendance.manage.assigned', 'attendance.view.assigned'])
          <a class="adm-btn adm-btn-line" href="{{ route('admin.attendance.index', ['class_id' => $classroom->id, 'date' => $nextSession->starts_at?->format('Y-m-d'), 'session' => $nextSession->id]) }}">آماده‌سازی حضور و غیاب</a>
        @endcanany
      </div>
    @else
      <h2>جلسه بعدی هنوز برنامه‌ریزی نشده</h2>
      <p>{{ $canManage ? 'زمان جلسه را ثبت کن تا در برنامه امروز و داشبورد دیده شود.' : 'برای زمان‌بندی جلسه جدید، با مدیر آکادمی هماهنگ کن.' }}</p>
      @if($canManage)<x-admin.btn size="sm" icon="plus" data-modal-open="session-modal">برنامه‌ریزی اولین جلسه</x-admin.btn>@endif
    @endif
  </div>
  <div class="adm-session-focus-side">
    <span>وضعیت کلاس</span>
    <b>{{ $classroom->status === 'active' ? 'در حال برگزاری' : 'متوقف' }}</b>
    <small>{{ number_format($classroom->students_count) }} دانش‌آموز عضو این کلاس</small>
  </div>
</section>

<div class="adm-stats">
  <x-admin.stat tone="edu" icon="cal" label="همه جلسه‌ها" :value="number_format($stats['total'])"/>
  <x-admin.stat tone="edu" icon="clock" label="جلسه‌های پیش‌رو" :value="number_format($stats['upcoming'])"/>
  <x-admin.stat tone="edu" icon="check" label="برگزارشده" :value="number_format($stats['completed'])"/>
  <x-admin.stat tone="edu" icon="users" label="دانش‌آموزان کلاس" :value="number_format($classroom->students_count)"/>
</div>

<x-admin.panel title="تقویم کلاس" desc="هر روزی را انتخاب کن تا برنامه همان روز را ببینی." data-tone="edu">
  <div class="adm-attendance-calendar-head">
    <div class="adm-calendar-month">
      <a href="{{ route('admin.classes.sessions.index', ['classroom' => $classroom->id, 'date' => $calendar['previous_date']]) }}" aria-label="ماه قبل">‹</a>
      <a class="adm-calendar-today" href="{{ route('admin.classes.sessions.index', ['classroom' => $classroom?->id, 'date' => now()->format('Y-m-d')]) }}">امروز</a>
      <strong>{{ $calendar['label'] }}</strong>
      <a href="{{ route('admin.classes.sessions.index', ['classroom' => $classroom->id, 'date' => $calendar['next_date']]) }}" aria-label="ماه بعد">›</a>
    </div>
    <span class="adm-muted">برای دیدن برنامه، روی یک روز بزن</span>
  </div>
  <div class="adm-calendar-grid" role="group" aria-label="روزهای ماه">
    @foreach($calendar['weekdays'] as $weekday)
      <span class="adm-calendar-weekday">{{ $weekday }}</span>
    @endforeach
    @for($blank = 0; $blank < $calendar['leading_blanks']; $blank++)
      <span class="adm-calendar-blank" aria-hidden="true"></span>
    @endfor
    @foreach($calendar['days'] as $day)
      <a class="adm-calendar-day {{ $day['selected'] ? 'is-selected' : '' }} {{ $day['today'] ? 'is-today' : '' }} {{ $day['sessions'] ? 'has-sessions' : '' }}"
         href="{{ route('admin.classes.sessions.index', ['classroom' => $classroom->id, 'date' => $day['date']]) }}"
         @if($day['selected']) aria-current="date" @endif>
        <b>{{ $day['day'] }}</b>
        @if($day['sessions'])
          <small>{{ $day['sessions'] }} جلسه</small>
          <span class="adm-calendar-dots" aria-hidden="true">
            @if($day['completed'])<i data-state="completed"></i>@endif
            @if($day['scheduled'])<i data-state="scheduled"></i>@endif
          </span>
        @else
          <small class="is-empty">—</small>
        @endif
      </a>
    @endforeach
  </div>
  <div class="adm-calendar-legend">
    <span><i data-state="completed"></i> برگزارشده</span>
    <span><i data-state="scheduled"></i> برنامه‌ریزی‌شده</span>
    <span><i data-state="none"></i> روز خالی</span>
  </div>
</x-admin.panel>

<x-admin.panel :title="'برنامه '.\App\Support\PersianDate::format($selectedDate, 'l، d F')" :desc="$daySessions->count().' جلسه در این روز'" data-tone="track">
  @if($daySessions->isNotEmpty())
    <div class="adm-session-agenda">
      @foreach($daySessions as $session)
        @php
          $fill = [
            'title' => $session->title,
            'session_type' => $session->session_type,
            'starts_at' => $session->starts_at?->format('Y-m-d\TH:i'),
            'ends_at' => $session->ends_at?->format('Y-m-d\TH:i'),
            'meeting_url' => $session->meeting_url,
            'meeting_provider' => $session->meeting_provider,
            'status' => $session->status,
            'teacher_notes' => $session->teacher_notes,
          ];
        @endphp
        <article class="adm-session-card" data-state="{{ $session->status }}">
          <div class="adm-session-time">
            <strong>{{ \App\Support\PersianDate::format($session->starts_at, 'H:i') }}</strong>
            <small>{{ ($session->ends_at ? \App\Support\PersianDate::format($session->ends_at, 'H:i') : 'بدون پایان') }}</small>
          </div>
          <div class="adm-session-copy">
            <div class="adm-card-title">
              <div><h3>{{ $session->title }}</h3><p>{{ $typeLabels[$session->session_type] ?? $session->session_type }} · {{ number_format($session->attendances_count) }} نفر ثبت حضور</p></div>
              <x-admin.badge :tone="$statusTones[$session->status] ?? 'warn'">{{ $statusLabels[$session->status] ?? $session->status }}</x-admin.badge>
            </div>
            @if($session->session_type === 'online')
              @if($session->meeting_url || $classroom->meeting_url)
                <small class="adm-session-link-ready"><x-admin.icon name="check"/> لینک ورود آماده است</small>
              @else
                <small class="adm-muted">زمان جلسه ثبت شده؛ لینک ورود پس از آماده‌شدن اتاق نمایش داده می‌شود.</small>
              @endif
            @endif
          </div>
          <div class="adm-actions">
            @if($session->session_type === 'online' && ($session->meeting_url || $classroom->meeting_url))
              <a class="adm-btn adm-btn-primary adm-btn-sm" href="{{ $session->meeting_url ?: $classroom->meeting_url }}" target="_blank" rel="noopener noreferrer"><x-admin.icon name="live"/>ورود به جلسه</a>
            @endif
            @canany(['attendance.manage.all', 'attendance.manage.assigned', 'attendance.view.assigned'])
              <x-admin.btn size="sm" icon="check" variant="soft" :href="route('admin.attendance.index', ['class_id' => $classroom->id, 'date' => $session->starts_at?->format('Y-m-d'), 'session' => $session->id])">ثبت حضور</x-admin.btn>
            @endcanany
            @if($canManage)
              <x-admin.btn size="sm" variant="line" icon="edit" data-modal-open="session-modal" data-title="ویرایش جلسه" :data-fill="json_encode($fill, JSON_UNESCAPED_UNICODE)" :data-action="route('admin.classes.sessions.update', $session)" data-method="PATCH">ویرایش</x-admin.btn>
              <x-admin.btn size="sm" variant="bad" icon="trash" data-modal-open="session-delete" :data-name="$session->title" :data-action="route('admin.classes.sessions.destroy', $session)" aria-label="حذف جلسه"/>
            @endif
          </div>
        </article>
      @endforeach
    </div>
  @else
    <div class="adm-queue-empty"><x-admin.icon name="cal"/><b>برای این روز جلسه‌ای ثبت نشده</b><span>{{ $canManage ? 'برای برگزاری کلاس، زمان جلسه را از همین‌جا ثبت کن.' : 'برای ثبت جلسه جدید، با مدیر آکادمی هماهنگ کن.' }}</span>@if($canManage)<x-admin.btn size="sm" icon="plus" data-modal-open="session-modal">برنامه‌ریزی جلسه</x-admin.btn>@endif</div>
  @endif
</x-admin.panel>

<details class="adm-history-panel">
  <summary><span><b>همه جلسه‌ها و سوابق</b><small>{{ $canManage ? "برای مرور یا ویرایش برنامه‌های قبلی" : "برای مرور جلسه‌های قبلی" }}</small></span><span class="adm-history-count">{{ number_format($sessions->total()) }} جلسه</span></summary>
  <x-admin.panel flush data-filter data-tone="edu">
    <x-admin.toolbar placeholder="جستجوی عنوان جلسه…">
      <x-admin.filter-select key="type" placeholder="همه انواع" :options="$typeLabels"/>
      <div class="adm-toolbar-end"><x-admin.seg :options="['scheduled' => 'برنامه‌ریزی‌شده', 'completed' => 'برگزارشده', 'cancelled' => 'لغوشده']"/></div>
    </x-admin.toolbar>
    <div class="adm-table-wrap">
      <table class="adm-table">
        <thead><tr><th>جلسه</th><th>نوع</th><th>زمان</th><th>حضور</th><th>وضعیت</th><th></th></tr></thead>
        <tbody data-filter-list>
          @forelse($sessions as $session)
            @php
              $fill = [
                'title' => $session->title,
                'session_type' => $session->session_type,
                'starts_at' => $session->starts_at?->format('Y-m-d\TH:i'),
                'ends_at' => $session->ends_at?->format('Y-m-d\TH:i'),
                'meeting_url' => $session->meeting_url,
                'meeting_provider' => $session->meeting_provider,
                'status' => $session->status,
                'teacher_notes' => $session->teacher_notes,
              ];
            @endphp
            <tr data-name="{{ $session->title }}" data-state="{{ $session->status }}" data-type="{{ $session->session_type }}">
              <td><b>{{ $session->title }}</b></td>
              <td>{{ $typeLabels[$session->session_type] ?? $session->session_type }}</td>
              <td class="adm-num is-nowrap">{{ \App\Support\PersianDate::format($session->starts_at) }}</td>
              <td class="adm-num">{{ number_format($session->attendances_count) }}</td>
              <td><x-admin.badge :tone="$statusTones[$session->status] ?? 'warn'">{{ $statusLabels[$session->status] ?? $session->status }}</x-admin.badge></td>
              <td><div class="adm-actions">
                @if($canManage)
                  <x-admin.btn size="sm" variant="soft" icon="edit" data-modal-open="session-modal" data-title="ویرایش جلسه" :data-fill="json_encode($fill, JSON_UNESCAPED_UNICODE)" :data-action="route('admin.classes.sessions.update', $session)" data-method="PATCH">ویرایش</x-admin.btn>
                  <x-admin.btn size="sm" variant="bad" icon="trash" data-modal-open="session-delete" :data-name="$session->title" :data-action="route('admin.classes.sessions.destroy', $session)" aria-label="حذف جلسه"/>
                @endif
              </div></td>
            </tr>
          @empty
            <tr><td colspan="6">هنوز جلسه‌ای برای این کلاس ثبت نشده است.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <x-slot:footer>{{ $sessions->links() }}</x-slot:footer>
  </x-admin.panel>
</details>
@endsection

@push('modals')
@if($canManage)
  <x-admin.modal :demo="false" id="session-modal" title="برنامه‌ریزی جلسه" size="lg" :action="route('admin.classes.sessions.store', $classroom)">
    @include('admin.classes.partials.session-fields', ['sessionDefaultDate' => $selectedDate->setTimeFromTimeString(data_get($classroom->schedule, 'time') ?: '09:00')->format('Y-m-d\\TH:i')])
  </x-admin.modal>
  <x-admin.confirm :demo="false" id="session-delete" title="حذف جلسه" text="جلسه‌ای که حضور یا سابقه ورود دارد حذف نمی‌شود؛ آن را لغو کن تا سوابق حفظ شود."/>
@endif
@endpush
