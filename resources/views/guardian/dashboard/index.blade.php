@extends('layouts.parent')

@section('title', 'پنل اولیا')

@section('content')
@php
    $faNumber = static fn ($value, int $precision = 0): string =>
        \App\Support\PersianDate::toPersianDigits(number_format((float) $value, $precision, '٫', '٬'));

    $presentCount = (int) ($attendance_breakdown['present'] ?? 0);
    $lateCount = (int) ($attendance_breakdown['late'] ?? 0);
    $absentCount = (int) ($attendance_breakdown['absent'] ?? 0);
    $trackedAttendance = $presentCount + $lateCount + $absentCount;
    $presentPercent = $trackedAttendance > 0 ? (int) round(($presentCount + $lateCount) / $trackedAttendance * 100) : null;
    $presentAngle = $trackedAttendance > 0 ? ($presentCount / $trackedAttendance * 360) : 0;
    $lateAngle = $trackedAttendance > 0 ? ($lateCount / $trackedAttendance * 360) : 0;
    $absentAngle = $trackedAttendance > 0 ? ($absentCount / $trackedAttendance * 360) : 0;
    $calendarStatus = $attendance_calendar->groupBy(fn ($record) => $record->session_date->format('j'))
        ->map(fn ($records) => $records->contains('status', 'absent') ? 'absent' : ($records->contains('status', 'late') ? 'late' : 'present'));
    $calendarOffset = (now()->startOfMonth()->dayOfWeekIso + 1) % 7;
    $calendarDays = now()->daysInMonth;
    $chartResults = $result_trend->take(6);
    $homeworkMonths = collect(range(5, 0))->map(fn ($offset) => now()->subMonths($offset));
    $maxHomeworkCount = max(1, (int) $homework_trend->map(fn ($counts) => array_sum($counts))->max());
    $meetingLabels = ['requested' => 'در انتظار تأیید', 'proposed' => 'زمان پیشنهادی', 'scheduled' => 'زمان‌بندی‌شده', 'completed' => 'برگزارشده', 'cancelled' => 'لغوشده', 'declined' => 'ردشده'];
@endphp

<section class="c" id="overview" data-page-section="home">
    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
        <div class="av">{{ mb_substr(auth()->user()->name ?? 'و', 0, 1) }}</div>
        <div style="flex:1;min-width:180px">
            <h2 style="margin:0">سلام، {{ auth()->user()->name }}!</h2>
            @if($selected_child)
                <p style="color:var(--mu);margin:4px 0 0">وضعیت آموزشی {{ $selected_child->name }}@if($selected_child->grade) · پایه {{ $selected_child->grade }}@endif</p>
            @else
                <p style="color:var(--mu);margin:4px 0 0">هنوز دانش‌آموز فعالی به این حساب متصل نشده است.</p>
            @endif
        </div>
        @if($selected_child)
            <a class="b" href="{{ route('guardian.meetings.index') }}">درخواست جلسه</a>
            <a class="b s" href="{{ route('guardian.messages.create') }}">پیام به مدرس</a>
        @endif
    </div>
</section>

@if($selected_child)
<section class="g g4" aria-label="خلاصه وضعیت آموزشی" data-page-section="home">
    <div class="st"><b>{{ $faNumber($course_progress->count()) }}</b><span>دوره فعال</span><small>دوره‌های قابل مشاهده</small></div>
    <div class="st bl"><b>{{ $faNumber($pending_homework_count) }}</b><span>تکلیف باز</span><small>بدون ارسال ثبت‌شده</small></div>
    <div class="st gn"><b>{{ $presentPercent === null ? '—' : $faNumber($presentPercent).'٪' }}</b><span>نرخ حضور</span><small>{{ $faNumber($trackedAttendance) }} جلسه ثبت‌شده</small></div>
    <div class="st am"><b>{{ $faNumber($results->count()) }}</b><span>نتیجه آزمون</span><small>نتایج مجاز برای مشاهده</small></div>
</section>

