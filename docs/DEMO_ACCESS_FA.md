# دسترسی نمایشی و حساب داخلی SEO

## حساب‌های نمایشی جلسه

حساب‌های نمایشی با فعال‌سازی صریح در محیط‌های `local` و `testing` ساخته می‌شوند.

در فایل `.env` محیط محلی تنظیمات زیر را وارد کنید.

```dotenv
SHIKHAN_DEMO_ACCOUNTS=true
SHIKHAN_DEMO_LOGIN_CARDS=true
SHIKHAN_DEMO_PASSWORD=<یک رمز موقت قوی حداقل ۱۲ کاراکتری>
```

سپس:

```bash
php artisan config:clear
php artisan db:seed
php artisan config:cache
```

کارت‌های ورود شامل این نقش‌ها هستند:

| نقش | نام کاربری |
|---|---|
| مدیر | admin@sh.local |
| کارشناس SEO | seo@sh.local |
| مدرس | teacher@sh.local |
| دانش‌آموز | student@sh.local |
| والد | parent@sh.local |

رمز هر پنج حساب، همان مقدار SHIKHAN_DEMO_PASSWORD است و فقط وقتی هر دو فلگ نمایش و ساخت فعال باشند روی صفحه ورود دیده می‌شود. این رمز را پس از جلسه تغییر دهید.

برای پایان نمایش، هر دو فلگ را false کنید و تنظیمات را دوباره cache کنید. حساب‌های نمایشی باقی‌مانده را نیز غیرفعال کنید؛ خاموش‌کردن کارت‌ها به‌تنهایی حساب‌ها را حذف یا غیرفعال نمی‌کند.

## حساب اختصاصی SEO

حساب SEO از مسیر متغیرهای محیطی مستقل ساخته یا به‌روزرسانی می‌شود و هیچ‌وقت در کارت‌های ورود عمومی قرار نمی‌گیرد:

```dotenv
SHIKHAN_SEO_NAME="کارشناس سئو"
SHIKHAN_SEO_EMAIL=<ایمیل اختصاصی اپراتور SEO>
SHIKHAN_SEO_PASSWORD=<رمز مستقل و قوی حداقل ۱۲ کاراکتری>
```

سپس:

```bash
php artisan config:clear
php artisan db:seed --class=RolePermissionSeeder --force
php artisan config:cache
```

این seeder فقط نقش seo و مجوز seo.manage را به آن حساب می‌دهد. رمز را در Git، مستندات عمومی یا صفحه ورود قرار ندهید. پس از ساخت حساب، ورود را با همان ایمیل و رمز محیطی انجام دهید.