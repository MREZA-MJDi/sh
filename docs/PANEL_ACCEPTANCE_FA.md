# گزارش بررسی کارتابل‌ها و صفحات عمومی

مبنای بررسی نسخهٔ کد بعد از `f7f7a888` و دادهٔ آزمایشی محلی است. برای پنل‌ها صفحات با حساب نقش مربوط از مسیر Laravel باز شدند. بررسی عمومی VPS با درخواست HTTP بدون ورود انجام شد. رندر مرورگر، نمایش در اندازه‌های مختلف و نتیجهٔ عملیات احرازشده روی VPS هنوز باید جداگانه ثبت شوند.

## هویت و جریان داده

| بخش | هویت و رابطه | گردش بررسی‌شده |
|---|---|---|
| مدیر | مدیریت دوره، کلاس و کاربران طبق مجوز. دوره به مدرس، فصل، کلاس و ثبت‌نام وصل است. | داشبورد و LMS، دوره و محتوا، تقویم کلاس، حضور، تکلیف، آزمون و نتایج، ثبت‌نام، منابع، ویدئو، درخواست‌های اولیا، پیام‌ها و اعلان‌ها باز شدند. |
| مدرس | دامنهٔ داده از کلاس یا دورهٔ تخصیص‌یافته می‌آید. دانش‌آموز و اولیا از رابطهٔ آموزشی مربوط پیدا می‌شوند. | خانه، پروفایل، کلاس، جلسات آنلاین، حضور، تکلیف، منابع، ویدئو، پیام و مشاوره باز شدند. پاسخ به پیام پس از بستن گفتگو توسط مدیر رد می‌شود. |
| دانش‌آموز | پروندهٔ شخصی، عضویت کلاس و دسترسی دوره مبنای دیدن محتواست. پاسخ تکلیف و تلاش آزمون به حساب همان دانش‌آموز وصل‌اند. | خانه، پروفایل، کلاس آنلاین، جلسات، حضور با دادهٔ نمونه، تکلیف، منابع و کتابخانه، دوره و ویدئو، آزمون و نتیجه، دستاورد، یادداشت و پیام بررسی شدند. |
| اولیا | فرزند باید به حساب ولی وصل باشد. ثبت‌نام و کلاس فرزند محدودهٔ نمایش داده را مشخص می‌کنند. | خانه، حضور، نتیجهٔ آزمون، تکلیف، منابع، جلسه، فهرست و جزئیات مشاوره و ارسال و مشاهدهٔ پیام بررسی شدند. گفت‌وگوی بسته در فیلتر «بسته» دیده می‌شود و فرم پاسخ ندارد. |
| سئو | مجوز `seo.manage` مستقل از نقش مدیر آموزشی است. دادهٔ منتشرشده از مسیرهای عمومی مصرف می‌شود. | داشبورد سئو، بنرها، مقاله‌ها، پرسش صفحهٔ اول و نظرات باز شدند. همان مسیرها برای حساب مدیر آموزشی فاقد مجوز `403` دادند. |
| عمومی | بازدیدکننده دوره‌ها، مدرسان و مقاله‌های منتشرشده را می‌بیند. ورود به دادهٔ پنل نیازمند نشست و مجوز نقش است. | صفحهٔ اصلی، فهرست و جزئیات دوره و مدرس، مقالات، تماس، سبد، ورود، ثبت‌نام و sitemap بررسی شدند. |

## یافته‌ها

- مدیر وضعیت بسته را در `closed_at` ثبت می‌کرد. برخی مسیرها فقط فیلد `status` را می‌خواندند و اجازهٔ ثبت پاسخ یا نمایش فرم می‌دادند. وضعیت مؤثر یکجا تعریف شد و ثبت پاسخ و تغییر وضعیت گفتگو در یک تراکنش انجام می‌شوند. کد، گفتگوهای قدیمی با وضعیت `closed` را هم تشخیص می‌دهد.
- روی VPS، `/robots.txt` پاسخ `404` داد. همان مسیر در نسخهٔ محلی پاسخ `200` داد. نسخهٔ مستقر و پیکربندی وب‌سرور باید بررسی شوند تا علت مشخص شود.
- روی VPS صفحات اصلی، دوره‌ها، مدرسان، مقالات، تماس، سبد، ورود، ثبت‌نام، sitemap اصلی، چهار بخش sitemap و یک نمونه جزئیات دوره، مدرس و مقاله پاسخ `200` دادند. این بررسی فقط پاسخ صفحه را تأیید می‌کند.

