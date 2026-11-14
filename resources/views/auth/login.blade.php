<!doctype html>
<html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="csrf-token" content="{{ csrf_token() }}"><meta name="robots" content="noindex,nofollow,noarchive"><title>ورود به آکادمی شیخان</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap">@vite(['resources/css/app.css','resources/css/public-academy.css','resources/js/app.js'])</head>
<body class="academy"><header class="academy-nav"><div class="ac-wrap ac-nav-row"><a class="ac-brand" href="{{ route('home') }}"><span class="ac-brand-mark">ش</span><span>آکادمی شیخان</span></a><a class="ac-link" href="{{ route('public.courses.index') }}">مشاهده دوره‌ها</a></div></header>
<main class="ac-auth-page"><aside class="ac-auth-aside"><span style="color:#5eead4;font-weight:800">به شیخان خوش آمدی</span><h1>مسیر یادگیری‌ات را ادامه بده</h1><p>با حساب کاربری خود وارد شو و به امکانات آموزشی متناسب با نقش و دسترسی‌ات برس.</p><ul style="padding:0;list-style:none"><li>✓ دسترسی به دوره‌های ثبت‌نام‌شده</li><li>✓ پیگیری مسیر آموزشی</li><li>✓ ادامه یادگیری از پنل کاربری</li></ul></aside><section class="ac-auth-main"><div class="ac-card ac-auth-card"><h2>ورود به حساب کاربری</h2><p class="ac-muted" style="font-size:13px;margin:0 0 18px">با ایمیل، شماره موبایل یا شناسه حساب وارد شو.</p>
@if(session('status'))<div class="ac-alert">{{ session('status') }}</div>@endif
@if($errors->any())<div class="ac-alert" style="background:#fee2e2;color:#b91c1c">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<form method="POST" action="{{ route('login.store') }}">@csrf<label for="email">ایمیل، موبایل یا شناسه حساب</label><input class="ac-field" id="email" name="email" type="text" value="{{ old('email') }}" autocomplete="username" required autofocus><label for="password">گذرواژه</label><input class="ac-field" id="password" name="password" type="password" autocomplete="current-password" required><label style="display:flex;align-items:center;gap:8px;font-weight:500"><input type="checkbox" name="remember" value="1" @checked(old('remember'))> مرا به خاطر بسپار</label><button class="ac-btn" style="width:100%;margin-top:16px" type="submit">ورود امن</button></form>
@if (! empty($demoAccounts) && is_string($demoPassword) && $demoPassword !== '')
<section style="margin-top:18px;padding:14px;border:1px solid var(--ac-line);border-radius:13px">
    <h3 style="font-size:14px;margin:0 0 8px">حساب‌های آزمایشی</h3>
    <div style="display:grid;gap:7px">
        @foreach ($demoAccounts as $account)
            <button type="button" data-demo-account data-demo-email="{{ $account['email'] }}" data-demo-password="{{ $demoPassword }}" style="display:flex;justify-content:space-between;gap:10px;padding:9px;border:1px solid var(--ac-line);border-radius:9px;background:var(--ac-bg);color:var(--ac-ink);font:inherit;cursor:pointer">
                <strong>{{ $account['label'] }}</strong><small dir="ltr">{{ $account['email'] }}</small>
            </button>
        @endforeach
    </div>
    <p class="ac-muted" style="font-size:11px;margin:8px 0 0">این حساب‌ها فقط در محیط توسعه نمایش داده می‌شوند.</p>
</section>
@endif
<div style="border-top:1px solid var(--ac-line);margin-top:20px;padding-top:16px;font-size:13px">حساب دانش‌آموزی نداری؟ <a class="ac-link" href="{{ route('register') }}">ثبت‌نام کن</a></div></div></section></main></body></html>