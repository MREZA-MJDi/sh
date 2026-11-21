@extends('admin.layouts.app')
@section('title', 'پرداخت‌ها')
@section('content')
@php
  $statusLabels = ['awaiting_provider' => ['warn', 'در انتظار فعال‌سازی درگاه'], 'pending_review' => ['info', 'در انتظار بررسی دستی'], 'paid' => ['ok', 'تأییدشده'], 'failed' => ['bad', 'ناموفق'], 'refund_pending' => ['warn', 'درخواست استرداد'], 'refunded' => ['mute', 'مستردشده']];
  $methods = ['gateway' => 'درگاه آنلاین', 'cash' => 'نقدی / حضوری', 'bank_transfer' => 'واریز بانکی'];
  $studentOptions = $students->pluck('name', 'id')->all();
  $courseOptions = $courses->mapWithKeys(fn ($course) => [$course->id => $course->title.' — '.number_format($course->price).' تومان'])->all();
@endphp
<x-admin.page-head tone="shop" icon="card" title="پرداخت‌ها" desc="سوابق مالی واقعی و وضعیت تأیید؛ پرداخت بدون رسید معتبر یا پاسخ رسمی درگاه تأیید نمی‌شود">
  <x-admin.btn icon="plus" data-modal-open="pay-modal">ثبت رسید پرداخت دستی</x-admin.btn>
</x-admin.page-head>
<x-admin.panel data-tone="shop">
  <div role="status" aria-live="polite" style="padding:14px;border:1px solid var(--line);border-radius:12px">
    <b>درگاه آنلاین هنوز فعال نشده است</b>
    <p class="adm-muted" style="margin:6px 0 0">اتصال درگاه و فرآیند پذیرندگی در انتظار دریافت اطلاعات رسمی است. سامانه پرداخت موفق ساختگی ثبت نمی‌کند؛ رسید نقدی یا بانکی در وضعیت بررسی دستی ثبت می‌شود و فقط پس از تأیید واقعی مدیر، مجوز دوره فعال خواهد شد.</p>
  </div>
</x-admin.panel>
<div class="adm-stats">
  <x-admin.stat tone="shop" icon="card" label="درآمد تأییدشده امروز" :value="number_format($stats['today'])" hint="تومان"/>
  <x-admin.stat tone="shop" icon="chart" label="درآمد تأییدشده ماه" :value="number_format($stats['month'])" hint="تومان"/>
  <x-admin.stat tone="shop" icon="clock" label="در انتظار بررسی دستی" :value="number_format($stats['review'])"/>
  <x-admin.stat tone="shop" icon="refresh" label="در انتظار درگاه" :value="number_format($stats['provider'])"/>
</div>
<x-admin.panel flush data-tone="shop">
  <form method="GET" class="adm-toolbar">
    <input class="adm-input" type="search" name="q" value="{{ request('q') }}" placeholder="جستجوی کد، دانش‌آموز یا دوره…">
    <select class="adm-select" name="status"><option value="">همه وضعیت‌ها</option>@foreach($statusLabels as $key => $label)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $label[1] }}</option>@endforeach</select>
    <button class="adm-btn adm-btn-line adm-btn-sm" type="submit">اعمال فیلتر</button>
  </form>
  <div class="adm-table-wrap"><table class="adm-table">
    <thead><tr><th>شناسه پرداخت</th><th>دانش‌آموز</th><th>دوره</th><th>مبلغ (تومان)</th><th>روش</th><th>وضعیت</th><th>تأییدکننده</th><th></th></tr></thead>
    <tbody>
      @forelse($payments as $payment)
        @php $status = $statusLabels[$payment->status] ?? ['mute', $payment->status]; @endphp
        <tr>
          <td class="adm-num"><b dir="ltr">{{ $payment->reference }}</b><small>{{ \\App\\Support\\PersianDate::format($payment->created_at, 'Y/m/d H:i') }}</small></td>
          <td>{{ $payment->student?->name ?? 'حساب حذف‌شده' }}</td>
          <td>{{ $payment->course?->title ?? 'دوره حذف‌شده' }}</td>
          <td class="adm-num"><b>{{ number_format($payment->amount) }}</b></td>
          <td>{{ $methods[$payment->method] ?? $payment->method }}</td>
          <td><x-admin.badge :tone="$status[0]">{{ $status[1] }}</x-admin.badge>@if($payment->note)<small>{{ $payment->note }}</small>@endif</td>
          <td>{{ $payment->verifier?->name ?? '—' }}</td>
          <td>
            @if($payment->status === 'pending_review' && $payment->provider === 'manual')
              <form method="POST" action="{{ route('admin.payments.verify', $payment) }}" onsubmit="return confirm('فقط در صورت تطبیق رسید و دریافت واقعی وجه، پرداخت را تأیید کنید.')">
                @csrf @method('PATCH')
                <button class="adm-btn adm-btn-primary adm-btn-sm" type="submit">تأیید و فعال‌سازی</button>
              </form>
            @else <span class="adm-muted">بدون اقدام</span> @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="8">هنوز تراکنش مالی ثبت نشده است؛ داده نمایشی یا ساختگی نشان داده نمی‌شود.</td></tr>
      @endforelse
    </tbody>
  </table></div>
  <x-slot:footer>{{ $payments->links() }}</x-slot:footer>
</x-admin.panel>
@endsection
@push('modals')
<x-admin.modal :demo="false" id="pay-modal" title="ثبت رسید پرداخت دستی" size="lg" :action="route('admin.payments.store')">
  <x-admin.legend title="پرداخت‌کننده و دوره"/>
  <x-admin.select label="دانش‌آموز فعال" name="student_id" :options="$studentOptions" required/>
  <x-admin.select label="دوره پولی منتشرشده" name="course_id" :options="$courseOptions" required/>
  <x-admin.input label="مبلغ دریافت‌شده" name="amount" type="number" min="1" addon="تومان" required/>
  <x-admin.select label="روش دریافت" name="method" :options="['cash' => 'نقدی / حضوری', 'bank_transfer' => 'واریز بانکی']" required/>
  <x-admin.input label="شماره پیگیری رسید (اختیاری)" name="provider_reference"/>
  <x-admin.textarea label="یادداشت بررسی" name="note" wide rows="2"/>
  <p class="adm-muted wide">ثبت فرم فقط رسید در انتظار بررسی می‌سازد. فعال‌سازی ثبت‌نام تا تأیید واقعی دریافت وجه انجام نمی‌شود.</p>
</x-admin.modal>
@endpush
