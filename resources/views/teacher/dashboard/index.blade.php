@extends('admin.layouts.app')

@section('page-title', 'داشبورد مدرس')
@section('page-description', 'نمای واقعی کلاس‌ها، حضور، تکالیف و ارتباطات شما')

@section('content')
<div class="teacher-dashboard">
    <section class="teacher-dashboard__hero">
        <div>
            <span class="teacher-dashboard__eyebrow">فضای کاری مدرس</span>
            <h2>سلام {{ auth()->user()->name }}، خوش آمدی</h2>
            <p>نمای امروز بر اساس کلاس‌ها و فعالیت‌های ثبت‌شده برای حساب شما به‌روزرسانی می‌شود.</p>
        </div>
        <div class="teacher-dashboard__hero-actions">
            @canany(['classes.view.assigned', 'classes.manage.assigned'])
                <a class="panel-btn" href="{{ route('admin.classes.index') }}">کلاس‌های من</a>
            @endcanany
            @can('messages.view.assigned')
                <a class="panel-btn panel-btn-secondary" href="{{ route('admin.messages.index') }}">کارتابل پیام‌ها</a>
            @endcan
        </div>
    </section>

    <section class="teacher-dashboard__stats">
        @can('students.view.assigned')
            <x-admin::stat label="دانش‌آموزان فعال" :value="number_format($stats['students'])" note="در کلاس‌ها و دوره‌های تخصیص‌یافته" tone="people" icon="♙"/>
        @endcan
        @canany(['classes.view.assigned', 'classes.manage.assigned'])
            <x-admin::stat label="کلاس‌های فعال" :value="number_format($stats['classes'])" note="کلاس‌های قابل دسترس" tone="learning" icon="▣"/>
        @endcanany
        @can('courses.view.assigned')
            <x-admin::stat label="دوره‌های منتشرشده" :value="number_format($stats['courses'])" note="دوره‌های تخصیص‌یافته" tone="learning" icon="▤"/>
        @endcan
        @canany(['attendance.view.assigned', 'attendance.manage.assigned'])
            <x-admin::stat label="نرخ حضور امروز" :value="number_format($stats['attendance_rate']).'٪'" note="بر اساس حضور و غیاب ثبت‌شده" tone="presence" icon="✓"/>
        @endcanany
        @canany(['homework.view.assigned', 'homework.manage.assigned', 'homework.grade'])
            <x-admin::stat label="تکالیف منتظر بررسی" :value="number_format($stats['pending_homework'])" note="ارسال‌های ثبت‌شده دانش‌آموزان" tone="work" icon="✎"/>
        @endcanany
    </section>

    <section class="teacher-dashboard__grid">
        @canany(['classes.view.assigned', 'classes.manage.assigned'])
            <article class="panel-card teacher-dashboard__classes" data-tone="learning">
                <div class="panel-card-title">
                    <div><h3>کلاس‌های فعال من</h3><p>فهرست کلاس‌های فعال و تعداد دانش‌آموزان ثبت‌شده</p></div>
                    <a href="{{ route('admin.classes.index') }}" class="panel-btn panel-btn-secondary panel-btn-sm">همه کلاس‌ها</a>
                </div>
                <div class="panel-table-wrap">
                    <table class="panel-table">
                        <thead><tr><th>کلاس</th><th>دوره</th><th>دانش‌آموز</th></tr></thead>
                        <tbody>
                        @forelse($todayClasses as $classroom)
                            <tr>
                                <td><strong>{{ $classroom->name }}</strong></td>
                                <td>{{ $classroom->course->title ?? '—' }}</td>
                                <td><span class="panel-badge">{{ number_format($classroom->students_count) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="panel-empty">کلاس فعالی برای حساب شما ثبت نشده است.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        @endcanany

        @canany(['attendance.view.assigned', 'attendance.manage.assigned'])
            <article class="panel-card teacher-dashboard__attendance" data-tone="presence">
                <div class="panel-card-title">
                    <div><h3>روند حضور در هفت روز اخیر</h3><p>محاسبه‌شده از رکوردهای واقعی حضور و غیاب</p></div>
                    <span class="panel-badge panel-badge-success">امروز {{ number_format($stats['attendance_rate']) }}٪</span>
                </div>
                <div class="panel-bars">
                    @forelse($weeklyAttendance as $day)
                        <div class="panel-bar-col">
                            <strong>{{ number_format($day['rate']) }}٪</strong>
                            <div class="panel-bar" style="height: {{ max(5, $day['rate']) }}%"></div>
                            <span>{{ $day['label'] }}</span>
                        </div>
                    @empty
                        <div class="panel-empty">هنوز رکورد حضور و غیاب ثبت نشده است.</div>
                    @endforelse
                </div>
                <a class="panel-btn panel-btn-secondary panel-btn-sm" href="{{ route('admin.attendance.index') }}">مدیریت حضور و غیاب</a>
            </article>
        @endcanany

        @canany(['homework.view.assigned', 'homework.manage.assigned', 'homework.grade'])
            <article class="panel-card" data-tone="work">
                <div class="panel-card-title"><div><h3>بررسی تکالیف</h3><p>ارسال‌های منتظر بررسی در کلاس‌های شما</p></div></div>
                <div class="teacher-dashboard__metric">{{ number_format($stats['pending_homework']) }}</div>
                <p class="panel-muted">پاسخ تحویل‌شده و بررسی‌نشده</p>
                <a class="panel-btn panel-btn-secondary panel-btn-sm" href="{{ route('admin.homework.index') }}">رفتن به تکالیف</a>
            </article>
        @endcanany

        @can('messages.view.assigned')
            <article class="panel-card" data-tone="talk">
                <div class="panel-card-title"><div><h3>پیام‌های بی‌پاسخ</h3><p>گفت‌وگوهای مرتبط با دانش‌آموزان کلاس‌های شما</p></div><a class="panel-btn panel-btn-secondary panel-btn-sm" href="{{ route('admin.messages.index') }}">کارتابل</a></div>
                <div class="panel-list">
                    @forelse($recentMessages as $message)
                        <a class="panel-list-row" href="{{ route('admin.messages.index', ['message' => $message->id]) }}">
                            <span class="panel-avatar">{{ mb_substr($message->sender->name ?? '؟', 0, 1) }}</span>
                            <span class="panel-list-main"><strong>{{ $message->subject }}</strong><span>{{ $message->sender->name ?? 'کاربر حذف‌شده' }} · {{ $message->created_at->diffForHumans() }}</span></span>
                        </a>
                    @empty
                        <div class="panel-empty">پیام بی‌پاسخی وجود ندارد.</div>
                    @endforelse
                </div>
                <a class="panel-btn panel-btn-secondary panel-btn-sm" href="{{ route('teacher.messages.create') }}">ارسال پیام جدید</a>
            </article>
        @endcan

        @can('consultations.create')
            <article class="panel-card" data-tone="talk">
                <div class="panel-card-title"><div><h3>درخواست‌های من</h3><p>وضعیت درخواست‌هایی که برای مدیریت ثبت کرده‌اید</p></div><a class="panel-btn panel-btn-secondary panel-btn-sm" href="{{ route('teacher.consultations.index') }}">مشاهده همه</a></div>
                <div class="panel-list">
                    @forelse($myConsultations as $consultation)
                        <div class="panel-list-row">
                            <span class="panel-avatar">◷</span>
                            <span class="panel-list-main"><strong>{{ $consultation->topic ?: 'درخواست مشاوره' }}</strong><span>{{ $consultation->created_at->diffForHumans() }}</span></span>
                            <span class="panel-badge {{ $consultation->status === 'pending' ? 'panel-badge-warning' : 'panel-badge-success' }}">{{ ['pending'=>'در انتظار','scheduled'=>'هماهنگ‌شده','done'=>'انجام‌شده','cancelled'=>'لغوشده'][$consultation->status] ?? $consultation->status }}</span>
                        </div>
                    @empty
                        <div class="panel-empty">درخواستی برای مدیریت ثبت نکرده‌اید.</div>
                    @endforelse
                </div>
                <a class="panel-btn panel-btn-secondary panel-btn-sm" href="{{ route('teacher.consultations.index') }}">ثبت درخواست مشاوره</a>
            </article>
        @endcan

        @canany(['announcements.view', 'announcements.manage'])
            <article class="panel-card" data-tone="system">
                <div class="panel-card-title"><div><h3>اطلاعیه‌های منتشرشده</h3><p>اطلاعیه‌های قابل مشاهده برای حساب شما</p></div></div>
                <div class="panel-list">
                    @forelse($recentAnnouncements as $announcement)
                        <div class="panel-list-row"><span class="panel-avatar">⚑</span><span class="panel-list-main"><strong>{{ $announcement->title }}</strong><span>{{ $announcement->published_at?->diffForHumans() ?? '—' }}</span></span></div>
                    @empty
                        <div class="panel-empty">اطلاعیه‌ای برای نمایش وجود ندارد.</div>
                    @endforelse
                </div>
            </article>
        @endcanany
    </section>

    <section class="teacher-dashboard__actions">
        @canany(['classes.view.assigned', 'classes.manage.assigned'])
            <a class="admin-quick-action" href="{{ route('admin.parent-meetings.index') }}"><span class="admin-quick-action-icon">♧</span><span>جلسات اولیا</span></a>
        @endcanany
        @can('resources.manage.assigned')
            <a class="admin-quick-action" href="{{ route('admin.resources.index') }}"><span class="admin-quick-action-icon">▧</span><span>منابع آموزشی</span></a>
        @endcan
        @can('videos.manage.assigned')
            <a class="admin-quick-action" href="{{ route('admin.spotplayer.index') }}"><span class="admin-quick-action-icon">▶</span><span>ویدیوهای آموزشی</span></a>
        @endcan
    </section>
</div>
@endsection
