# پردازش تیزر دوره

آپلود تیزر با chunkهای ۵ مگابایتی انجام می‌شود. فایل خام روی دیسک خصوصی `local` ذخیره می‌شود و پس از تکمیل آپلود، Job پردازش در صف `video` قرار می‌گیرد. خروجی MP4 فشرده ۷۲۰p و پوستر روی دیسک `public` قرار می‌گیرند.

## پیش‌نیاز سرور

- نصب `ffmpeg` و در دسترس بودن آن در `PATH` کاربر اجرای queue worker.
- فعال بودن queue driver پایدار (ترجیحاً database یا Redis).
- ساخت symlink عمومی Laravel با `php artisan storage:link`.
- فضای موقت کافی روی دیسک `local` برای فایل خام و فضای کافی روی دیسک `public` برای خروجی.

## Worker

Worker پردازش ویدیو باید صف `video` را مصرف کند:

```bash
php artisan queue:work --queue=video,default --sleep=3 --tries=1 --timeout=3600
```

این worker را زیر Supervisor یا systemd اجرا کنید و بعد از deploy/restart، وضعیت آن را بررسی کنید. صرفاً قرار گرفتن Job در صف به معنی آماده‌شدن تیزر نیست؛ وضعیت دوره تا پایان پردازش `processing` می‌ماند و در صورت نبود ffmpeg یا خطای تبدیل به `failed` تغییر می‌کند.

## محدودیت‌ها

- سقف تیزر در `config/learning.php` با `max_teaser_mb` کنترل می‌شود.
- هر upload session به کاربر سازنده متصل است و پس از دو ساعت منقضی می‌شود.
- فعلاً chunk upload فقط برای تیزر دوره فعال است؛ مسیر ویدیوهای درس تا زمان تطبیق کامل مدل `CourseLesson` با جدول‌های واقعی پروژه عمداً فعال نشده است.
