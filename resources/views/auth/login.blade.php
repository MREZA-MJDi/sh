<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title>ورود و ثبت‌نام | آکادمی شیخان</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap">
    @vite(['resources/css/app.css','resources/css/public-academy.css','resources/js/app.js'])
</head>
<body class="academy">
<header class="academy-nav">
    <div class="ac-wrap ac-nav-row">
        <a class="ac-brand" href="{{ route('home') }}"><span class="ac-brand-mark">ش</span><span>آکادمی شیخان</span></a>
        <a class="ac-link" href="{{ route('public.courses.index') }}">مشاهده دوره‌ها</a>
    </div>
</header>

<main class="ac-auth-page">
    <section class="ac-auth-main">
        <div class="ac-card ac-auth-card">
            <div class="auth-card-heading">
                <span class="auth-kicker">خوش آمدی</span>
                <h1>ورود به حساب کاربری</h1>
                <p class="ac-muted">وارد حساب شو یا برای شروع یادگیری، حساب دانش‌آموزی بساز.</p>
            </div>

            @if(session('status'))
                <div class="ac-alert" role="status">{{ session('status') }}</div>
            @endif

            @if($errors->any())
                <div class="ac-alert" role="alert" style="background:#fee2e2;color:#b91c1c">
                    @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                </div>
            @endif

            <div class="auth-tabs" role="tablist" aria-label="ورود یا ثبت‌نام">
                <button type="button" class="auth-tab {{ ($activeTab ?? 'login') === 'login' ? 'is-active' : '' }}" id="loginTab" role="tab" aria-controls="loginPanel" aria-selected="{{ ($activeTab ?? 'login') === 'login' ? 'true' : 'false' }}">ورود</button>
                <button type="button" class="auth-tab {{ ($activeTab ?? 'login') === 'register' ? 'is-active' : '' }}" id="registerTab" role="tab" aria-controls="registerPanel" aria-selected="{{ ($activeTab ?? 'login') === 'register' ? 'true' : 'false' }}">ثبت‌نام دانش‌آموز</button>
            </div>

            <section id="loginPanel" role="tabpanel" aria-labelledby="loginTab" @if(($activeTab ?? 'login') === 'register') hidden @endif>
                <form method="POST" action="{{ route('login.store') }}">
                    @csrf
                    <label for="email">ایمیل، شماره موبایل یا شناسه حساب</label>
                    <input class="ac-field" id="email" name="email" type="text" value="{{ old('email') }}" autocomplete="username" required @if(($activeTab ?? 'login') === 'login') autofocus @endif>
                    <label for="password">گذرواژه</label>
                    <input class="ac-field" id="password" name="password" type="password" autocomplete="current-password" required>
                    <label class="auth-remember"><input type="checkbox" name="remember" value="1" @checked(old('remember'))> مرا به خاطر بسپار</label>
                    <button class="ac-btn auth-submit" type="submit">ورود به پنل</button>
                </form>

                @if (! empty($demoAccounts) && is_string($demoPassword) && $demoPassword !== '')
                    <div class="auth-demo">
                        <div class="auth-demo-heading">
                            <strong>ورود سریع برای بررسی پنل‌ها</strong>
                            <span>برای ورود، کارت پنل موردنظر را انتخاب کن.</span>
                        </div>
                        <div class="auth-demo-grid">
                            @foreach ($demoAccounts as $account)
                                <button type="button" class="auth-demo-card" data-demo-account data-demo-email="{{ $account['email'] }}" data-demo-password="{{ $demoPassword }}">
                                    <span class="auth-demo-icon" aria-hidden="true">{{ ['admin'=>'⚙','teacher'=>'✎','student'=>'▣','parent'=>'⌂'][$account['role']] ?? '•' }}</span>
                                    <span class="auth-demo-copy"><strong>{{ $account['label'] }}</strong><small dir="ltr">{{ $account['email'] }}</small></span>
                                    <span class="auth-demo-action">انتخاب</span>
                                </button>
                            @endforeach
                        </div>
                        <div class="auth-demo-password">
                            <span>گذرواژه مشترک حساب‌های آزمایشی</span>
                            <strong dir="ltr">{{ $demoPassword }}</strong>
                        </div>
                        <p class="auth-demo-note">این حساب‌ها فقط برای تست محیط محلی فعال شده‌اند.</p>
                    </div>
                @endif
            </section>

            <section id="registerPanel" role="tabpanel" aria-labelledby="registerTab" @if(($activeTab ?? 'login') !== 'register') hidden @endif>
                <p class="auth-register-note">این فرم یک حساب دانش‌آموزی ایجاد می‌کند.</p>
                <form method="POST" action="{{ route('register.store') }}">
                    @csrf
                    <label for="register_name">نام و نام خانوادگی</label>
                    <input class="ac-field" id="register_name" name="name" value="{{ old('name') }}" autocomplete="name" maxlength="120" required>
                    @error('name')<div class="ac-error">{{ $message }}</div>@enderror

                    <label for="register_email">ایمیل</label>
                    <input class="ac-field" id="register_email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" maxlength="190" required>
                    @error('email')<div class="ac-error">{{ $message }}</div>@enderror

                    <label for="register_phone">شماره تماس (اختیاری)</label>
                    <input class="ac-field" id="register_phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel" maxlength="30">
                    @error('phone')<div class="ac-error">{{ $message }}</div>@enderror

                    <label for="register_password">گذرواژه (حداقل ۱۰ کاراکتر)</label>
                    <input class="ac-field" id="register_password" name="password" type="password" autocomplete="new-password" minlength="10" required>
                    @error('password')<div class="ac-error">{{ $message }}</div>@enderror

                    <label for="register_password_confirmation">تکرار گذرواژه</label>
                    <input class="ac-field" id="register_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="10" required>

                    <button class="ac-btn auth-submit" type="submit">ساخت حساب دانش‌آموزی</button>
                </form>
            </section>

            <div class="auth-card-footer"><a href="{{ route('home') }}">بازگشت به صفحه اصلی</a></div>
        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const loginTab = document.getElementById('loginTab');
    const registerTab = document.getElementById('registerTab');
    const loginPanel = document.getElementById('loginPanel');
    const registerPanel = document.getElementById('registerPanel');

    function activate(tab) {
        const showLogin = tab === 'login';
        loginPanel.hidden = !showLogin;
        registerPanel.hidden = showLogin;
        loginTab.classList.toggle('is-active', showLogin);
        registerTab.classList.toggle('is-active', !showLogin);
        loginTab.setAttribute('aria-selected', String(showLogin));
        registerTab.setAttribute('aria-selected', String(!showLogin));
    }

    loginTab.addEventListener('click', () => activate('login'));
    registerTab.addEventListener('click', () => activate('register'));

    document.querySelectorAll('[data-demo-account]').forEach((button) => {
        button.addEventListener('click', () => {
            activate('login');
            document.getElementById('email').value = button.dataset.demoEmail || '';
            document.getElementById('password').value = button.dataset.demoPassword || '';
            document.getElementById('password').focus();
        });
    });
});
</script>
</body>
</html>
