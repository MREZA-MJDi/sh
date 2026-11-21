@extends('admin.layouts.app')
@section('title', 'کلاس‌های آنلاین')
@section('content')
@php $statusLabels = ['scheduled' => ['info', 'برنامه‌ریزی‌شده'], 'completed' => ['ok', 'برگزارشده'], 'cancelled' => ['warn', 'لغوشده']]; @endphp
<x-admin.page-head tone="edu" icon="live" title="کلاس‌های آنلاین" desc="نمای یکپارچه تمام جلسه‌های آنلاین؛ مدیریت هر جلسه در بستر کلاس اصلی آن انجام می‌شود">
  <x-admin.btn variant="line" icon="cls" :href="route('admin.classes.index')">مدیریت کلاس‌ها</x-admin.btn>
</x-admin.page-head>
<div class="adm-stats">
  <x-admin.stat tone="edu" icon="live" label="جلسات امروز" :value="number_format($stats['today'])"/>
  <x-admin.stat tone="edu" icon="cal" label="جلسات این هفته" :value="number_format($stats['week'])"/>
  <x-admin.stat tone="edu" icon="clock" label="جلسات پیش‌رو" :value="number_format($stats['upcoming'])"/>
  <x-admin.stat tone="edu" icon="lock" label="کلاس‌های در انتظار اتصال" :value="number_format($stats['waiting'])" hint="زمان‌بندی داخلی فعال است"/>
</div>
<x-admin.panel flush data-tone="edu">
  <form method="GET" class="adm-toolbar">
    <input class="adm-input" type="search" name="q" value="{{ request('q') }}" placeholder="جستجوی عنوان جلسه یا کلاس…">
    <select class="adm-select" name="status"><option value="">همه وضعیت‌ها</option>@foreach($statusLabels as $key => $label)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $label[1] }}</option>@endforeach</select>
    <button class="adm-btn adm-btn-line adm-btn-sm" type="submit">اعمال فیلتر</button>
  </form>
  <div class="adm-table-wrap"><table class="adm-table">
    <thead><tr><th>جلسه</th><th>کلاس / دوره</th><th>مدرس</th><th>زمان</th><th>حضور</th><th>اتصال</th><th>وضعیت</th><th></th></tr></thead>
    <tbody>
      @forelse($sessions as $session)
        @php $classroom = $session->classroom; $status = $statusLabels[$session->status] ?? ['mute', $session->status]; $meetingLink = $session->meeting_url ?: $classroom?->meeting_url; @endphp
        <tr>
          <td><b>{{ $session->title }}</b><small>#{{ $session->id }}</small></td>
          <td>{{ $classroom?->name ?? 'کلاس حذف‌شده' }}<small>{{ $classroom?->course?->title ?? 'بدون دوره' }}</small></td>
          <td>{{ $classroom?->teacher?->name ?? '—' }}</td>
          <td class="adm-num is-nowrap">{{ ($session->starts_at ? \App\Support\PersianDate::format($session->starts_at, 'Y/m/d H:i') : '—') }}<small>{{ ($session->ends_at ? \App\Support\PersianDate::format($session->ends_at, 'H:i') : '—') }}</small></td>
          <td class="adm-num">{{ number_format($session->attendances_count) }}</td>
          <td>@if(filled($meetingLink))<a href="{{ $meetingLink }}" target="_blank" rel="noopener noreferrer">لینک ثبت‌شده</a>@else<x-admin.badge tone="warn">در انتظار فعال‌سازی</x-admin.badge><small>اتصال Skyroom هنوز آماده نیست</small>@endif</td>
          <td><x-admin.badge :tone="$status[0]">{{ $status[1] }}</x-admin.badge></td>
          <td>@if($classroom)<x-admin.btn size="sm" variant="soft" icon="calendar" :href="route('admin.classes.sessions.index', $classroom)">مدیریت جلسه</x-admin.btn>@endif</td>
        </tr>
      @empty
        <tr><td colspan="8">هنوز جلسه آنلاین ثبت نشده است. ابتدا کلاس بسازید و از صفحه جلسات آن، جلسه آنلاین را زمان‌بندی کنید.</td></tr>
      @endforelse
    </tbody>
  </table></div>
  <x-slot:footer>{{ $sessions->links() }}</x-slot:footer>
</x-admin.panel>
@endsection
