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
    <div class="card"><div class="ch"><h2>آخرین اخبار مهم</h2><a class="btn s g" href="#s-news">همه</a></div>
        @forelse($announcements->take(2) as $announcement)<div class="news"><b>{{ $announcement->title }}</b><span class="mu">{{ \\App\\Support\\PersianDate::format($announcement->published_at, 'Y/m/d') }}</span></div>
        @empty<div class="empty">اطلاعیه‌ای برای نمایش وجود ندارد.</div>@endforelse
    </div>
</section>
