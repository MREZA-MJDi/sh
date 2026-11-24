# پذیرش روی VPS آزمایشی

از ریشهٔ پروژه شروع کنید. دستور انتشار نسخه و بررسی صف‌ها در [راهنمای نصب](INSTALL_FA.md) آمده است. پس از انتشار، این دستورها را برای تشخیص نسخه، مسیر حضور و سلامت سرویس‌ها اجرا کنید.

```bash
git rev-parse --short HEAD
php artisan route:list --name=student.attendance
php artisan route:list --name=admin.uploads
php artisan migrate:status
php artisan queue:failed
sudo supervisorctl status
ffmpeg -version | head -n 1
ffprobe -version | head -n 1
```

## ساخت مجموعهٔ مستقل QA در Tinker

این بلوک را فقط روی نصب آزمایشی اجرا کنید. هر اجرا شش حساب جدید، یک دورهٔ منتشرشدهٔ آزمایشی، یک کلاس و یک رکورد حضور ایجاد می‌کند. دوره در فهرست دور‌ه‌های منتشرشده نمایش داده می‌شود. دسترسی دانش‌آموز از ثبت‌نام دستی می‌آید و هیچ پرداختی ایجاد نمی‌شود. حساب `outsider` برای بررسی رد دسترسی است.

گذرواژهٔ ۱۲ نویسه‌ای یا بلندتر را در ترمینال وارد کنید. در خروجی فقط ایمیل‌ها و شناسهٔ دوره و کلاس دیده می‌شوند.

```bash
(
  set -e
  export QA_RUN="$(date +%Y%m%d%H%M%S)-$(od -An -N3 -tx1 /dev/urandom | tr -d ' 
')"
  read -r -s -p 'Temporary QA password: ' QA_PASSWORD
  printf '\n'
  export QA_PASSWORD
  php artisan tinker --execute="$(cat <<'PHP'
DB::transaction(function () {
    $run = getenv("QA_RUN");
    $password = getenv("QA_PASSWORD");
    throw_unless($run && strlen((string) $password) >= 12, InvalidArgumentException::class, "Set QA_RUN and a password of at least 12 characters.");
    $accounts = [];
    foreach (["admin"=>"admin", "teacher"=>"teacher", "student"=>"student", "parent"=>"parent", "seo"=>"seo", "outsider"=>"student"] as $key => $role) {
        $account = App\Models\User::create([
            "name" => "QA ".$key." ".$run,
            "email" => "qa.".$key.".".$run."@example.invalid",
            "password" => $password,
            "is_active" => true,
        ]);
        $account->roles()->attach(App\Models\Role::where("name", $role)->sole());
        $accounts[$key] = $account;
    }
    $course = App\Models\Course::create([
        "title" => "دوره آزمایشی QA ".$run, "slug" => "qa-".$run,
        "teacher_id" => $accounts["teacher"]->id, "type" => "paid", "price" => 100000,
        "delivery_type" => "onsite", "status" => "published",
        "learning_features" => ["recorded_video"=>true, "assignments"=>true, "exams"=>true, "resources"=>true],
    ]);
    $classroom = App\Models\Classroom::create([
        "name" => "کلاس آزمایشی QA ".$run, "status" => "active", "course_id" => $course->id,
        "teacher_id" => $accounts["teacher"]->id, "created_by" => $accounts["admin"]->id,
    ]);
    $classroom->students()->attach($accounts["student"]->id);
    $accounts["parent"]->children()->attach($accounts["student"]->id);
    app(App\Services\Courses\CourseEnrollmentService::class)->grantManually($accounts["student"], $course, $accounts["admin"]);
    $session = App\Models\ClassSession::create([
        "classroom_id"=>$classroom->id, "title"=>"جلسه آزمایشی QA", "session_type"=>"onsite",
        "starts_at"=>today()->setHour(9), "ends_at"=>today()->setHour(10), "status"=>"completed",
        "created_by"=>$accounts["admin"]->id,
    ]);
    App\Models\Attendance::create([
        "classroom_id"=>$classroom->id, "class_session_id"=>$session->id, "student_id"=>$accounts["student"]->id,
        "session_date"=>today()->toDateString(), "status"=>"present", "marked_by"=>$accounts["admin"]->id,
    ]);
    dump(["course_id"=>$course->id, "classroom_id"=>$classroom->id, "emails"=>collect($accounts)->map->email->all()]);
});
PHP
)"
)
```

## آزمون حضور و ویدئو

۱. با حساب `student` به `/student/attendance` بروید. انتظار می‌رود «حاضر» در تقویم ماه فعلی دیده شود. اگر خطای ۵۰۰ دیدید، زمان درخواست و خطای همان زمان را از لاگ Laravel بررسی کنید.

۲. با حساب `admin`، دورهٔ ایجادشده را از `/admin/lms` باز کنید. در «محتوا و ضبط کلاس» یک WebM یا MP4 آزمایشی بارگذاری و **کلاس QA** را انتخاب کنید. نهایی‌سازی و تبدیل باید از صف‌های `upload-finalize` و `video` عبور کنند. اگر ویدئو در انتظار ماند، وضعیت کارگرها و خطاهای صف را بررسی کنید.

۳. با حساب `student` ویدئو را پخش کنید و با حساب `outsider` بررسی کنید که همان ویدئو در دسترس نباشد. برای مقایسه از دو نشست جدا استفاده کنید.

۴. فرم تماس را با یک نام تک‌حرفی آزمایش کنید. علت رد باید کنار فیلد دیده شود و مقدار معتبر تماس حفظ شود. همچنین یک نشانی ناموجود را باز کنید و نمایش صفحهٔ فارسی `404` را ثبت کنید.

نتیجه را همراه با شناسهٔ نسخه، نقش، مسیر و مرحلهٔ موفق یا ناموفق ثبت کنید. گذرواژه و اطلاعات واقعی کاربران را در گزارش وارد نکنید.
