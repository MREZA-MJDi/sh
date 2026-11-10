<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title>ثبت‌نام دانش‌آموز | آکادمی شیخان</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="panel-body">
<main class="panel-auth">
    <section class="panel-auth-card">
        <div class="panel-auth-brand">
            <div class="panel-brand-mark">ش</div>
            <div>
                <h1>آکادمی شیخان</h1>
                <p>ساخت حساب آموزشی</p>
            </div>
        </div>

        <div class="panel-auth-title">
            <h2>ثبت‌نام دانش‌آموز</h2>
            <p>پس از ثبت‌نام، حساب شما با نقش دانش‌آموز ساخته می‌شود. ایجاد حساب مدرس یا مدیر از این فرم امکان‌پذیر نیست.</p>
        </div>

        @if ($errors->any())
            <div class="panel-alert panel-alert-error" role="alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('register.store') }}" class="grid gap-4">
            @csrf
            <div class="panel-field">
                <label for="name">نام و نام خانوادگی</label>
                <input class="panel-input" id="name" name="name" type="text" value="{{ old('name') }}"
                       autocomplete="name" maxlength="120" required autofocus>
                @error('name') <div class="panel-field-error">{{ $message }}</div> @enderror
            </div>
            <div class="panel-field">
                <label for="email">ایمیل</label>
                <input class="panel-input" id="email" name="email" type="email" value="{{ old('email') }}"
                       autocomplete="email" inputmode="email" maxlength="190" required>
                @error('email') <div class="panel-field-error">{{ $message }}</div> @enderror
            </div>
            <div class="panel-field">
                <label for="phone">شماره تماس <span class="panel-muted">(اختیاری)</span></label>
                <input class="panel-input" id="phone" name="phone" type="tel" value="{{ old('phone') }}"
                       autocomplete="tel" maxlength="30">
                @error('phone') <div class="panel-field-error">{{ $message }}</div> @enderror
            </div>
            <div class="panel-form-grid">
                <div class="panel-field">
                    <label for="password">گذرواژه</label>
                    <input class="panel-input" id="password" name="password" type="password"
                           autocomplete="new-password" minlength="10" required>
                    @error('password') <div class="panel-field-error">{{ $message }}</div> @enderror
                </div>
                <div class="panel-field">
                    <label for="password_confirmation">تکرار گذرواژه</label>
                    <input class="panel-input" id="password_confirmation" name="password_confirmation" type="password"
                           autocomplete="new-password" minlength="10" required>
                </div>
            </div>
            <button class="panel-btn w-full" type="submit">ساخت حساب دانش‌آموزی</button>
        </form>

        <div class="panel-auth-footer">
            <span>قبلاً ثبت‌نام کرده‌اید؟</span>
            <a href="{{ route('login') }}">ورود به حساب</a>
        </div>
    </section>
</main>
</body>
</html>
