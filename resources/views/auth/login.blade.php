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

        @if (app()->environment('local') && config('demo.enabled') && config('demo.password'))
            <details class="panel-details mt-5" open>
                <summary>ورود سریع به داشبوردهای آزمایشی</summary>
                <div class="panel-details-content grid gap-3">
                    <p class="panel-muted">برای ورود سریع، یکی از نقش‌ها را انتخاب کنید. این حساب‌ها فقط در محیط توسعه ساخته می‌شوند.</p>
                    @foreach ([
                        ['label' => 'مدیر سامانه', 'email' => 'admin@sh.local'],
                        ['label' => 'معلم', 'email' => 'teacher@sh.local'],
                        ['label' => 'دانش‌آموز', 'email' => 'student@sh.local'],
                        ['label' => 'ولی دانش‌آموز', 'email' => 'parent@sh.local'],
                    ] as $account)
                        <div class="flex items-center justify-between gap-3 text-xs">
                            <div class="grid gap-1">
                                <strong>{{ $account['label'] }}</strong>
                                <span dir="ltr">{{ $account['email'] }}</span>
                            </div>
                            <button
                                class="panel-btn"
                                type="button"
                                data-demo-email="{{ $account['email'] }}"
                                data-demo-password="{{ config('demo.password') }}"
                            >انتخاب حساب</button>
                        </div>
                    @endforeach
                    <div class="grid gap-1 text-xs">
                        <span class="panel-muted">گذرواژه مشترک:</span>
                        <strong dir="ltr">{{ config('demo.password') }}</strong>
                    </div>
                </div>
            </details>
            <script>
                document.querySelectorAll('[data-demo-email]').forEach((button) => {
                    button.addEventListener('click', () => {
                        document.getElementById('email').value = button.dataset.demoEmail;
                        document.getElementById('password').value = button.dataset.demoPassword;
                        document.getElementById('password').focus();
                    });
                });
            </script>
        @endif
    </section>
</main>
</body>
</html>
