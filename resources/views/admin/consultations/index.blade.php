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
  <x-admin.stat tone="comm" icon="chat" label="کل درخواست‌ها" :value="number_format($consultations->total())"/>
  <x-admin.stat tone="comm" icon="clock" label="در انتظار بررسی" :value="number_format($consultations->getCollection()->where('status', 'pending')->count())"/>
  <x-admin.stat tone="comm" icon="calendar" label="زمان‌بندی‌شده" :value="number_format($consultations->getCollection()->where('status', 'scheduled')->count())"/>
  <x-admin.stat tone="comm" icon="check" label="انجام‌شده" :value="number_format($consultations->getCollection()->where('status', 'done')->count())"/>
</div>

<x-admin.panel flush data-filter data-tone="comm">
  <x-admin.toolbar placeholder="جستجوی درخواست‌کننده، دانش‌آموز یا موضوع…">
    <div class="adm-toolbar-end"><x-admin.seg :options="['pending' => 'در انتظار', 'scheduled' => 'زمان‌بندی‌شده', 'done' => 'انجام‌شده', 'cancelled' => 'لغوشده']"/></div>
  </x-admin.toolbar>
  <div class="adm-table-wrap"><table class="adm-table">
    <thead><tr><th>درخواست‌کننده</th><th>دانش‌آموز / مدرس</th><th>موضوع و نوع</th><th>زمان پیشنهادی</th><th>وضعیت</th><th>ثبت نوبت / یادداشت</th></tr></thead>
    <tbody data-filter-list>
      @forelse($consultations as $consultation)
        <tr id="consultation-{{ $consultation->id }}" data-name="{{ ($consultation->requester?->name ?? '').' '.($consultation->student?->name ?? '').' '.$consultation->topic }}" data-state="{{ $consultation->status }}">
          <td><b>{{ $consultation->requester?->name ?? 'حساب حذف‌شده' }}</b><small>@if($consultation->contact_phone || $consultation->requester?->phone) @phone($consultation->contact_phone ?: $consultation->requester?->phone) @else بدون شماره تماس @endif</small></td>
          <td>{{ $consultation->student?->name ?? '—' }}<small>{{ $consultation->teacher?->name ?? 'مدرس تعیین نشده' }}</small></td>
          <td>{{ $consultation->topic }}<small>{{ $consultation->type }}</small></td>
          <td>{{ $consultation->desired_at?->format('Y/m/d H:i') ?? 'زمانی ثبت نشده' }}</td>
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
