<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>دسترسی مجاز نیست | آکادمی شیخان</title>
    <style>
        *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f5f7fb;color:#172033;font-family:Tahoma,Arial,sans-serif;padding:24px}.panel{width:min(100%,560px);background:#fff;border:1px solid #e5eaf2;border-radius:24px;padding:36px;box-shadow:0 18px 60px #17203312;text-align:center}.code{font-size:13px;color:#8a94a6;letter-spacing:2px}.mark{width:64px;height:64px;margin:20px auto;border-radius:20px;background:#fff1e9;color:#a94d24;display:grid;place-items:center;font-size:28px}h1{font-size:24px;margin:12px 0}p{color:#667085;line-height:2;margin:0 0 24px}.actions{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}a{padding:12px 18px;border-radius:12px;text-decoration:none;background:#25365b;color:#fff}a.secondary{background:#eef1f6;color:#25365b}
    </style>
</head>
<body>
<main class="panel">
    <div class="code">ERROR 403</div><div class="mark" aria-hidden="true">⌑</div>
    <h1>این بخش برای حساب شما در دسترس نیست</h1>
    <p>دسترسی این صفحه بر اساس نقش و مجوزهای حساب شما کنترل می‌شود. اگر فکر می‌کنید باید به این بخش دسترسی داشته باشید، با مدیر آکادمی تماس بگیرید.</p>
    <div class="actions"><a href="{{ url('/') }}">بازگشت به صفحه اصلی</a>@auth<a class="secondary" href="{{ route('dashboard.redirect') }}">رفتن به پنل من</a>@else<a class="secondary" href="{{ route('login') }}">ورود به حساب</a>@endauth</div>
</main>
</body>
</html>
