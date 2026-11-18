@extends('admin.layouts.app')
@section('title', 'جلسات کلاس')

@section('content')
@php
  $statusLabels = ['scheduled' => 'برنامه‌ریزی‌شده', 'completed' => 'برگزارشده', 'cancelled' => 'لغوشده'];
  $statusTones = ['scheduled' => 'info', 'completed' => 'ok', 'cancelled' => 'warn'];
  $typeLabels = ['online' => 'آنلاین', 'onsite' => 'حضوری'];
  $stats = [
    'total' => $sessions->total(),
    'upcoming' => $sessions->getCollection()->where('status', 'scheduled')->where('starts_at', '>=', now())->count(),
    'completed' => $sessions->getCollection()->where('status', 'completed')->count(),
  ];
@endphp

<x-admin.page-head tone="edu" icon="calendar" :title="'جلسات '.$classroom->name" :desc="($classroom->course?->title ?? 'کلاس مستقل').' · '.($classroom->teacher?->name ?? 'مدرس تعیین نشده')"
  :back-url="route('admin.classes.index')" back-label="کلاس‌ها">
  <x-admin.btn icon="plus" data-modal-open="session-modal">ثبت جلسه جدید</x-admin.btn>
</x-admin.page-head>

@if($classroom->skyroom_status === 'waiting_contract')
  <x-admin.panel title="اتصال Skyroom هنوز فعال نشده" desc="برنامه‌ریزی و ثبت جلسه‌ها در شیخان فعال است. لینک ورود پس از دریافت قرارداد و تأیید اتصال سرویس در دسترس قرار می‌گیرد." data-tone="shop"/>
@endif

<div class="adm-stats">
  <x-admin.stat tone="edu" icon="calendar" label="کل جلسات" :value="number_format($stats['total'])"/>
  <x-admin.stat tone="edu" icon="clock" label="جلسات برنامه‌ریزی‌شده" :value="number_format($stats['upcoming'])"/>
  <x-admin.stat tone="edu" icon="check" label="جلسات برگزارشده" :value="number_format($stats['completed'])"/>
  <x-admin.stat tone="edu" icon="users" label="دانش‌آموزان کلاس" :value="number_format($classroom->students()->count())"/>
</div>

<x-admin.panel flush data-filter data-tone="edu">
  <x-admin.toolbar placeholder="جستجوی عنوان جلسه…">
    <x-admin.filter-select key="type" placeholder="همه انواع" :options="$typeLabels"/>
    <div class="adm-toolbar-end"><x-admin.seg :options="['scheduled' => 'برنامه‌ریزی‌شده', 'completed' => 'برگزارشده', 'cancelled' => 'لغوشده']"/></div>
  </x-admin.toolbar>
  <div class="adm-table-wrap">
    <table class="adm-table">
      <thead><tr><th>جلسه</th><th>نوع</th><th>زمان شروع</th><th>زمان پایان</th><th>حضور</th><th>وضعیت</th><th></th></tr></thead>
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
            <td><b>{{ $session->title }}</b><small>{{ ['skyroom' => 'ارائه‌دهنده: Skyroom', 'jitsi' => 'ارائه‌دهنده: Jitsi Meet', 'bbb' => 'ارائه‌دهنده: BigBlueButton', 'link' => 'لینک اختصاصی'][$session->meeting_provider] ?? 'جلسه داخلی' }}</small></td>
            <td>{{ $typeLabels[$session->session_type] ?? $session->session_type }}</td>
            <td class="adm-num is-nowrap">{{ $session->starts_at?->format('Y/m/d H:i') }}</td>
            <td class="adm-num is-nowrap">{{ $session->ends_at?->format('Y/m/d H:i') ?? '—' }}</td>
            <td class="adm-num">{{ number_format($session->attendances_count) }}</td>
            <td><x-admin.badge :tone="$statusTones[$session->status] ?? 'warn'">{{ $statusLabels[$session->status] ?? $session->status }}</x-admin.badge></td>
            <td>
              <div class="adm-actions">
                <x-admin.btn size="sm" variant="soft" icon="edit" data-modal-open="session-modal" data-title="ویرایش جلسه" :data-fill="json_encode($fill, JSON_UNESCAPED_UNICODE)" :data-action="route('admin.classes.sessions.update', $session)" data-method="PATCH">ویرایش</x-admin.btn>
                <x-admin.btn size="sm" variant="bad" icon="trash" data-modal-open="session-delete" :data-name="$session->title" :data-action="route('admin.classes.sessions.destroy', $session)" aria-label="حذف جلسه"/>
              </div>
              @if($session->session_type === 'online' && blank($session->meeting_url) && blank($classroom->meeting_url) && blank($classroom->skyroom_room_id))
                <small class="adm-muted">زمان‌بندی ذخیره شده؛ لینک ورود پس از فعال‌شدن Skyroom ثبت می‌شود.</small>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="7">هنوز جلسه‌ای برای این کلاس ثبت نشده است.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <x-slot:footer>{{ $sessions->links() }}</x-slot:footer>
</x-admin.panel>
@endsection

@push('modals')
<x-admin.modal :demo="false" id="session-modal" title="ثبت جلسه جدید" size="lg" :action="route('admin.classes.sessions.store', $classroom)">
  <x-admin.legend title="اطلاعات جلسه"/>
  <x-admin.input label="عنوان جلسه" name="title" wide required/>
  <x-admin.select label="نوع جلسه" name="session_type" :options="$typeLabels" required/>
  <x-admin.select label="وضعیت" name="status" :options="$statusLabels" required/>
  <x-admin.input label="زمان شروع" name="starts_at" type="datetime-local" required/>
  <x-admin.input label="زمان پایان" name="ends_at" type="datetime-local"/>
  <x-admin.select label="ارائه‌دهنده کلاس آنلاین" name="meeting_provider" :options="['skyroom' => 'Skyroom', 'jitsi' => 'Jitsi Meet', 'bbb' => 'BigBlueButton', 'link' => 'لینک دستی']"/>
  <x-admin.input label="لینک جلسه (برای Jitsi یا لینک دستی)" name="meeting_url" placeholder="https://meet.jit.si/..." wide/>
  <x-admin.textarea label="یادداشت مدرس" name="teacher_notes" wide/>
  <p class="adm-muted" style="grid-column:1/-1">برای جلسه آنلاین، ثبت زمان‌بندی به لینک خارجی وابسته نیست. برای Jitsi یا لینک دستی، لینک جلسه را وارد کنید. برای Skyroom، ورود پس از آماده‌شدن اتصال سرویس فعال می‌شود.</p>
</x-admin.modal>
<x-admin.confirm :demo="false" id="session-delete" title="حذف جلسه" text="جلسه دارای سابقه حضور یا کلیک ورود حذف نمی‌شود؛ آن را لغو کنید تا سوابق حفظ شود."/>
@endpush
