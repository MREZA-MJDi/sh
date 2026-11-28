<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <style>
        body { margin: 0; background: #f1f4f7; color: #16263b; font: 1rem/1.7 Tahoma, sans-serif }
        main { max-width: 58rem; margin: auto; padding: 1rem }
        header { position: sticky; top: 0; z-index: 1; background: #f1f4f7; padding: .5rem 0 1rem }
        h1 { margin: .25rem 0; font-size: 1.25rem }
        nav { display: flex; align-items: center; flex-wrap: wrap; gap: .75rem }
        nav a { background: #143867; color: white; padding: .35rem .75rem; border-radius: .45rem }
        canvas { display: block; width: 100%; height: auto; background: white; box-shadow: 0 2px 12px #16263b24 }
        canvas[hidden] { display: none }
        [data-document-content] { user-select: none; -webkit-user-select: none; -webkit-touch-callout: none }
        @media print { main { display: none } }
        pre { white-space: pre-wrap; overflow-wrap: anywhere; background: white; padding: 1rem }
        .reader-brand { display: flex; justify-content: center; margin-block-end: .5rem }
        .reader-text { position: relative; overflow: hidden }
        .reader-mark { position: absolute; inset: 0; display: grid; place-items: center; pointer-events: none; overflow: hidden }
        .reader-mark span { max-width: 82%; padding: .3em .8em; border-radius: 999px; background: #1326420b; color: #13264234; font-size: clamp(16px, 5vw, 42px); font-weight: 700; transform: rotate(32deg); filter: blur(.7px); overflow-wrap: anywhere }
    </style>
</head>
<body>
    <main>
        <header>
            <div class="reader-brand"><x-learning.academy-badge/></div>
            <h1>{{ $title }}</h1>
            @if ($pages)
                <nav aria-label="صفحه‌های سند">
                    @if ($page > 1)<a href="{{ $previousUrl }}" rel="prev">صفحه قبل</a>@endif
                    <span>صفحه {{ $page }} از {{ $pages }}</span>
                    @if ($page < $pages)<a href="{{ $nextUrl }}" rel="next">صفحه بعد</a>@endif
                </nav>
            @endif
        </header>
        <section data-document-content>
        @if ($pages)
            <p id="document-render-status" role="status">در حال بارگذاری صفحه…</p>
            <canvas data-render-url="{{ $renderUrl }}" role="img" aria-label="تصویر صفحه {{ $page }} از {{ $title }}" hidden></canvas>
            <noscript><p>برای مشاهدهٔ صفحه، جاوااسکریپت مرورگر را فعال کنید.</p></noscript>
        @elseif ($text !== null)
            <div class="reader-text">
                <pre dir="auto">{{ $text }}</pre>
                <div class="reader-mark" aria-hidden="true"><span><bdi dir="ltr">{{ $watermark }}</bdi></span></div>
            </div>
            @if ($textTruncated)<p>فقط ۱۲۸ کیلوبایت اول متن نمایش داده شده است.</p>@endif
        @else
            <p>پیش‌نمایش این فرمت در دسترس نیست. این سند باید به صورت PDF یا تصویر بارگذاری شود.</p>
        @endif
        </section>
    </main>
    <script>
        (() => {
            const content = document.querySelector('[data-document-content]');
            for (const eventName of ['contextmenu', 'dragstart', 'selectstart']) {
                content.addEventListener(eventName, (event) => event.preventDefault());
            }
            document.addEventListener('copy', (event) => event.preventDefault());
            document.addEventListener('keydown', (event) => {
                if ((event.ctrlKey || event.metaKey) && ['a', 'c', 's', 'p'].includes(event.key.toLowerCase())) {
                    event.preventDefault();
                }
            });

            const canvas = content.querySelector('canvas');
            if (!canvas) return;
            const status = document.getElementById('document-render-status');

            async function renderPage() {
                let objectUrl;
                try {
                    const result = await fetch(canvas.dataset.renderUrl, { cache: 'no-store', redirect: 'error' });
                    if (!result.ok || !result.headers.get('Content-Type')?.startsWith('image/png')) {
                        throw new Error('Page unavailable');
                    }
                    objectUrl = URL.createObjectURL(await result.blob());
                    const page = new Image();
                    page.src = objectUrl;
                    await page.decode();
                    canvas.width = page.naturalWidth;
                    canvas.height = page.naturalHeight;
                    canvas.getContext('2d').drawImage(page, 0, 0);
                    canvas.hidden = false;
                    status.hidden = true;
                } catch {
                    status.textContent = 'نمایش صفحه ممکن نشد. صفحه را تازه‌سازی کنید.';
                } finally {
                    if (objectUrl) URL.revokeObjectURL(objectUrl);
                }
            }

            renderPage();
        })();
    </script>
</body>
</html>