<section class="g g2">
    <section class="c chart-card" data-page-section="home">
        <h3>روند نمرات ثبت‌شده</h3>
        @if($chartResults->isNotEmpty())
            <div class="bars" role="img" aria-label="نمودار نمرات آزمون‌های ثبت‌شده">
                @foreach($chartResults as $result)
                    @php($ratio = $result->max_score > 0 ? max(0, min(100, ((float) $result->score / (float) $result->max_score) * 100)) : 0)
                    <div title="{{ $result->exam?->title ?? 'آزمون' }}: {{ $faNumber($result->score, 2) }} از {{ $faNumber($result->max_score, 2) }}">
                        <i style="height:{{ max(4, $ratio) }}%"></i>
                        <small>{{ $faNumber($result->score, 1) }}</small>
                    </div>
                @endforeach
            </div>
            <div class="leg"><span><i style="background:var(--pri)"></i>نمره واقعی آزمون‌های منتشرشده</span></div>
        @else
            <div class="empty">هنوز نمره قابل نمایشی برای این فرزند ثبت نشده است.</div>
        @endif
    </section>

    <section class="c chart-card" data-page-section="home">
        <h3>روند تحویل تکالیف در شش ماه اخیر</h3>
        @if($homework_trend->isNotEmpty())
            <div class="bars" role="img" aria-label="نمودار واقعی وضعیت تحویل تکالیف">
                @foreach($homeworkMonths as $month)
                    @php($monthCounts = $homework_trend->get($month->format('Y-m'), ['on_time' => 0, 'late' => 0, 'missing' => 0]))
                    @php($monthTotal = array_sum($monthCounts))
                    <div title="به‌موقع {{ $faNumber($monthCounts['on_time']) }} · با تأخیر {{ $faNumber($monthCounts['late']) }} · انجام‌نشده {{ $faNumber($monthCounts['missing']) }}">
                        <i style="height:{{ $monthTotal > 0 ? max(4, ($monthTotal / $maxHomeworkCount) * 100) : 0 }}%;background:var(--pri)"></i>
                        <small>{{ \App\Support\PersianDate::format($month, 'M') }}</small>
                    </div>
                @endforeach
            </div>
            <div class="leg"><span><i style="background:var(--pri)"></i>تکالیف ثبت‌شده در هر ماه</span></div>
        @else
            <div class="empty">برای شش ماه اخیر داده‌ای از تکالیف و ارسال‌ها ثبت نشده است.</div>
        @endif
    </section>

    <section class="c" data-page-section="home">
        <h3>وضعیت حضور و غیاب</h3>
        @if($trackedAttendance > 0)
            <div class="donut">
                <svg viewBox="0 0 120 120" role="img" aria-label="ترکیب وضعیت حضور و غیاب">
                    <circle cx="60" cy="60" r="42" fill="none" stroke="var(--ln)" stroke-width="16"/>
                    <circle cx="60" cy="60" r="42" fill="none" stroke="var(--ok)" stroke-width="16" stroke-dasharray="{{ 263.89 * $presentCount / $trackedAttendance }} 263.89" transform="rotate(-90 60 60)"/>
                    <circle cx="60" cy="60" r="42" fill="none" stroke="var(--am)" stroke-width="16" stroke-dasharray="{{ 263.89 * $lateCount / $trackedAttendance }} 263.89" stroke-dashoffset="-{{ 263.89 * $presentCount / $trackedAttendance }}" transform="rotate(-90 60 60)"/>
                    <circle cx="60" cy="60" r="42" fill="none" stroke="var(--rd)" stroke-width="16" stroke-dasharray="{{ 263.89 * $absentCount / $trackedAttendance }} 263.89" stroke-dashoffset="-{{ 263.89 * ($presentCount + $lateCount) / $trackedAttendance }}" transform="rotate(-90 60 60)"/>
                    <text x="60" y="62" text-anchor="middle" class="big">{{ $faNumber($presentPercent) }}٪</text>
                    <text x="60" y="78" text-anchor="middle" class="ax">حضور</text>
                </svg>
                <ul class="lg">
                    <li><i style="background:var(--ok)"></i>حاضر <b>{{ $faNumber($presentCount) }}</b></li>
                    <li><i style="background:var(--am)"></i>با تأخیر <b>{{ $faNumber($lateCount) }}</b></li>
                    <li><i style="background:var(--rd)"></i>غایب <b>{{ $faNumber($absentCount) }}</b></li>
                </ul>
            </div>
        @else
            <div class="empty">هنوز رکورد حضور و غیابی ثبت نشده است.</div>
        @endif
    </section>
</section>

