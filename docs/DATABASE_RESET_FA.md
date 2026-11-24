# راه‌اندازی دیتابیس و داده‌های آزمایشی شیخان

## بازسازی کامل دیتابیس توسعه

 > **هشدار جدی:** دستور `migrate:fresh` تمام جدول‌ها و داده‌های دیتابیس انتخاب‌شده را حذف می‌کند. روی دیتابیس مشتری واقعی اجرا نکن؛ فقط روی دیتابیس Demo مستقل و قابل حذف، پس از تأیید مالک داده اجرا شود.

در فایل `.env` محیط محلی:

```dotenv
APP_ENV=local
SHIKHAN_DEMO_ACCOUNTS=true
SHIKHAN_DEMO_LOGIN_CARDS=true
SHIKHAN_DEMO_PASSWORD=Replace-With-A-Strong-Local-Password
```

مقدار رمز باید حداقل ۱۲ کاراکتر باشد. برای محیط محلی، دیتابیس توسعه را در `.env` انتخاب و سپس اجرا کن:

```bash
php artisan optimize:clear
php artisan migrate:fresh --seed
php artisan test
```

Seederها idempotent هستند؛ اجرای دوباره `php artisan db:seed` نباید دوره‌ها، کلاس‌ها یا حساب‌های نمایشی تکراری بسازد.

حساب‌های نمایشی فقط با فعال‌کردن صریح `SHIKHAN_DEMO_ACCOUNTS` ساخته می‌شوند. کارت‌های ورود نیز به فعال بودن هم‌زمان `SHIKHAN_DEMO_LOGIN_CARDS` نیاز دارند. نقش SEO عمداً کارت ورود عمومی ندارد.

## حساب مدیر و کارشناس SEO

برای ساخت حساب‌های اختصاصی غیرنمایشی، مقادیر زیر را با ایمیل و رمز قوی تنظیم کن:

```dotenv
SHIKHAN_ADMIN_NAME="مدیر سامانه"
SHIKHAN_ADMIN_EMAIL=admin@example.com
SHIKHAN_ADMIN_PASSWORD=Use-A-Unique-Password-Here

SHIKHAN_SEO_NAME="کارشناس سئو"
SHIKHAN_SEO_EMAIL=seo@example.com
SHIKHAN_SEO_PASSWORD=Use-Another-Unique-Password
```

سپس در محیطی که قرار است حساب‌ها ایجاد شوند:

```bash
php artisan config:clear
php artisan db:seed --class=RolePermissionSeeder --force
php artisan config:cache
```

Seeder نقش SEO را از نقش‌های مشتری‌محور جدا نگه می‌دارد و اگر ایمیل SEO قبلاً به مدیر، مدرس، دانش‌آموز یا ولی اختصاص یافته باشد، عمداً متوقف می‌شود.

## استقرار روی Production

روی دیتابیس دارای اطلاعات واقعی از `migrate:fresh` استفاده نکن. فقط Migrationهای جدید را اجرا کن:

```bash
php artisan down
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder --force
php artisan optimize:clear
php artisan config:cache
php artisan up
```

## Production واقعی

در دیتابیس واقعی فقط migrationهای افزایشی و Seeder نقش و مجوز را اجرا کن. ساخت حساب و محتوای نمایشی محدود به محیط‌های `local` و `testing` است.

## نکته سازگاری Migration جلسات اولیا

نام Migration ساخت جدول `parent_meetings` با شناسه تاریخی `2026_11_13_000001_create_parent_meetings_table` حفظ شده است. این نام در دیتابیس‌های توسعه قبلی ثبت شده بود؛ تغییر نام فایل باعث می‌شد Laravel همان جدول را دوباره ایجاد کند. نسخه فعلی ساختار و ایندکس‌های موردنیاز را در همان Migration canonical نگه می‌دارد.
