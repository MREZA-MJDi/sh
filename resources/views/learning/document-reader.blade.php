<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <style>
        body { margin: 0; background: #f1f4f7; color: #16263b; font: 1rem/1.7 Tahoma, sans-serif }
        main { max-width: 58rem; margin: auto; padding: 1rem }
        header { position: sticky; top: 0; background: #f1f4f7; padding: .5rem 0 1rem }
        h1 { margin: .25rem 0; font-size: 1.25rem }
        nav { display: flex; align-items: center; flex-wrap: wrap; gap: .75rem }
        nav a { background: #143867; color: white; padding: .35rem .75rem; border-radius: .45rem }
        img { display: block; width: 100%; height: auto; background: white; box-shadow: 0 2px 12px #16263b24 }
        pre { white-space: pre-wrap; overflow-wrap: anywhere; background: white; padding: 1rem }
        .reader-badge { display: inline-block; color: white; background: #132642; padding: .2rem .65rem; border-radius: .45rem; font-size: .8rem }
        .reader-text { position: relative; overflow: hidden }
        .reader-mark { position: absolute; inset: 0; display: grid; place-items: center; pointer-events: none; overflow: hidden }
        .reader-mark span { max-width: 82%; padding: .3em .8em; border-radius: 999px; background: #1326420b; color: #13264234; font-size: clamp(12px, 3vw, 26px); font-weight: 700; transform: rotate(-12deg); filter: blur(.45px); overflow-wrap: anywhere }
    </style>
</head>
<body>
    <main>
        <header>
            <span class="reader-badge">آکادمی شیخان</span>
            <h1>{{ $title }}</h1>
            @if ($pages)
                <nav aria-label="صفحه‌های سند">
                    @if ($page > 1)<a href="{{ $previousUrl }}" rel="prev">صفحه قبل</a>@endif
                    <span>صفحه {{ $page }} از {{ $pages }}</span>
                    @if ($page < $pages)<a href="{{ $nextUrl }}" rel="next">صفحه بعد</a>@endif
                </nav>
            @endif
        </header>
        @if ($pages)
            <img src="{{ $renderUrl }}" alt="تصویر صفحه {{ $page }} از {{ $title }}">
        @elseif ($text !== null)
            <div class="reader-text">
                <pre dir="auto">{{ $text }}</pre>
                <div class="reader-mark" aria-hidden="true"><span>آکادمی شیخان · <bdi dir="ltr">{{ $watermark }}</bdi></span></div>
            </div>
            @if ($textTruncated)<p>فقط ۱۲۸ کیلوبایت اول متن نمایش داده شده است.</p>@endif
        @else
            <p>پیش‌نمایش این فرمت در دسترس نیست. این سند باید به صورت PDF یا تصویر بارگذاری شود.</p>
        @endif
    </main>
</body>
</html>
