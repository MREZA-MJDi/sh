@extends('admin.layouts.app')

@section('page-title', auth()->user()->hasRole('teacher') && ! auth()->user()->hasRole('admin') ? 'داشبورد من' : 'داشبورد مدیریت')
@section('page-description', 'نمای کلی وضعیت آموزشی و فعالیت‌های مجاز شما')

@section('content')
    @php($teacherView = auth()->user()->hasRole('teacher') && ! auth()->user()->hasRole('admin'))
    <section class="panel-card panel-hero mb-4">
        <div>
            <span class="text-xs text-indigo-100">{{ $teacherView ? 'به فضای کاری آکادمی شیخان خوش آمدید' : 'به پنل مدیریت آکادمی شیخان خوش آمدید' }}</span>
            <h2>{{ $teacherView ? 'کلاس‌ها و فعالیت‌های شما' : 'مدیریت امروز آکادمی' }}</h2>
            <p>{{ $teacherView ? 'همه ابزارهای کاری شما در همین پنل و بر اساس دسترسی‌های تعیین‌شده نمایش داده می‌شود.' : 'آمارها و فعالیت‌های واقعی ثبت‌شده در سامانه در این صفحه نمایش داده می‌شوند.' }}</p>
        </div>
        @canany(['classes.manage', 'classes.view.assigned', 'classes.manage.assigned'])
            <a href="{{ route('admin.classes.index') }}" class="panel-btn">مشاهده کلاس‌ها <span>←</span></a>
        @endcanany
    </section>

    <section class="panel-grid panel-grid-4 mb-4">
        @canany(['users.manage', 'students.view.assigned'])
            <x-admin.stat label="دانش‌آموزان فعال" :value="number_format($stats['students'])" note="در کلاس‌های قابل دسترس" accent="#5065d8" icon="♙"/>
        @endcanany
        @can('users.manage')
            <x-admin.stat label="مدرسان فعال" :value="number_format($stats['teachers'])" note="حساب‌های فعال" accent="#0b9a89" icon="♟"/>
        @endcan
        @canany(['classes.manage', 'classes.view.assigned', 'classes.manage.assigned'])
            <x-admin.stat label="کلاس‌های فعال" :value="number_format($stats['classes'])" note="کلاس‌های قابل دسترس" accent="#d18b11" icon="▣"/>
        @endcanany
        @canany(['courses.manage', 'courses.view.assigned'])
            <x-admin.stat label="دوره‌های منتشرشده" :value="number_format($stats['courses'])" note="دوره‌های قابل ارائه" accent="#d84b59" icon="▤"/>
        @endcanany
        @canany(['homework.manage.all', 'homework.manage.assigned', 'homework.view.assigned', 'homework.grade'])
            <x-admin.stat label="تکالیف منتظر بررسی" :value="number_format($stats['pending_homework'])" note="ارسال‌های تحویل‌شده" accent="#7b55c7" icon="✎"/>
        @endcanany
    </section>

    <section class="panel-grid panel-grid-2 mb-4">
        @canany(['attendance.manage.all', 'attendance.manage.assigned', 'attendance.view.assigned'])
            <article class="panel-card">
                <div class="panel-card-title">
                    <div><h3>نرخ حضور در هفت روز گذشته</h3><p>بر اساس رکوردهای واقعی حضور و غیاب</p></div>
                    <span class="panel-badge panel-badge-success">امروز {{ $stats['attendance_rate'] }}٪</span>
                </div>
                <div class="panel-bars">
                    @foreach ($weeklyAttendance as $day)
                        <div class="panel-bar-col"><strong>{{ $day['rate'] }}٪</strong><div class="panel-bar" style="height: {{ max(5, $day['rate']) }}%"></div><span>{{ $day['label'] }}</span></div>
                    @endforeach
                </div>
            </article>
        @endcanany

        @canany(['classes.manage', 'classes.view.assigned', 'classes.manage.assigned'])
            <article class="panel-card">
                <div class="panel-card-title">
                    <div><h3>{{ $teacherView ? 'کلاس‌های من' : 'کلاس‌های فعال' }}</h3><p>کلاس‌های مجاز برای حساب شما</p></div>
                    <a href="{{ route('admin.classes.index') }}" class="panel-btn panel-btn-secondary panel-btn-sm">همه کلاس‌ها</a>
                </div>
                <div class="panel-table-wrap">
                    <table class="panel-table">
                        <thead><tr><th>کلاس</th><th>مدرس</th><th>دوره</th><th>دانش‌آموز</th></tr></thead>
                        <tbody>
                        @forelse ($todayClasses as $classroom)
                            <tr><td>{{ $classroom->name }}</td><td>{{ $classroom->teacher->name ?? '—' }}</td><td>{{ $classroom->course->title ?? '—' }}</td><td><span class="panel-badge">{{ $classroom->students_count }}</span></td></tr>
                        @empty
                            <tr><td colspan="4" class="panel-empty">کلاسی برای نمایش وجود ندارد.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        @endcanany
    </section>

    <section class="panel-grid panel-grid-3">
        @canany(['messages.view.all', 'messages.view.assigned'])
            <article class="panel-card">
                <div class="panel-card-title"><h3>پیام‌های بی‌پاسخ</h3><a href="{{ route('admin.messages.index') }}" class="panel-btn panel-btn-ghost panel-btn-sm">مشاهده</a></div>
                <div class="panel-list">
                    @forelse ($recentMessages as $message)
                        <div class="panel-list-row"><div class="panel-avatar">{{ mb_substr($message->sender->name ?? '؟', 0, 1) }}</div><div class="panel-list-main"><strong>{{ $message->subject }}</strong><span>{{ $message->sender->name ?? 'کاربر حذف‌شده' }} · {{ $message->created_at->diffForHumans() }}</span></div></div>
                    @empty
                        <div class="panel-empty">پیام بی‌پاسخی وجود ندارد.</div>
                    @endforelse
                </div>
            </article>
        @endcanany

        @can('consultations.manage')
            <article class="panel-card">
                <div class="panel-card-title"><h3>درخواست‌های مشاوره</h3><a href="{{ route('admin.consultations.index') }}" class="panel-btn panel-btn-ghost panel-btn-sm">کارتابل</a></div>
                <div class="panel-list">
                    @forelse ($pendingConsultations as $consultation)
                        <div class="panel-list-row"><div class="panel-avatar">◷</div><div class="panel-list-main"><strong>{{ $consultation->requester->name ?? 'کاربر' }}</strong><span>{{ $consultation->type }} · {{ $consultation->created_at->diffForHumans() }}</span></div><span class="panel-badge panel-badge-warning">در انتظار</span></div>
                    @empty
                        <div class="panel-empty">درخواست در انتظاری وجود ندارد.</div>
                    @endforelse
                </div>
            </article>
        @endcan

        @canany(['announcements.manage', 'announcements.view'])
            <article class="panel-card">
                <div class="panel-card-title"><h3>اطلاعیه‌های منتشرشده</h3><a href="{{ route('admin.announcements.index') }}" class="panel-btn panel-btn-ghost panel-btn-sm">مشاهده</a></div>
                <div class="panel-list">
                    @forelse ($recentAnnouncements as $announcement)
                        <div class="panel-list-row"><div class="panel-avatar">⚑</div><div class="panel-list-main"><strong>{{ $announcement->title }}</strong><span>{{ $announcement->published_at?->diffForHumans() ?? '—' }}</span></div></div>
                    @empty
                        <div class="panel-empty">اطلاعیه منتشرشده‌ای وجود ندارد.</div>
                    @endforelse
                </div>
            </article>
        @endcanany
    </section>
@endsection
