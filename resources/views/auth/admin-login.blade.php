<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ورود مدیر | آکادمی شیخان</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="panel-body">
<main class="panel-auth">
    <section class="panel-auth-card">
        <div class="panel-auth-brand">
            <div class="panel-brand-mark">ش</div>
            <div>
                <h1>آکادمی شیخان</h1>
                <p>سامانه مدیریت و آموزش</p>
            </div>
        </div>

        <div class="panel-auth-title">
            <h2>ورود به پنل مدیریت</h2>
            <p>برای ادامه، اطلاعات حساب مدیریتی خود را وارد کنید.</p>
        </div>

        @include('admin.partials.flash')

        <form method="POST" action="{{ route('admin.login.store') }}" class="grid gap-4">
            @csrf
            <div class="panel-field">
                <label for="email">ایمیل مدیر</label>
                <input class="panel-input" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
                @error('email') <div class="panel-field-error">{{ $message }}</div> @enderror
            </div>
            <div class="panel-field">
                <label for="password">گذرواژه</label>
                <input class="panel-input" id="password" name="password" type="password" autocomplete="current-password" required>
                @error('password') <div class="panel-field-error">{{ $message }}</div> @enderror
            </div>
            <label class="panel-checkbox"><input type="checkbox" name="remember" value="1"> مرا به خاطر بسپار</label>
            <button class="panel-btn w-full" type="submit">ورود امن به پنل</button>
        </form>
        <p class="panel-muted mt-5 text-center">دسترسی این صفحه فقط برای حساب‌های دارای نقش مدیر فعال است.</p>
    </section>
</main>
</body>
</html>
