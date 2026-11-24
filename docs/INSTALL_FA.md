# به‌روزرسانی آکادمی شیخان روی VPS

این دستورها برای نصب موجود هستند. در مسیر اصلی مخزن و با همان کاربری اجرا کنید که انتشار نسخه‌های قبلی را انجام می‌دهد. تنظیمات فعلی `.env`، کلید برنامه و فایل‌های `storage` برای این به‌روزرسانی استفاده می‌شوند.

## بررسی قبل از انتشار

```bash
pwd
git remote get-url origin
git status --short
git branch --show-current
php -v
composer --version
node --version
npm --version
ffmpeg -version | head -n 1
ffprobe -version | head -n 1
php artisan migrate:status
sudo supervisorctl status
```

نسخهٔ CI پروژه PHP 8.2 و Node.js 22 است. شاخه باید `main` باشد و خروجی `git status --short` خالی باشد. اگر روی VPS تغییر محلی دارید، به‌روزرسانی را متوقف کنید و تغییرات را بررسی کنید. قبل از انتشار از دیتابیس و فایل‌های بارگذاری‌شده نسخهٔ پشتیبان بگیرید. مسیر Document Root باید پوشهٔ `public` برنامه باشد.

## انتشار نسخه

```bash
bash -e <<'DEPLOY'
test "$(git branch --show-current)" = main
test -z "$(git status --porcelain)"
git fetch origin main
printf 'Previous revision: '
git rev-parse HEAD
php artisan down --retry=60
git merge --ff-only origin/main
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
npm ci --include=dev --no-audit --no-fund
npm run build
php artisan optimize:clear
php artisan migrate --force
php artisan optimize
php artisan queue:restart
php artisan up
printf 'Deployed revision: '
git rev-parse --short HEAD
DEPLOY
```

در صورت خطای یکی از دستورها، اسکریپت در همان مرحله متوقف می‌شود. ابتدا خطا را رفع کنید، سپس انتشار را دوباره اجرا کنید. اگر برنامه پس از خطا در وضعیت تعمیر مانده، پیش از باز کردن سایت سلامت نسخه و دیتابیس را بررسی کنید.

## بررسی سرویس‌ها

```bash
php artisan route:list --name=admin.lms
php artisan migrate:status
php artisan schedule:list
php artisan queue:failed
sudo supervisorctl status
```

در صورت استفاده از Supervisor، نمونهٔ `deploy/supervisor-learning.conf` سه برنامهٔ `learning-default`، `learning-video` و `learning-upload-finalize` را تعریف می‌کند. مسیر PHP، مسیر برنامه، کاربر اجرا و اتصال صف را با VPS تطبیق دهید. کارگر نهایی‌سازی قطعه‌ها باید صف `upload-finalize` و کارگر تبدیل ویدئو باید صف `video` را بخواند. برای پاک‌سازی نشست‌های رهاشده، اجرای دقیقه‌ای `php artisan schedule:run` را در زمان‌بند سرور بررسی کنید.

در صورت استفاده از Nginx، اندازهٔ درخواست و مسیرهای ذخیره‌سازی را با `deploy/nginx-learning.conf` مقایسه کنید. پس از تغییر تنظیمات Nginx ابتدا `sudo nginx -t` را اجرا کنید. برای ورود با حساب واقعی از نشانی HTTPS دارای گواهی معتبر استفاده کنید.

## پذیرش نسخه روی VPS

| حساب | مسیر و اقدام | نتیجهٔ قابل ثبت |
|---|---|---|
| مدیر | آموزش ← مرکز مدیریت آموزش ← باز کردن دوره | دوره، کلاس و برنامهٔ جلسات همان دوره باز شوند و لینک ثبت‌نام با همان دوره فیلتر شود. |
| مدیر و مدرس | دوره یا کلاس مربوط ← بارگذاری ویدئو | دریافت فایل، خروج از صف و رسیدن به وضعیت آماده یا منتظر تأیید در حساب ثبت شود. |
| دانش‌آموز عضو و غیرعضو | دوره ← ویدئو و جزوه | عضو بتواند پخش یا دریافت کند و دسترسی غیرعضو محدود باشد. |
| مدرس، دانش‌آموز و ولی | ساخت تکلیف، ارسال پاسخ و ثبت بازخورد | نتیجه در حساب شاگرد و ولی مرتبط دیده شود. |
| مدرس، دانش‌آموز و ولی | ثبت حضور در یک جلسه، باز کردن گزارش دانش‌آموز و ولی | گزارش دارای رکورد و حالت بدون رکورد بدون خطا باز شوند و تاریخ جلسه یکسان باشد. |
| ولی، مدرس و مدیر | ارسال پیام، پاسخ مدرس و تعیین زمان جلسه | گیرنده و پاسخ در همان گفت‌وگو باشند و جلسه در حساب‌های مرتبط دیده شود. |
| مدیر و دانش‌آموز | انتشار اطلاعیه، تنظیم منبع یا محصول و باز کردن داشبورد یا کتابخانه | مخاطب مجاز محتوا را ببیند و وضعیت ذخیره و نمایش محصول ثبت شود. |

برای ایراد ۵۰۰، زمان درخواست و مسیر را یادداشت کنید و همان خطا را در لاگ برنامه یا سرویس PHP پیدا کنید. برای ویدئوی معطل، وضعیت کارگرها و خطای کار مربوط را بررسی کنید. در گزارش رفع، شناسهٔ نسخه، نقش، مسیر، نتیجه و زمان را ثبت کنید. این مراحل مربوط به قابلیت‌های محلی هستند. سرویس‌های بیرونی در راهنمای `docs/integrations/API_HANDOFF_FA.md` توضیح داده شده‌اند.
