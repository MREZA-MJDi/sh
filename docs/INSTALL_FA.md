# نصب بسته Learning (دوره، تیزر، ثبت‌نام، لایسنس)

> این کد بدون دیدن فایل‌های پروژه نوشته شده. اسم‌های فرضی در انتها فهرست شده؛ قبل از اجرا با پروژه تطبیق بده.

## ۱) کپی فایل‌ها
همه پوشه‌ها را روی ریشه پروژه کپی کن (ساختار پوشه‌ها همان ساختار Laravel است).
`CourseSection` و `CourseLesson` جایگزین نسخه قبلی می‌شوند.

## ۲) ثبت چیزها
- `bootstrap/providers.php`: اضافه کن `App\Providers\LearningServiceProvider::class,`
- انتهای `routes/web.php`: `require __DIR__.'/learning.php';`
- مدل `Course`: `use \App\Models\Concerns\HasLearningAccess;` و اگر `getTeaserUrlAttribute/getTeaserPosterUrlAttribute` را قبلاً دستی نوشته‌ای حذفشان کن (داخل trait هستند). رابطه `sections()` از قبل لازم است.
- `$fillable` مدل Course: `access_type, price, is_published, external_course_id, teaser_*`.
- مدل `User`: رابطه‌ای لازم نیست.

## ۳) اجرا
```bash
php artisan migrate
php artisan storage:link
sudo apt install ffmpeg
```
در `.env`:
```
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SPOTPLAYER_APP_URL=            # لینک دانلود اپ (اختیاری)
LEARNING_PLAYER_MODE=license   # embed فقط بعد از تحویل رسمی نسخه وب
```
در `config/queue.php` برای اتصال redis: `'retry_after' => 3700` (بزرگ‌تر از timeout جاب ویدیو).

## ۴) ورکرها
`deploy/supervisor-learning.conf` را کپی و فعال کن:
```bash
sudo supervisorctl reread && sudo supervisorctl update
```
بعد از هر deploy: `php artisan queue:restart`

## ۵) Nginx / PHP
- `deploy/nginx-learning.conf` را داخل server سایت بگذار.
- آپلود تکه‌تکه ۵ مگابایتی است؛ لازم نیست `upload_max_filesize` را بالا ببری (حداقل ۶ مگ باشد).

## ۶) پاک‌سازی تکه‌های رهاشده (routes/console.php)
```php
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Schedule::call(function () {
    $disk = Storage::disk('local');
    foreach ($disk->directories('chunks') as $dir) {
        if ($disk->lastModified($dir) < now()->subDay()->timestamp) $disk->deleteDirectory($dir);
    }
})->daily();
```

## ۷) صفحه ادمین (curriculum.blade.php)
- بخش قدیمی «ویدیوی تیزر» و فرم آپلودش را حذف کن و بگذار: `@include('admin.courses._teaser_box')`
- داخل `<details>` ویرایش هر درس، زیر `_lesson_form` بگذار: `@include('admin.courses._lesson_video', ['lesson' => $lesson])`
- `CourseTeaserController` قبلی و route‌های `courses.teaser.update/destroy` قبلی را حذف کن (جایگزین در `LearningAdminController` است).
- فرم ویرایش دوره: فیلد «نوع دسترسی» (`access_type`: free/paid)، «قیمت» (`price`) و «External Course ID». هنگام انتشار:
```php
if ($request->boolean('is_published') && ($problems = $course->fill($data)->publishProblems())) {
    return back()->withErrors(['is_published' => implode(' ', $problems)])->withInput();
}
```

## ۸) وصل کردن پرداخت
۱. در شروع پرداخت (route با نام `payment.start` یا هر اسم دیگری که در `config/learning.php` گذاشتی) Authority را ذخیره کن: `$enrollment->update(['payment_ref' => $authority]);`
۲. داخل Callback، **بعد از verify موفق درگاه**:
```php
$enrollment = Enrollment::where('payment_ref', $authority)->firstOrFail();
app(\App\Services\Learning\EnrollmentService::class)
    ->activateAfterPayment($enrollment, $verifiedAmount); // واحد مبلغ = واحد ستون price
return redirect()->route('courses.show', $enrollment->course->slug);
```
فعال‌سازی idempotent است؛ رفرش یا تکرار Callback لایسنس دوم نمی‌سازد.

## ۹) وقتی API رسمی SpotPlayer رسید
یک کلاس بنویس که `App\Services\Learning\LearningProvider` را پیاده کند (`issueLicense` و `uploadVideo`) و در `LearningServiceProvider` به‌جای `PendingContractProvider` bind کن. بعدش:
- لایسنس‌های `waiting_contract` را با route `admin.licenses.retry` دوباره به صف بده.
- ویدیوهای `waiting_contract` را با `admin.lessons.video.retry`.

## فرض‌هایی که باید چک کنی
| فرض | اگر فرق داشت |
|---|---|
| layout عمومی `layouts.app` با `@yield('content')` و `@yield('title')` | اسم را در `courses/*.blade.php` عوض کن |
| مدل‌های `App\Models\User` و `Course`، جدول `courses` با `slug` و `title` یا `name` | |
| route لاگین با نام `login` | |
| مجوز ادمین `courses.manage` | در `routes/learning.php` عوض کن |
| جدول `enrollments` قبلاً وجود ندارد | اگر دارد، migration دست نمی‌زند؛ ستون‌ها را با مدل `Enrollment` تطبیق بده |
| ستون انتشار `is_published` | `scopePublished` در trait را عوض کن |
| واحد `price` تومان است (در JSON-LD ×۱۰ می‌شود به ریال) | |

---
## اضافه‌های نسخه ۲ (نوع دوره، کلاس آنلاین، حضور و غیاب)
migration دوم (`..._000002_course_types_and_attendance.php`) را هم اجرا کن و `docs/FILAMENT_AND_TYPES_FA.md` را بخوان.
فایل‌های جدید: `Enums/CourseType`, `ClassSession`, `Attendance`, `LiveClassService`, `AttendanceImporter`, `SpotPlayerProvider`, `JoinClassController`, کامپوننت `course-type-badge`.
`_access_box.blade.php` و `routes/learning.php` و `config/learning.php` نسبت به نسخه قبل به‌روز شده‌اند (جایگزین کن).
