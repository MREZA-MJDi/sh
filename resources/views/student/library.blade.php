@extends('student.layouts.app')

@section('title', 'کتابخانه و خریدها')

@section('content')
<section class="page" id="library"><div class="card"><div class="chead"><h2>کتابخانه و خریدهای من</h2><a class="pill" href="{{ url('/courses') }}">فروشگاه دوره‌ها</a></div>
 <div class="g3">
  @forelse ($purchases as $purchase)
   <div class="card cc">
    <div class="th" style="background:linear-gradient(160deg,#6fb2b6,#4b6fb3)"><svg class="i"><use href="#i-book"/></svg></div>
    <h3>{{ $purchase->course?->title ?? 'دوره حذف‌شده' }}</h3>
    <p>{{ $purchase->course?->subject }} · {{ $purchase->course?->grade }}</p>
    @php
      $paymentStatus = ['paid' => 'پرداخت تأییدشده', 'pending_review' => 'رسید در انتظار بررسی', 'awaiting_provider' => 'در انتظار فعال‌سازی درگاه', 'failed' => 'پرداخت ناموفق', 'refund_pending' => 'درخواست استرداد', 'refunded' => 'مستردشده'];
    @endphp
    <p>وضعیت پرداخت: {{ $paymentStatus[$purchase->status] ?? $purchase->status }}</p>
    <p>مبلغ: {{ number_format($purchase->amount) }} تومان</p>
    <p>تاریخ پرداخت: {{ $purchase->paid_at?->format('Y/m/d') ?? 'هنوز پرداخت تأیید نشده' }}</p>
    @if ($purchase->status === 'awaiting_provider')
     <span class="tag">درگاه آنلاین در انتظار فعال‌سازی است</span>
    @elseif ($purchase->status === 'pending_review')
     <span class="tag">پس از تأیید رسید، دسترسی فعال می‌شود</span>
    @endif
   </div>
  @empty
   <p>خریدی برای حساب شما ثبت نشده است.</p>
  @endforelse
 </div>
 {{ $purchases->links() }}
 <div class="chead"><h2>دوره‌های دارای دسترسی فعال</h2></div>
 <div class="g3">
  @forelse ($accesses as $access)
   <div class="card cc">
    <div class="th" style="background:linear-gradient(160deg,#9b9acb,#4a4b8a)"><svg class="i"><use href="#i-cart"/></svg></div>
    <h3>{{ $access->course?->title ?? 'دوره' }}</h3>
    <p>{{ $access->course?->subject }} · {{ $access->course?->grade }}</p>
    <p>دسترسی از {{ $access->granted_at?->format('Y/m/d') ?? '—' }}</p>
    <p>انقضا: {{ $access->expires_at?->format('Y/m/d') ?? 'بدون تاریخ انقضا' }}</p>
   </div>
  @empty
   <p>دسترسی فعالی خارج از کلاس‌های ثبت‌شده برای شما وجود ندارد.</p>
  @endforelse
 </div>
</div></section>
@endsection