<section class="g g2">
    <section class="c" id="attendance" data-page-section="attendance">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap">
            <h3>تقویم حضور و غیاب · {{ \App\Support\PersianDate::format(now(), 'F Y') }}</h3>
            <span class="bd bl">{{ $faNumber($attendance_count) }} جلسه</span>
        </div>
        <div class="calendar" aria-label="تقویم حضور و غیاب ماه جاری">
            @foreach(['ش','ی','د','س','چ','پ','ج'] as $weekday)<small class="wk">{{ $weekday }}</small>@endforeach
            @for($blank = 0; $blank < $calendarOffset; $blank++)<span></span>@endfor
            @for($day = 1; $day <= $calendarDays; $day++)
                @php($status = $calendarStatus->get((string) $day))
                <span class="day {{ $status ?? '' }} {{ $day === now()->day ? 'today' : '' }}" title="{{ $status === 'present' ? 'حاضر' : ($status === 'late' ? 'با تأخیر' : ($status === 'absent' ? 'غایب' : 'بدون رکورد')) }}">{{ $faNumber($day) }}</span>
            @endfor
        </div>
        <div class="leg">
            <span><i style="background:var(--ok)"></i>حاضر</span>
            <span><i style="background:var(--am)"></i>تأخیر</span>
            <span><i style="background:var(--rd)"></i>غایب</span>
        </div>
    </section>

    <section class="c" id="classes" data-page-section="home">
        <h3>پیشرفت دوره‌ها و درس‌ها</h3>
        @forelse($course_progress as $course)
            <div class="row">
                <div><strong>{{ $course->title }}</strong><small>{{ $faNumber($course->completed_lessons) }} از {{ $faNumber($course->total_lessons) }} درس تکمیل‌شده</small></div>
                <b>{{ $faNumber($course->progress_percent) }}٪</b>
            </div>
            <div class="pr" style="margin:0 0 12px"><u style="width:{{ max(0,min(100,(int) $course->progress_percent)) }}%"></u></div>
        @empty
            <div class="empty">برای این فرزند دوره فعال یا درس منتشرشده‌ای ثبت نشده است.</div>
        @endforelse
        <h3 style="margin-top:16px">کلاس‌های ثبت‌شده</h3>
        @forelse($classrooms as $classroom)
            <div class="row"><div><strong>{{ $classroom->name }}</strong><small>{{ $classroom->course?->title ?? 'کلاس مستقل' }} · مدرس: {{ $classroom->teacher?->name ?? 'تعیین نشده' }}</small></div><span class="bd gn">فعال</span></div>
        @empty
            <div class="empty">کلاس فعالی برای این فرزند ثبت نشده است.</div>
        @endforelse
    </section>
</section>

<section class="g g2">
    <section class="c" id="exams" data-page-section="exams">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px"><h3>آزمون‌ها و نتایج</h3><span class="bd pk">{{ $faNumber($results->count()) }} نتیجه</span></div>
        @forelse($results as $result)
            <div class="row">
                <div><strong>{{ $result->exam?->title ?? 'آزمون' }}</strong><small>{{ $result->exam?->course?->title ?? $result->exam?->classroom?->name ?? '—' }} · {{ \App\Support\PersianDate::format($result->synced_at ?? $result->created_at, 'Y/m/d') }}</small></div>
                <b>{{ $result->score === null ? '—' : $faNumber($result->score, 2).' / '.$faNumber($result->max_score, 2) }}</b>
            </div>
            @if(filled($result->feedback))<p style="color:var(--mu);font-size:12px">بازخورد مدرس: {{ $result->feedback }}</p>@endif
        @empty
            <div class="empty">نتیجه‌ای برای نمایش وجود ندارد.</div>
        @endforelse
    </section>

    <section class="c" id="homework" data-page-section="homework">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px"><h3>تکالیف نیازمند پیگیری</h3><span class="bd am">{{ $faNumber($pending_homework_count) }} باز</span></div>
        @forelse($homeworks as $homework)
            <div class="row">
                <div><strong>{{ $homework->title }}</strong><small>{{ $homework->classroom?->name ?? '—' }} · مهلت: {{ $homework->due_at ? \App\Support\PersianDate::format($homework->due_at, 'Y/m/d H:i') : 'بدون مهلت' }}</small></div>
                @if($homework->selected_student_submission_count > 0)<span class="bd gn">ارسال شده</span>@else<span class="bd am">در انتظار ارسال</span>@endif
            </div>
        @empty
            <div class="empty">تکلیف منتشرشده‌ای برای این فرزند وجود ندارد.</div>
        @endforelse
    </section>
</section>

<section class="c" id="homework-reviews" data-page-section="homework">
    <h3>نتایج بررسی تکالیف</h3>
    @forelse($reviewed_homework as $submission)
        <div class="row">
            <div><strong>{{ $submission->homework?->title ?? 'تکلیف' }}</strong><small>{{ $submission->homework?->classroom?->name ?? 'کلاس' }} · {{ $submission->reviewer?->name ?? 'مدرس' }} · {{ \App\Support\PersianDate::format($submission->reviewed_at, 'Y/m/d H:i') }}</small></div>
            @if($submission->score !== null)<b>{{ $faNumber($submission->score, 2) }}</b>@endif
        </div>
        @if(filled($submission->feedback))<p style="color:var(--mu)">{{ $submission->feedback }}</p>@endif
    @empty
        <div class="empty">بازخورد بررسی‌شده‌ای برای تکالیف ثبت نشده است.</div>
    @endforelse
