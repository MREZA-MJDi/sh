@extends('public.layouts.academy')
@section('title',($settings->home_sections['cart_page_title'] ?? 'سبد خرید').$settings->title_separator.$settings->site_name)
@section('robots','noindex,nofollow')
@section('content')
<section class="contact-hero-wrap">
    <div class="c">
        <header class="contact-hero">
            <div class="contact-hero-copy">
                <div class="contact-breadcrumb">
                    <a href="{{ route('home') }}">خانه</a>
                    <span>‹</span>
                    <span>سبد خرید</span>
                </div>
                <h1>{{ $settings->home_sections['cart_page_title'] ?? 'سبد خرید' }}</h1>
                <p>{{ $settings->home_sections['cart_page_description'] ?? 'دوره‌هایی که برای بررسی و ثبت‌نام انتخاب کرده‌اید.' }}</p>
            </div>

            <div class="contact-hero-highlights">
                <div class="contact-highlight-pill">
                    <svg class="i"><use href="#cartI"/></svg>
                    <span>{{ number_format($courses->count()) }} دوره در سبد خرید</span>
                </div>
                <div class="contact-highlight-pill">
                    <svg class="i"><use href="#check"/></svg>
                    <span>بازبینی دوره‌های انتخاب‌شده پیش از ثبت‌نام</span>
                </div>
            </div>
        </header>
    </div>
</section>
<div class="ac-wrap ac-layout" style="grid-template-columns:1fr 330px"><div>@forelse($courses as $course)<article class="ac-card" style="display:flex;align-items:center;gap:16px;padding:16px;margin-bottom:13px"><div class="ac-category-icon" style="margin:0;flex:none">✦</div><div style="flex:1"><b>{{ $course->title }}</b><div class="ac-muted" style="font-size:12px">{{ $course->teacher?->name }}</div></div><b>{{ $course->type==='paid'?number_format((int)$course->price).' تومان':'رایگان' }}</b><form method="POST" action="{{ route('public.cart.remove',$course) }}">@csrf @method('DELETE')<button type="submit" style="border:0;background:none;color:#dc2626;cursor:pointer;font:inherit">حذف</button></form></article>@empty<div class="ac-empty">سبد خریدت خالی است. <a class="ac-link" href="{{ route('public.courses.index') }}">مشاهده دوره‌ها</a></div>@endforelse</div>
<aside class="ac-card ac-block" style="height:max-content"><h2>خلاصه سبد خرید</h2><div class="ac-list"><li><span>تعداد دوره‌ها</span><b>{{ $courses->count() }}</b></li><li><span>جمع مبلغ</span><b>{{ number_format($courses->sum(fn($course)=>$course->type==='paid'?(int)$course->price:0)) }} تومان</b></li></div>
<div role="status" aria-live="polite" style="margin-top:16px;padding:14px;border:1px solid rgba(180,120,40,.35);border-radius:12px;background:rgba(180,120,40,.07)">
  <strong style="display:block;margin-bottom:7px">پرداخت آنلاین در حال آماده‌سازی است</strong>
  <p class="ac-muted" style="font-size:12px;margin:0;line-height:1.9">زیرساخت ثبت سفارش و اتصال درگاه در حال تکمیل است و فعال‌سازی پرداخت به دریافت اطلاعات رسمی درگاه و تأیید الزامات پذیرندگی وابسته است. سبد خرید شما حفظ می‌شود؛ در حال حاضر هیچ مبلغی دریافت یا ثبت‌نام مالی به‌صورت خودکار تأیید نمی‌شود.</p>
  <span style="display:inline-block;margin-top:10px;font-size:12px;font-weight:700">وضعیت: در انتظار فعال‌سازی ارائه‌دهنده پرداخت</span>
</div>
<button class="ac-btn" type="button" disabled aria-disabled="true" style="width:100%;opacity:.55;cursor:not-allowed;margin-top:16px">پرداخت پس از فعال‌سازی درگاه</button>
<a class="ac-link" href="{{ route('public.courses.index') }}">بازگشت به دوره‌ها ←</a></aside></div>
@endsection
