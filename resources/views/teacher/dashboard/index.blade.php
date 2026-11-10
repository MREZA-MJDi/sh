@extends('layouts.panel')

@section('page-title', 'داشبورد مدرس')
@section('page-description', 'فضای کاری کلاس‌ها و دانش‌آموزان تحت مسئولیت شما')

@section('content')
    <section class="panel-card panel-hero panel-section-anchor mb-4" id="overview">
        <div>
            <span class="text-xs text-indigo-100">خوش آمدید {{ auth()->user()->name }}</span>
            <h2>مدیریت آموزشی کلاس‌های شما</h2>
            <p>فقط داده‌های کلاس‌هایی نمایش داده می‌شوند که به عنوان مدرس به شما تخصیص یافته‌اند.</p>
        </div>
        <span class="panel-hero-meta">{{ now()->translatedFormat('l، d F Y') }}</span>
    </section>

    <section class="panel-grid panel-grid-4 mb-4">
        <article class="panel-card panel-stat" style="--stat-color:#5065d8;--stat-soft:#edf0ff"><div class="panel-stat-symbol">▣</div><span class="panel-stat-label">کلاس‌های فعال</span><strong class="panel-stat-value">{{ number_format($classroom_count) }}</strong><span class="panel-stat-note">کلاس‌های تخصیص‌یافته به شما</span></article>
        <article class="panel-card panel-stat" style="--stat-color:#0b9a89;--stat-soft:#e2f6f2"><div class="panel-stat-symbol">♙</div><span class="panel-stat-label">دانش‌آموزان</span><strong class="panel-stat-value">{{ number_format($student_count) }}</strong><span class="panel-stat-note">دانش‌آموزان یکتای کلاس‌های شما</span></article>
        <article class="panel-card panel-stat" style="--stat-color:#d18b11;--stat-soft:#fff5dc"><div class="panel-stat-symbol">✎</div><span class="panel-stat-label">تکالیف در انتظار بررسی</span><strong class="panel-stat-value">{{ number_format($pending_submission_count) }}</strong><span class="panel-stat-note">ارسال‌های ثبت‌شده با وضعیت در انتظار</span></article>
        <article class="panel-card panel-stat" style="--stat-color:#7b55c7;--stat-soft:#f1ebff"><div class="panel-stat-symbol">✓</div><span class="panel-stat-label">حضورهای ثبت‌شده امروز</span><strong class="panel-stat-value">{{ number_format((int) $attendance_by_status->sum()) }}</strong><span class="panel-stat-note">مجموع حضور، تأخیر و غیبت امروز</span></article>
    </section>

    @can('classes.view.assigned')
        <section class="panel-card panel-section-anchor mb-4" id="classes">
            <div class="panel-card-title"><div><h3>کلاس‌های من</h3><p>فهرست کلاس‌های فعال تخصیص‌یافته به این حساب</p></div></div>
            <div class="panel-grid panel-grid-3">
                @forelse ($classrooms as $classroom)
                    <article class="panel-subcard">
                        <strong>{{ $classroom->name }}</strong>
                        <span>{{ $classroom->course?->title ?? 'بدون دوره متصل' }}</span>
                        <small>{{ number_format($classroom->students_count) }} دانش‌آموز · {{ $classroom->course?->subject ?? 'موضوع ثبت نشده' }}</small>
                    </article>
                @empty
                    <div class="panel-empty">هنوز کلاسی به این حساب مدرس تخصیص داده نشده است.</div>
                @endforelse
            </div>
        </section>
    @endcan

    @can('courses.view.assigned')
        <section class="panel-card panel-section-anchor mb-4" id="courses">
            <div class="panel-card-title"><div><h3>دوره‌های من</h3><p>دوره‌هایی که مدرس آن هستید یا از طریق کلاس به شما تخصیص یافته‌اند</p></div></div>
            <div class="panel-table-wrap">
                <table class="panel-table">
                    <thead><tr><th>عنوان دوره</th><th>موضوع</th><th>پایه</th><th>تعداد کلاس مرتبط</th></tr></thead>
                    <tbody>
                    @forelse ($courses as $course)
                        <tr><td>{{ $course->title }}</td><td>{{ $course->subject ?? '—' }}</td><td>{{ $course->grade ?? '—' }}</td><td>{{ number_format($course->classrooms_count) }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="panel-empty">دوره‌ای برای نمایش وجود ندارد.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endcan

    @can('students.view.assigned')
        <section class="panel-card panel-section-anchor mb-4" id="students">
            <div class="panel-card-title"><div><h3>دانش‌آموزان کلاس‌های من</h3><p>فقط دانش‌آموزان متصل به کلاس‌های تحت مسئولیت شما</p></div></div>
            <div class="panel-list">
                @forelse ($students as $student)
                    <div class="panel-list-row"><div class="panel-avatar">{{ mb_substr($student->name, 0, 1) }}</div><div class="panel-list-main"><strong>{{ $student->name }}</strong><span>{{ $student->grade ?? 'پایه ثبت نشده' }}</span></div></div>
                @empty
                    <div class="panel-empty">در کلاس‌های شما دانش‌آموز فعالی ثبت نشده است.</div>
                @endforelse
            </div>
        </section>
    @endcan

    @can('attendance.view.assigned')
        <section class="panel-card panel-section-anchor mb-4" id="attendance">
            <div class="panel-card-title"><div><h3>گزارش حضور و غیاب امروز</h3><p>فقط رکوردهای کلاس‌های تخصیص‌یافته به شما</p></div></div>
            <div class="panel-grid panel-grid-3">
                <div class="panel-stat"><span class="panel-stat-label">حاضر</span><strong class="panel-stat-value">{{ number_format((int) $attendance_by_status->get('present', 0)) }}</strong></div>
                <div class="panel-stat"><span class="panel-stat-label">با تأخیر</span><strong class="panel-stat-value">{{ number_format((int) $attendance_by_status->get('late', 0)) }}</strong></div>
                <div class="panel-stat"><span class="panel-stat-label">غایب</span><strong class="panel-stat-value">{{ number_format((int) $attendance_by_status->get('absent', 0)) }}</strong></div>
            </div>
            @if ($classroom_count === 0)
                <div class="panel-empty">برای محاسبه گزارش، ابتدا باید کلاسی به شما تخصیص داده شود.</div>
            @endif
        </section>
    @endcan

    @can('homework.view.assigned')
        <section class="panel-card panel-section-anchor mb-4" id="homework">
            <div class="panel-card-title"><div><h3>ارسال‌های منتظر بررسی</h3><p>پاسخ‌های دانش‌آموزان کلاس‌های تحت مسئولیت شما</p></div><span class="panel-badge panel-badge-warning">{{ number_format($pending_submission_count) }} مورد</span></div>
            <div class="panel-table-wrap">
                <table class="panel-table" data-searchable-table>
                    <thead><tr><th>دانش‌آموز</th><th>تکلیف</th><th>کلاس</th><th>زمان ارسال</th></tr></thead>
                    <tbody>
                    @forelse ($submissions as $submission)
                        <tr>
                            <td>{{ $submission->student?->name ?? 'کاربر حذف‌شده' }}</td>
                            <td>{{ $submission->homework?->title ?? 'تکلیف حذف‌شده' }}</td>
                            <td>{{ $submission->homework?->classroom?->name ?? '—' }}</td>
                            <td>{{ $submission->submitted_at?->translatedFormat('Y/m/d H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="panel-empty">ارسال منتظر بررسی در کلاس‌های شما وجود ندارد.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endcan

    @can('announcements.view')
        <section class="panel-card panel-section-anchor mb-4" id="announcements">
            <div class="panel-card-title"><div><h3>اطلاعیه‌های آکادمی</h3><p>اطلاعیه‌های منتشرشده برای مدرسان</p></div></div>
            <div class="panel-list">
                @forelse ($announcements as $announcement)
                    <div class="panel-list-row"><div class="panel-avatar">⚑</div><div class="panel-list-main"><strong>{{ $announcement->title }}</strong><span>{{ $announcement->published_at?->diffForHumans() ?? '—' }}</span></div></div>
                @empty
                    <div class="panel-empty">اطلاعیه‌ای برای نمایش وجود ندارد.</div>
                @endforelse
            </div>
        </section>
    @endcan
@endsection