</section>

<section class="g g2">
    <section class="c" id="messages" data-page-section="messages">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap">
            <h3>پیام به مدرس</h3>
            <a class="b" href="{{ route('guardian.messages.create', ['child' => $selected_child->id]) }}">پیام جدید</a>
        </div>
        @forelse($messages as $message)
            <a class="row" href="{{ route('guardian.messages.show', ['message' => $message, 'child' => $selected_child->id]) }}" style="text-decoration:none;color:inherit">
                <div><strong>{{ $message->subject }}</strong><small>{{ $message->sender?->name ?? 'فرستنده' }} · {{ \App\Support\PersianDate::format($message->created_at, 'Y/m/d H:i') }}</small></div>
                <span class="bd {{ $message->status === 'closed' ? 'bl' : 'gn' }}">{{ $message->replies->count() ? $faNumber($message->replies->count()).' پاسخ' : 'بدون پاسخ' }}</span>
            </a>
        @empty
            <div class="empty">هنوز گفت‌وگویی برای این فرزند ثبت نشده است.</div>
        @endforelse
        <a class="b s" href="{{ route('guardian.messages.index', ['child' => $selected_child->id]) }}">مشاهده همه پیام‌ها</a>
    </section>
    <section class="c" id="meetings" data-page-section="meetings">
        <h3>جلسات مشاوره و ملاقات</h3>
        @forelse($meetings as $meeting)
            <div class="row">
                <div><strong>{{ $meeting->title }}</strong><small>{{ $meeting->classroom?->name ?? 'عمومی' }} · {{ $meeting->starts_at ? \App\Support\PersianDate::format($meeting->starts_at, 'Y/m/d H:i') : ($meeting->preferred_at ? 'زمان پیشنهادی: '.\App\Support\PersianDate::format($meeting->preferred_at, 'Y/m/d H:i') : 'زمان پس از بررسی اعلام می‌شود') }}</small></div>
                <span class="bd {{ in_array($meeting->status, ['scheduled','completed'], true) ? 'gn' : 'am' }}">{{ $meetingLabels[$meeting->status] ?? $meeting->status }}</span>
            </div>
        @empty
            <div class="empty">هنوز جلسه‌ای ثبت نشده است.</div>
        @endforelse
        <a class="b" href="{{ route('guardian.meetings.index') }}">ثبت یا پیگیری جلسه</a>
    </section>
</section>

<section class="g g2">
    <section class="c" id="resources" data-page-section="home">
        <h3>منابع آموزشی</h3>
        @forelse($resources as $resource)
            <div class="row">
                <div><strong>{{ $resource->title }}</strong><small>{{ $resource->course?->title ?? $resource->classroom?->name ?? $resource->kind }}</small></div>
                @if(! $resource->is_free)
                    <span class="bd am">نیازمند خرید</span>
                @elseif($resource->file_path)
                    <span class="bd">دریافت مستقیم غیرفعال است</span>
                @else
                    <span class="bd gn">رایگان</span>
                @endif
            </div>
        @empty
            <div class="empty">منبع آموزشی منتشرشده‌ای در دسترس نیست.</div>
        @endforelse
    </section>
    <section class="c" id="announcements" data-page-section="home">
        <h3>اطلاعیه‌های آکادمی</h3>
        @forelse($announcements as $announcement)
            <div class="row"><div><strong>{{ $announcement->title }}</strong><small>{{ \App\Support\PersianDate::format($announcement->published_at, 'Y/m/d H:i') }}</small>@if(filled($announcement->body))<p style="color:var(--mu)">{{ \Illuminate\Support\Str::limit(strip_tags((string) $announcement->body), 180) }}</p>@endif</div></div>
        @empty
            <div class="empty">اطلاعیه‌ای برای نمایش وجود ندارد.</div>
        @endforelse
    </section>
</section>
@else
<section class="c">
    <h2>حساب اولیا هنوز به دانش‌آموزی متصل نشده است</h2>
    <p style="color:var(--mu)">برای اتصال فرزند با پشتیبانی آکادمی تماس بگیرید. تا آن زمان هیچ داده آموزشی ساختگی نمایش داده نمی‌شود.</p>
</section>
@endif
@endsection
