@extends('student.layouts.app')

@section('title', 'کتابخانه و خریدها')

@section('content')
<section class="page" id="library">
<x-portal.page-head icon="wallet" tone="commerce" title="کتابخانه و خریدهای من"><a class="btn s" href="{{ route('public.courses.index') }}">فروشگاه دوره‌ها</a></x-portal.page-head>
<div class="card"><div class="chead"><h2>پرداخت‌های دوره</h2></div>
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
    <p>تاریخ پرداخت: {{ $purchase->paid_at ? \App\Support\PersianDate::format($purchase->paid_at, 'Y/m/d') : 'هنوز پرداخت تأیید نشده' }}</p>
    @if ($purchase->status === 'awaiting_provider')
     <span class="tag">درگاه آنلاین در انتظار فعال‌سازی است</span>
    @elseif ($purchase->status === 'pending_review')
     <span class="tag">پس از تأیید رسید، دسترسی فعال می‌شود</span>
    @endif
   </div>
  @empty
   <x-portal.empty-state icon="folder" tone="commerce" title="خرید دوره‌ای برای حساب شما ثبت نشده است."/>
  @endforelse
 </div>
 {{ $purchases->links() }}
 <div class="chead"><h2>خرید کتاب و منابع آموزشی</h2><a class="pill" href="{{ route('student.resources.index') }}">منابع و محصولات</a></div>
 <div class="g3">
  @forelse($resourcePurchases as $purchase)
   <article class="card cc">
    <div class="th"><svg class="i" aria-hidden="true"><use href="#i-file"/></svg></div>
    <h3>{{ $purchase->resource?->title ?? 'منبع حذف‌شده' }}</h3>
    <span class="tag {{ $purchase->status === 'paid' ? 'd' : 'w' }}">{{ ['paid'=>'خرید تأییدشده','pending_review'=>'در انتظار بررسی رسید','rejected'=>'ردشده','failed'=>'ناموفق','refunded'=>'مستردشده'][$purchase->status] ?? 'در حال بررسی' }}</span>
    <p>مبلغ: {{ number_format($purchase->amount) }} تومان</p>
    <p>تاریخ ثبت: {{ \App\Support\PersianDate::format($purchase->created_at, 'Y/m/d') }}</p>
    @if($purchase->status === 'paid' && $purchase->resource?->status === 'published')
     <a class="btn s" href="{{ route('student.resources.index') }}">بررسی دسترسی به منبع</a>
    @endif
   </article>
  @empty
   <x-portal.empty-state icon="file" tone="commerce" title="هنوز خرید منبع آموزشی ثبت نشده است."/>
  @endforelse
 </div>
 {{ $resourcePurchases->links() }}
 <div class="chead"><h2>دوره‌های دارای دسترسی فعال</h2></div>
 <div class="g3">
  @forelse ($accesses as $access)
   <div class="card cc">
    <div class="th" style="background:linear-gradient(160deg,#9b9acb,#4a4b8a)"><svg class="i"><use href="#i-cart"/></svg></div>
    <h3>{{ $access->course?->title ?? 'دوره' }}</h3>
    <p>{{ $access->course?->subject }} · {{ $access->course?->grade }}</p>
    <p>دسترسی از {{ \App\Support\PersianDate::format($access->granted_at, 'Y/m/d') }}</p>
    <p>انقضا: {{ $access->expires_at ? \App\Support\PersianDate::format($access->expires_at, 'Y/m/d') : 'بدون تاریخ انقضا' }}</p>
   </div>
  @empty
   <x-portal.empty-state icon="folder" title="دسترسی فعال دیگری ثبت نشده است."/>
  @endforelse
 </div>
 {{ $accesses->links() }}
</div></section>
@endsection
