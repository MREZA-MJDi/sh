<!doctype html>
<html lang="fa" dir="rtl">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>{{ $kind === 'skyroom' ? 'کلاس زنده (نمایشی)' : 'آزمون Quiz24 (نمایشی)' }}</title>
<style>body{margin:0;font-family:Vazirmatn,Tahoma,sans-serif;background:#f1f5f9;display:grid;place-items:center;min-height:100vh}
.c{background:#fff;border-radius:16px;padding:32px;max-width:460px;text-align:center;box-shadow:0 8px 30px #0001}
.b{display:inline-block;background:#fef3c7;color:#92400e;border-radius:99px;padding:4px 12px;font-size:13px}
a{color:#2563eb}</style></head>
<body><div class="c">
<span class="b">حالت نمایشی — اتصال واقعی نیست</span>
<h2>{{ $kind === 'skyroom' ? 'اتاق کلاس زنده' : 'آزمون آنلاین' }}</h2>
<p>شناسه: <code dir="ltr">{{ $id }}</code></p>
<p>این صفحه جایگزین سرویس واقعی {{ $kind === 'skyroom' ? 'Skyroom' : 'Quiz24' }} است.</p>
<a href="javascript:history.back()">بازگشت</a>
</div></body></html>