## پذیرش باقی‌مانده روی VPS

- ورود و خروج هر نقش با حساب QA، بررسی منو و فرم‌ها در موبایل و دسکتاپ، و آزمون رد دسترسی با حساب نامرتبط.
- ساخت و اتصال حساب‌ها به کلاس و ولی، ایجاد دوره، جلسه و حضور، ارسال تکلیف و ثبت بازخورد، اجرای آزمون داخلی و مشاهدهٔ نتیجه برای نقش‌های مجاز.
- بررسی درخواست حضور دانش‌آموز با سابقهٔ واقعی و جمع‌آوری لاگ همان درخواست در صورت تکرار خطای ۵۰۰.
- آپلود، پردازش و پخش ویدئوی خصوصی، دانلود جزوه، ثبت و مشاهدهٔ محصول، جلسهٔ سراسری اولیا و اتصال MediaAsset به ویرایشگر درس.
- ذخیره و انتشار مقاله یا بنر با حساب سئو و مشاهدهٔ نتیجه در سایت، رفتار واقعی فرم‌های عمومی و رفع پاسخ ۴۰۴ مسیر robots.
- سرویس‌های بیرونی مانند SpotPlayer، Quiz24، Skyroom و درگاه پرداخت در این بررسی محلی اجرا نشده‌اند.

## فهرست پاسخ صفحه‌ها در آزمون محلی

داده شامل دوره، کلاس، حساب‌های آزمایشی پنج نقش، پیام، مشاوره، ویدئو و تلاش آزمون بود. در این فهرست، `302` برای هدایت مسیر قدیمی، انتقال به صفحهٔ اصلی یک قابلیت یا پاسخ مسیر جست‌وجوی JSON به درخواست HTML دیده شد. مسیرهای فایل و پاسخ‌های نوشتنی در این فهرست صفحه‌ها نیامده‌اند.

### عمومی

- `home` · `200`
- `public.home.quiz.question` · `200`
- `public.courses.index` · `200`
- `public.teachers.index` · `200`
- `public.teachers.show` · `200`
- `public.contact` · `200`
- `public.cart` · `200`
- `public.courses.show` · `200`
- `seo.articles.index` · `200`
- `seo.articles.show` · `200`
- `seo.sitemap` · `200`
- `seo.sitemap.section` · `200`
- `seo.robots` · `200`
- `login` · `200`
- `register` · `200`

### مدیر

- `admin.login` · `302`
- `admin.dashboard` · `200`
- `admin.lms.index` · `200`
- `admin.lms.show` · `200`
- `admin.assignments.legacy` · `302`
- `admin.recordings.legacy` · `302`
- `admin.meetings.legacy` · `302`
- `admin.parent-messages.legacy` · `302`
- `admin.communication-center.index` · `200`
- `admin.contact-messages.index` · `200`
- `admin.seo.index` · `403`
- `admin.seo.banners.index` · `403`
- `admin.seo.articles.index` · `403`
- `admin.seo.quiz.index` · `403`
- `admin.seo.testimonials.index` · `403`
- `admin.lookups.students` · `302`
- `admin.students.index` · `200`
- `admin.teachers.index` · `200`
- `admin.parents.index` · `200`
- `admin.enrollments.index` · `200`
- `admin.payments.index` · `200`
- `admin.courses.create` · `200`
- `admin.courses.edit` · `200`
- `admin.courses.index` · `200`
- `admin.courses.content` · `200`
- `admin.classes.index` · `200`
- `admin.online-classes.index` · `200`
- `admin.classes.sessions.index` · `200`
- `admin.parent-meetings.lookup` · `302`
- `admin.parent-meetings.index` · `200`
- `admin.attendance.index` · `200`
- `admin.homework.index` · `200`
- `admin.exams.index` · `200`
- `admin.exams.results` · `200`
- `admin.exams.questions` · `200`
- `admin.exams.attempts` · `200`
- `admin.resources.index` · `200`
- `admin.consultations.index` · `200`
- `admin.messages.index` · `200`
- `admin.announcements.index` · `200`
- `admin.internal-notifications.index` · `200`
- `admin.spotplayer.index` · `200`
- `admin.quiz24.index` · `200`

