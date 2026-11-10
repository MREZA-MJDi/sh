<section class="page on" id="p-home">
    <div class="hero"><h2>کارتابل اولیاء</h2>
        @if($selected_child)<p>خلاصه وضعیت {{ $selected_child->name }}@if($selected_child->grade) · {{ $selected_child->grade }}@endif در یک نگاه</p><a class="btn" href="#p-consult">درخواست جلسه مشاوره</a>
        @else<p>هنوز دانش‌آموزی به این حساب متصل نشده است. برای اتصال فرزند با پشتیبانی آکادمی تماس بگیرید.</p>@endif
    </div>
    <div class="st">
        <a class="s c1" href="#p-progress"><small>کلاس‌های فعال</small><b>{{ $faNumber($classroom_count) }}</b><span>دوره‌های ثبت‌نام‌شده</span></a>
        <a class="s c2" href="#p-hw"><small>تکالیف باز</small><b>{{ $faNumber($pending_homework_count) }}</b><span>نیازمند پیگیری</span></a>
        <a class="s c3" href="#p-exams"><small>نتایج آزمون</small><b>{{ $faNumber($results->count()) }}</b><span>ثبت‌شده در سامانه</span></a>
        <a class="s c4" href="#p-att"><small>حضور</small><b>{{ $attendance_rate === null ? '—' : $faNumber($attendance_rate).'٪' }}</b><span>از جلسات ثبت‌شده</span></a>
    </div>
    @include('guardian.dashboard.sections.news', ['preview' => true])
</section>
