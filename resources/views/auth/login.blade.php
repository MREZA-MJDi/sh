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

        @if (! empty($demoAccounts) && is_string($demoPassword) && $demoPassword !== '')
            <section class="panel-demo-accounts" aria-labelledby="demo-accounts-title">
                <h3 id="demo-accounts-title">ورود سریع برای بررسی پنل‌ها</h3>
                <p class="panel-muted">برای پر شدن خودکار فرم، حساب موردنظر را انتخاب کنید.</p>

                <div class="grid gap-2">
                    @foreach ($demoAccounts as $account)
                        <button
                            class="panel-demo-account"
                            type="button"
                            data-demo-account
                            data-demo-email="{{ $account['email'] }}"
                            data-demo-password="{{ $demoPassword }}"
                        >
                            <span>
                                <strong>{{ $account['label'] }}</strong>
                                <small dir="ltr">{{ $account['email'] }}</small>
                            </span>
                            <span class="panel-demo-account-action">انتخاب حساب</span>
                        </button>
                    @endforeach
                </div>

                <div class="panel-demo-password">
                    <span>گذرواژه مشترک حساب‌های آزمایشی</span>
                    <strong dir="ltr">{{ $demoPassword }}</strong>
                </div>
                <p class="panel-muted">این حساب‌ها فقط در محیط توسعه و با فعال‌سازی گزینهٔ آزمایشی ساخته و نمایش داده می‌شوند.</p>
            </section>
        @endif

        <div class="panel-auth-footer">
            <span>حساب دانش‌آموزی ندارید؟</span>
            <a href="{{ route('register') }}">ثبت‌نام دانش‌آموز</a>
        </div>
    </section>
</main>
</body>
</html>
