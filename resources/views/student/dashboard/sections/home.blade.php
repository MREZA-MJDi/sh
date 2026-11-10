<section class="page on" id="s-home">
    <div class="hero"><h2>سلام {{ auth()->user()->name }}!</h2><p>وضعیت کلاس‌ها، تکالیف و فعالیت‌های آموزشی تو در یک نگاه.</p>
        @if($classrooms->isNotEmpty())<a class="btn" href="#s-classes">ورود به کلاس‌ها</a>@endif
    </div>
    <div class="st">
        <a class="s c1" href="#s-classes"><small>کلاس‌های فعال</small><b>{{ $faNumber($classroom_count) }}</b><span>کلاس ثبت‌شده</span></a>
        <a class="s c2" href="#s-hw"><small>تکالیف باز</small><b>{{ $faNumber($pending_homework_count) }}</b><span>در انتظار ارسال</span></a>
        <a class="s c3" href="#s-exams"><small>نتایج اخیر</small><b>{{ $faNumber($results->count()) }}</b><span>نتیجه قابل مشاهده</span></a>
        <a class="s c4" href="#s-news"><small>اخبار مهم</small><b>{{ $faNumber($announcements->count()) }}</b><span>اطلاعیه منتشرشده</span></a>
    </div>
    @include('student.dashboard.sections.news', ['preview' => true])
</section>
