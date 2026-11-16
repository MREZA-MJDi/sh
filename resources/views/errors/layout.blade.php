<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title>@yield('title', 'خطای سامانه') | آکادمی شیخان</title>
    <style>
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:#f4f6fb;color:#172033;font-family:Vazirmatn,Tahoma,Arial,sans-serif}
        .error-card{width:min(100%,620px);padding:clamp(24px,5vw,44px);border:1px solid #e3e8f1;border-radius:24px;background:#fff;box-shadow:0 20px 70px #17203312;text-align:center}
        .brand{display:inline-flex;align-items:center;gap:10px;margin-bottom:28px;font-weight:800;color:#24365b}
        .brand-mark{display:grid;place-items:center;width:38px;height:38px;border-radius:12px;background:#24365b;color:#fff}
        .error-code{font-size:12px;font-weight:800;letter-spacing:2px;color:#7b879b}
        h1{margin:14px 0;font-size:clamp(22px,4vw,30px);line-height:1.6}
        p{margin:0 auto 26px;max-width:480px;color:#667085;line-height:2}
        .actions{display:flex;justify-content:center;gap:10px;flex-wrap:wrap}
        a{display:inline-flex;justify-content:center;align-items:center;min-height:44px;padding:10px 18px;border-radius:12px;background:#24365b;color:#fff;text-decoration:none;font-size:14px;font-weight:700}
        a.secondary{background:#eef1f6;color:#24365b}
    </style>
</head>
<body>
    <main class="error-card">
        <div class="brand"><span class="brand-mark">ش</span><span>آکادمی شیخان</span></div>
        <div class="error-code">@yield('code', 'SYSTEM')</div>
        <h1>@yield('heading', 'در انجام درخواست مشکلی پیش آمد')</h1>
        <p>@yield('message', 'این وضعیت ثبت شده است. می‌توانید به صفحه اصلی برگردید و دوباره تلاش کنید.')</p>
        <div class="actions">
            <a href="{{ url('/') }}">صفحه اصلی</a>
            @auth
                <a class="secondary" href="{{ route('dashboard.redirect') }}">پنل من</a>
            @else
                <a class="secondary" href="{{ route('login') }}">ورود به حساب</a>
            @endauth
        </div>
    </main>
</body>
</html>
