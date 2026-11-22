@extends('admin.layouts.app')
@section('title', 'مشاوره‌ها')

@section('content')
@include('admin.partials.communication-center-link')
@php
  $statusLabels = ['pending' => 'در انتظار بررسی', 'scheduled' => 'زمان‌بندی‌شده', 'done' => 'انجام‌شده', 'cancelled' => 'لغوشده', 'approved' => 'تأییدشده', 'completed' => 'تکمیل‌شده'];
  $statusTones = ['pending' => 'warn', 'scheduled' => 'info', 'done' => 'ok', 'cancelled' => 'mute', 'approved' => 'ok', 'completed' => 'ok'];
  $statusOptions = $statusLabels;
@endphp

<x-admin.page-head tone="comm" icon="chat" title="مشاوره‌ها" desc="درخواست‌های واقعی اولیا و مدرسان؛ ثبت نوبت و پیگیری وضعیت">
</x-admin.page-head>

<div class="adm-stats">
  <x-admin.stat tone="comm" icon="chat" label="کل درخواست‌ها" :value="number_format($counts['total'])"/>
  <x-admin.stat tone="comm" icon="clock" label="در انتظار بررسی" :value="number_format($counts['pending'])"/>
  <x-admin.stat tone="comm" icon="calendar" label="زمان‌بندی‌شده" :value="number_format($counts['scheduled'])"/>
  <x-admin.stat tone="comm" icon="check" label="انجام‌شده" :value="number_format($counts['done'])"/>
</div>

<x-admin.panel flush data-tone="comm">
  <form method="GET" action="{{ route('admin.consultations.index') }}" class="adm-toolbar">
    <label class="adm-label">جستجو <input class="adm-input" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="نام درخواست‌کننده، دانش‌آموز یا موضوع…"></label>
    <label class="adm-label">وضعیت
      <select class="adm-select" name="status"><option value="">همه وضعیت‌ها</option><option value="pending" @selected(($filters['status'] ?? '') === 'pending')>در انتظار بررسی</option><option value="scheduled" @selected(($filters['status'] ?? '') === 'scheduled')>زمان‌بندی‌شده</option><option value="done" @selected(($filters['status'] ?? '') === 'done')>انجام‌شده</option><option value="cancelled" @selected(($filters['status'] ?? '') === 'cancelled')>لغوشده</option></select>
    </label>
    <div class="adm-toolbar-end"><x-admin.btn type="submit" size="sm">اعمال فیلتر</x-admin.btn><x-admin.btn size="sm" variant="line" :href="route('admin.consultations.index')">پاک‌کردن</x-admin.btn></div>
  </form>
  <div class="adm-table-wrap"><table class="adm-table">
    <thead><tr><th>درخواست‌کننده</th><th>دانش‌آموز / مدرس</th><th>موضوع و نوع</th><th>زمان پیشنهادی</th><th>وضعیت</th><th>ثبت نوبت / یادداشت</th></tr></thead>
    <tbody>
      @forelse($consultations as $consultation)
        <tr id="consultation-{{ $consultation->id }}" data-name="{{ ($consultation->requester?->name ?? '').' '.($consultation->student?->name ?? '').' '.$consultation->topic }}" data-state="{{ $consultation->status }}">
          <td><b>{{ $consultation->requester?->name ?? 'حساب حذف‌شده' }}</b><small>@if($consultation->contact_phone || $consultation->requester?->phone) @phone($consultation->contact_phone ?: $consultation->requester?->phone) @else بدون شماره تماس @endif</small></td>
          <td>{{ $consultation->student?->name ?? '—' }}<small>{{ $consultation->teacher?->name ?? 'مدرس تعیین نشده' }}</small></td>
          <td>{{ $consultation->topic }}<small>{{ $consultation->type }}</small></td>
          <td>{{ ($consultation->desired_at ? \App\Support\PersianDate::format($consultation->desired_at, 'Y/m/d H:i') : 'زمانی ثبت نشده') }}</td>
          <td><x-admin.badge :tone="$statusTones[$consultation->status] ?? 'warn'">{{ $statusLabels[$consultation->status] ?? $consultation->status }}</x-admin.badge></td>
          <td>
            <form method="POST" action="{{ route('admin.consultations.update', $consultation) }}" class="adm-stack">
              @csrf
              @method('PATCH')
              <select name="status" class="adm-select" required>
                @foreach($statusOptions as $key => $label)
                  <option value="{{ $key }}" @selected($consultation->status === $key)>{{ $label }}</option>
                @endforeach
              </select>
              <input type="datetime-local" name="scheduled_at" class="adm-input" value="{{ $consultation->scheduled_at?->format('Y-m-d\TH:i') }}">
              <textarea name="notes" class="adm-input" rows="2" placeholder="یادداشت پیگیری">{{ $consultation->notes }}</textarea>
              <button type="submit" class="adm-btn adm-btn-line adm-btn-sm">ذخیره وضعیت</button>
            </form>
            @if(auth()->user()->hasRole('admin'))
              <form method="POST" action="{{ route('admin.consultations.destroy', $consultation) }}" onsubmit="return confirm('این درخواست مشاوره حذف شود؟')">
                @csrf @method('DELETE')
                <button type="submit" class="adm-btn adm-btn-line adm-btn-sm">حذف درخواست</button>
              </form>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="6">درخواست مشاوره‌ای ثبت نشده است.</td></tr>
      @endforelse
    </tbody>
  </table></div>
  <x-slot:footer>{{ $consultations->links() }}</x-slot:footer>
</x-admin.panel>
@endsection
