<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ورود به آکادمی شیخان</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="panel-body">
<main class="panel-auth">
    <section class="panel-auth-card">
        <div class="panel-auth-brand">
            <div class="panel-brand-mark">ش</div>
            <div>
                <h1>آکادمی شیخان</h1>
                <p>سامانه یکپارچه آموزش و مدیریت</p>
            </div>
        </div>

        <div class="panel-auth-title">
            <h2>ورود به حساب کاربری</h2>
            <p>با حساب خود وارد شوید؛ مقصد ورود بر اساس نقش و دسترسی شما تعیین می‌شود.</p>
        </div>

        @if (session('status'))
            <div class="panel-alert" role="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="panel-alert panel-alert-error" role="alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="grid gap-4">
            @csrf
            <div class="panel-field">
                <label for="email">ایمیل</label>
                <input class="panel-input" id="email" name="email" type="email" value="{{ old('email') }}"
                       autocomplete="username" inputmode="email" required autofocus>
                @error('email') <div class="panel-field-error">{{ $message }}</div> @enderror
            </div>
            <div class="panel-field">
                <label for="password">گذرواژه</label>
                <input class="panel-input" id="password" name="password" type="password"
                       autocomplete="current-password" required>
                @error('password') <div class="panel-field-error">{{ $message }}</div> @enderror
            </div>
            <label class="panel-checkbox">
                <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                مرا به خاطر بسپار
            </label>
            <button class="panel-btn w-full" type="submit">ورود امن</button>
        </form>

        <div class="panel-auth-footer">
            <span>حساب دانش‌آموزی ندارید؟</span>
            <a href="{{ route('register') }}">ثبت‌نام دانش‌آموز</a>
        </div>

        @if (app()->environment('local') && config('demo.enabled'))
            <details class="panel-details mt-5">
                <summary>حساب‌های آزمایشی محیط توسعه</summary>
                <div class="panel-details-content grid gap-2">
                    @foreach ([
                        'مدیر' => 'admin@sh.local',
                        'مدرس' => 'teacher@sh.local',
                        'دانش‌آموز' => 'student@sh.local',
                        'ولی دانش‌آموز' => 'parent@sh.local',
                    ] as $label => $email)
                        <div class="flex items-center justify-between gap-3 text-xs">
                            <span>{{ $label }}</span>
                            <strong dir="ltr">{{ $email }}</strong>
                        </div>
                    @endforeach
                    <p class="panel-muted">گذرواژه مشترک حساب‌ها از متغیر SHIKHAN_DEMO_PASSWORD خوانده می‌شود.</p>
                </div>
            </details>
        @endif
    </section>
</main>
</body>
</html>
