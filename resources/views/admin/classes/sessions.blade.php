@extends('admin.layouts.app')
@section('title', 'جلسات کلاس')

@section('content')
@php
  $statusLabels = ['scheduled' => 'برنامه‌ریزی‌شده', 'completed' => 'برگزارشده', 'cancelled' => 'لغوشده'];
  $statusTones = ['scheduled' => 'info', 'completed' => 'ok', 'cancelled' => 'warn'];
  $typeLabels = ['online' => 'آنلاین', 'onsite' => 'حضوری'];
@endphp

<x-admin.page-head tone="edu" icon="calendar" :title="'برنامه کلاس '.$classroom->name" :desc="($classroom->course?->title ?? 'کلاس مستقل').' · '.($classroom->teacher?->name ?? 'مدرس تعیین نشده')"
  :back-url="route('admin.classes.index')" back-label="کلاس‌ها">
  <x-admin.btn icon="plus" data-modal-open="session-modal">برنامه‌ریزی جلسه</x-admin.btn>
</x-admin.page-head>

@if($classroom->skyroom_status === 'waiting_contract')
  <x-admin.panel title="جلسه‌ها قابل برنامه‌ریزی هستند" desc="اتاق آنلاین هنوز فعال نشده؛ می‌توانی زمان جلسات را ثبت کنی. لینک ورود بعد از فعال‌شدن سرویس نمایش داده می‌شود." data-tone="shop"/>
@endif

<div class="adm-stats">
  <x-admin.stat tone="edu" icon="calendar" label="همه جلسه‌ها" :value="number_format($stats['total'])"/>
  <x-admin.stat tone="edu" icon="clock" label="جلسه‌های پیش‌رو" :value="number_format($stats['upcoming'])"/>
  <x-admin.stat tone="edu" icon="check" label="برگزارشده" :value="number_format($stats['completed'])"/>
  <x-admin.stat tone="edu" icon="users" label="دانش‌آموزان کلاس" :value="number_format($classroom->students()->count())"/>
</div>

<x-admin.panel title="تقویم کلاس" desc="هر روزی را انتخاب کن تا برنامه همان روز را ببینی." data-tone="edu">
  <div class="adm-attendance-calendar-head">
    <div class="adm-calendar-month">
      <a href="{{ route('admin.classes.sessions.index', ['classroom' => $classroom->id, 'date' => $calendar['previous_date']]) }}" aria-label="ماه قبل">‹</a>
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
            <strong>{{ $session->starts_at?->format('H:i') }}</strong>
            <small>{{ $session->ends_at?->format('H:i') ?? 'بدون پایان' }}</small>
          </div>
          <div class="adm-session-copy">
            <div class="adm-card-title">
              <div><h3>{{ $session->title }}</h3><p>{{ $typeLabels[$session->session_type] ?? $session->session_type }} · {{ number_format($session->attendances_count) }} نفر ثبت حضور</p></div>
              <x-admin.badge :tone="$statusTones[$session->status] ?? 'warn'">{{ $statusLabels[$session->status] ?? $session->status }}</x-admin.badge>
            </div>
            @if($session->session_type === 'online' && blank($session->meeting_url) && blank($classroom->meeting_url) && blank($classroom->skyroom_room_id))
              <small class="adm-muted">زمان جلسه ثبت شده؛ لینک ورود پس از آماده‌شدن اتاق نمایش داده می‌شود.</small>
            @endif
          </div>
          <div class="adm-actions">
            @canany(['attendance.manage.all', 'attendance.manage.assigned', 'attendance.view.assigned'])
              <x-admin.btn size="sm" icon="check" variant="soft" :href="route('admin.attendance.index', ['class_id' => $classroom->id, 'date' => $session->starts_at?->format('Y-m-d'), 'session' => $session->id])">ثبت حضور</x-admin.btn>
            @endcanany
            <x-admin.btn size="sm" variant="line" icon="edit" data-modal-open="session-modal" data-title="ویرایش جلسه" :data-fill="json_encode($fill, JSON_UNESCAPED_UNICODE)" :data-action="route('admin.classes.sessions.update', $session)" data-method="PATCH">ویرایش</x-admin.btn>
            <x-admin.btn size="sm" variant="bad" icon="trash" data-modal-open="session-delete" :data-name="$session->title" :data-action="route('admin.classes.sessions.destroy', $session)" aria-label="حذف جلسه"/>
          </div>
        </article>
      @endforeach
    </div>
  @else
    <div class="adm-queue-empty"><x-admin.icon name="calendar"/><b>برای این روز جلسه‌ای ثبت نشده</b><span>اگر قرار است کلاس برگزار شود، از همین‌جا زمان جلسه را مشخص کن.</span><x-admin.btn size="sm" icon="plus" data-modal-open="session-modal">برنامه‌ریزی جلسه</x-admin.btn></div>
  @endif
</x-admin.panel>

<details class="adm-history-panel">
  <summary><span><b>همه جلسه‌ها و سوابق</b><small>برای مرور یا ویرایش برنامه‌های قبلی</small></span><span class="adm-history-count">{{ number_format($sessions->total()) }} جلسه</span></summary>
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
              <td><x-admin.btn size="sm" variant="soft" icon="edit" data-modal-open="session-modal" data-title="ویرایش جلسه" :data-fill="json_encode($fill, JSON_UNESCAPED_UNICODE)" :data-action="route('admin.classes.sessions.update', $session)" data-method="PATCH">ویرایش</x-admin.btn></td>
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
<x-admin.modal :demo="false" id="session-modal" title="برنامه‌ریزی جلسه" size="lg" :action="route('admin.classes.sessions.store', $classroom)">
  <x-admin.legend title="زمان و موضوع"/>
  <x-admin.input label="عنوان جلسه" name="title" wide required/>
  <x-admin.select label="نوع برگزاری" name="session_type" :options="$typeLabels" :selected="'onsite'" required/>
  <x-admin.select label="وضعیت" name="status" :options="$statusLabels" :selected="'scheduled'" required/>
  <x-admin.input label="روز و ساعت شروع" name="starts_at" type="datetime-local" :value="$selectedDate->format('Y-m-d').'T09:00'" required/>
  <x-admin.input label="زمان پایان" name="ends_at" type="datetime-local"/>
  <x-admin.select label="ارائه‌دهنده کلاس آنلاین" name="meeting_provider" :options="['skyroom' => 'اتاق کلاس', 'jitsi' => 'سرویس ویدیویی دیگر', 'bbb' => 'سرویس ویدیویی دیگر (گزینه دوم)', 'link' => 'لینک دستی']"/>
  <x-admin.input label="لینک جلسه (در صورت نیاز)" name="meeting_url" placeholder="https://..." wide/>
  <x-admin.textarea label="یادداشت مدرس" name="teacher_notes" wide/>
  <p class="adm-muted" style="grid-column:1/-1">برای کلاس حضوری نیازی به لینک نیست. جلسه آنلاین را هم می‌توانی حتی قبل از آماده‌شدن اتاق ثبت کنی.</p>
</x-admin.modal>
<x-admin.confirm :demo="false" id="session-delete" title="حذف جلسه" text="جلسه‌ای که حضور یا سابقه ورود دارد حذف نمی‌شود؛ آن را لغو کن تا سوابق حفظ شود."/>
@endpush