### مدرس

- `teacher.dashboard` · `200`
- `teacher.profile.edit` · `200`
- `teacher.classes` · `200`
- `teacher.homework` · `200`
- `teacher.videos` · `200`
- `teacher.resources` · `200`
- `teacher.attendance` · `200`
- `teacher.live` · `200`
- `teacher.meetings` · `200`
- `teacher.messages.index` · `200`
- `teacher.consultations.index` · `200`
- `teacher.messages.recipients` · `302`
- `teacher.messages.create` · `200`

### دانش‌آموز

- `student.dashboard` · `200`
- `student.meetings.index` · `200`
- `student.homework.index` · `200`
- `student.attendance` · `200`
- `student.results` · `200`
- `student.resources.index` · `200`
- `student.library` · `200`
- `student.live-classes` · `200`
- `student.achievements` · `200`
- `student.profile.edit` · `200`
- `student.exams.index` · `200`
- `student.exams.show` · `200`
- `student.exams.attempt` · `200`
- `student.exams.result` · `200`
- `student.notes.index` · `200`
- `student.messages.index` · `200`
- `student.messages.create` · `200`
- `student.messages.show` · `200`
- `student.videos.index` · `200`
- `student.videos.show` · `200`
- `student.courses.index` · `200`
- `student.courses.show` · `302`

### اولیا

- `guardian.dashboard` · `200`
- `guardian.attendance` · `200`
- `guardian.exams` · `200`
- `guardian.homework` · `200`
- `guardian.resources.index` · `200`
- `guardian.meetings.index` · `200`
- `guardian.consultations.index` · `200`
- `guardian.consultations.create` · `200`
- `guardian.consultations.show` · `200`
- `guardian.messages.index` · `200`
- `guardian.messages.create` · `200`
- `guardian.messages.show` · `200`

### سئو

- `admin.seo.index` · `200`
- `admin.seo.banners.index` · `200`
- `admin.seo.articles.index` · `200`
- `admin.seo.quiz.index` · `200`
- `admin.seo.testimonials.index` · `200`

## پذیرش مرورگری نسخهٔ محلی

در Chromium با نسخهٔ ساخته‌شدهٔ فرانت‌اند، ۹۴ ترکیب نقش و صفحه در عرض‌های ۳۹۰ و ۱۴۴۰ پیکسل باز شدند. هر ۱۸۸ پاسخ صفحه موفق بود. در این اجرا خطای JavaScript هنگام بارگذاری یا سرریز افقی در سطح صفحه ثبت نشد. این اندازه‌گیری همراه با آزمون‌های عملی زیر انجام شد:

- فرم تماس پس از ارسال نام کوتاه، دلیل فارسی را کنار فیلد نشان داد و مقدار معتبر تماس را حفظ کرد.
- ارسال فایل متنی به فرم منبع ادمین به صفحهٔ همان گردش برگشت و فرمت‌های مجاز فایل را در پیام اعتبارسنجی نشان داد.
- در فرم دانش‌آموز، دکمه‌ای که ارسال را آغاز می‌کند و وضعیت کنترل‌های غیرفعالِ قبلی حفظ شدند.
- در حساب دانش‌آموز عضو کلاس، گزارش ماهانه علامت حضور ثبت‌شده را نشان داد.
- فایل WebM از فرم ادمین به صف نهایی‌سازی و سپس صف تبدیل رفت. عضو کلاس MP4 خصوصی را در مرورگر پخش کرد و دریافت بازهٔ بایت پاسخ `206` داد. حساب خارج از کلاس پاسخ `404` گرفت.
- صفحات خطا وضعیت HTTP را حفظ می‌کنند. برای اعتبارسنجی فرم، پاسخ فارسی و بازگشت به همان صفحه بررسی شد. برای خطای داخلی، جزئیات استثنا در صفحه نمایش داده نشد.

ورود مشترک QA از Chrome به نشانی آزمایشی با خطای `ERR_CERT_COMMON_NAME_INVALID` متوقف شد. این شواهد وضعیت محیط محلی را نشان می‌دهند. پذیرش احرازشده و عبور درخواست از وب‌سرور VPS جداگانه باز هستند.
