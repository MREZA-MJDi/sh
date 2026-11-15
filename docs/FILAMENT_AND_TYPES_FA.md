# نوع دوره، اسکای‌روم، حضور و غیاب و Filament

## نصب پکیج‌ها
```bash
composer require eamirgh/skyroom-laravel      # اسکای‌روم (پکیج جامعه؛ قبل از استفاده بررسی‌اش کن)
composer require abdal/spotplayer-php         # فقط وقتی API رسمی SpotPlayer تأیید شد
composer require filament/filament            # پنل CRUD (نسخه ۴ با Laravel 12)
php artisan filament:install --panels         # پنل جدا، مثلاً /panel
```
در `.env`: `SKYROOM_API_KEY`, `SKYROOM_BASE_URL=https://www.skyroom.online/skyroom/api/`, `SPOTPLAYER_API_KEY`

## مدل Course
```php
protected $casts = ['type' => \App\Enums\CourseType::class];
// $fillable: type, skyroom_room_id, location (+ موارد قبلی)
```
نمایش به کاربر: `<x-course-type-badge :type="$course->type" />` در کارت لیست و صفحه دوره.

## فرم Filament (بخش اصلی؛ namespaceها را با مستندات نسخه نصب‌شده چک کن)
```php
Select::make('type')->label('نوع')
    ->options(collect(CourseType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()]))
    ->live()->required(),
Radio::make('access_type')->label('دسترسی')->options(['free' => 'رایگان', 'paid' => 'پولی'])->live()->required(),
TextInput::make('price')->label('قیمت')->numeric()->visible(fn ($get) => $get('access_type') === 'paid'),
TextInput::make('external_course_id')->label('شناسه دوره در SpotPlayer')->visible(fn ($get) => $get('type') === 'recorded'),
TextInput::make('skyroom_room_id')->label('شناسه اتاق اسکای‌روم')->disabled()
    ->helperText('اولین بار خودکار ساخته می‌شود')->visible(fn ($get) => $get('type') === 'online_class'),
TextInput::make('location')->label('آدرس برگزاری')->visible(fn ($get) => $get('type') === 'in_person'),
```

## هنوز ننوشته‌ام
- Relation Manager «جلسه‌ها» با اکشن «ایمپورت گزارش اسکای‌روم» (فایل CSV → `AttendanceImporter::fromCsv`).
- جدول حضور و غیاب دستی (toggle برای هر دانش‌آموز).
- داشبورد دانش‌آموز با سه تب بر اساس نوع.

## نکات اسکای‌روم
- گزارش حضور و غیاب در بخش «نشست‌ها»ی پنل اسکای‌روم است و خروجی اکسل دارد؛ endpoint وب‌سرویس برای آن پیدا نکردم.
- مستندات می‌گوید `createLoginUrl` برای کلاس‌های هم‌زمان زیاد پرهزینه است و محدودیت نرخ دارد.
- `nickname` به شکل «نام (#شناسه)» ساخته می‌شود تا ایمپورت حضور و غیاب با regex تطبیق بدهد.
