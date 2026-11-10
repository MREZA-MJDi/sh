@extends('layouts.panel')

@section('page-title', 'داشبورد مدیریت')
@section('page-description', 'نمای کلی وضعیت آموزشی و عملیاتی آکادمی')

@section('content')
    <section class="panel-card panel-hero mb-4">
        <div>
            <span class="text-xs text-indigo-100">به پنل مدیریت آکادمی شیخان خوش آمدید</span>
            <h2>مدیریت امروز آکادمی</h2>
            <p>آمارها و فعالیت‌های واقعی ثبت‌شده در سامانه در این صفحه نمایش داده می‌شوند.</p>
        </div>
        <a href="{{ route('admin.classes.index') }}" class="panel-btn">مشاهده کلاس‌ها <span>←</span></a>
    </section>

    <section class="panel-grid panel-grid-4 mb-4">
        <article class="panel-card panel-stat" class="panel-card panel-stat panel-stat--students">
            <div class="panel-stat-symbol">♙</div>
            <span class="panel-stat-label">دانش‌آموزان فعال</span>
            <strong class="panel-stat-value">{{ number_format($stats['students']) }}</strong>
            <span class="panel-stat-note">حساب‌های فعال دارای نقش دانش‌آموز</span>
        </article>
        <article class="panel-card panel-stat" class="panel-card panel-stat panel-stat--teachers">
            <div class="panel-stat-symbol">♟</div>
            <span class="panel-stat-label">مدرسان فعال</span>
            <strong class="panel-stat-value">{{ number_format($stats['teachers']) }}</strong>
            <span class="panel-stat-note">حساب‌های فعال دارای نقش معلم</span>
        </article>
        <article class="panel-card panel-stat" class="panel-card panel-stat panel-stat--classes">
            <div class="panel-stat-symbol">▣</div>
            <span class="panel-stat-label">کلاس‌های فعال</span>
            <strong class="panel-stat-value">{{ number_format($stats['classes']) }}</strong>
            <span class="panel-stat-note">کلاس‌های در وضعیت فعال</span>
        </article>
        <article class="panel-card panel-stat" class="panel-card panel-stat panel-stat--courses">
            <div class="panel-stat-symbol">▤</div>
            <span class="panel-stat-label">دوره‌های منتشرشده</span>
            <strong class="panel-stat-value">{{ number_format($stats['courses']) }}</strong>
            <span class="panel-stat-note">دوره‌های قابل ارائه</span>
        </article>
        <article class="panel-card panel-stat" class="panel-card panel-stat panel-stat--homework">
            <div class="panel-stat-symbol">✎</div>
            <span class="panel-stat-label">ارسال‌های تکلیف منتظر بررسی</span>
            <strong class="panel-stat-value">{{ number_format($stats['pending_homework']) }}</strong>
            <span class="panel-stat-note">پاسخ‌های تحویل‌شده دانش‌آموزان</span>
        </article>
    </section>

    <section class="panel-grid panel-grid-2 mb-4">
        <article class="panel-card">
            <div class="panel-card-title">
                <div><h3>نرخ حضور در هفت روز گذشته</h3><p>بر اساس رکوردهای واقعی حضور و غیاب</p></div>
                <span class="panel-badge panel-badge-success">امروز {{ $stats['attendance_rate'] }}٪</span>
            </div>
            <div class="panel-bars">
                @foreach ($weeklyAttendance as $day)
                    <div class="panel-bar-col">
                        <strong>{{ $day['rate'] }}٪</strong>
                        <div class="panel-bar" style="height: {{ max(5, $day['rate']) }}%"></div>
                        <span>{{ $day['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="panel-card">
            <div class="panel-card-title">
                <div><h3>کلاس‌های فعال</h3><p>آخرین کلاس‌های تعریف‌شده در سامانه</p></div>
                <a href="{{ route('admin.classes.index') }}" class="panel-btn panel-btn-secondary panel-btn-sm">همه کلاس‌ها</a>
            </div>
            <div class="panel-table-wrap">
                <table class="panel-table">
                    <thead><tr><th>کلاس</th><th>مدرس</th><th>دوره</th><th>دانش‌آموز</th></tr></thead>
                    <tbody>
                    @forelse ($todayClasses as $classroom)
                        <tr>
                            <td>{{ $classroom->name }}</td>
                            <td>{{ $classroom->teacher->name ?? '—' }}</td>
                            <td>{{ $classroom->course->title ?? '—' }}</td>
                            <td><span class="panel-badge">{{ $classroom->students_count ?? $classroom->students()->count() }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="panel-empty">هنوز کلاسی ثبت نشده است.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>

    <section class="panel-grid panel-grid-3">
        <article class="panel-card">
            <div class="panel-card-title"><h3>پیام‌های بی‌پاسخ</h3><a href="{{ route('admin.messages.index') }}" class="panel-btn panel-btn-ghost panel-btn-sm">مشاهده</a></div>
            <div class="panel-list">
                @forelse ($recentMessages as $message)
                    <div class="panel-list-row">
                        <div class="panel-avatar">{{ mb_substr($message->sender->name ?? '؟', 0, 1) }}</div>
                        <div class="panel-list-main"><strong>{{ $message->subject }}</strong><span>{{ $message->sender->name ?? 'کاربر حذف‌شده' }} · {{ $message->created_at->diffForHumans() }}</span></div>
                    </div>
                @empty
                    <div class="panel-empty">پیام بی‌پاسخی وجود ندارد.</div>
                @endforelse
            </div>
        </article>

        <article class="panel-card">
            <div class="panel-card-title"><h3>درخواست‌های مشاوره</h3><a href="{{ route('admin.consultations.index') }}" class="panel-btn panel-btn-ghost panel-btn-sm">کارتابل</a></div>
            <div class="panel-list">
                @forelse ($pendingConsultations as $consultation)
                    <div class="panel-list-row">
                        <div class="panel-avatar">◷</div>
                        <div class="panel-list-main"><strong>{{ $consultation->requester->name ?? 'کاربر' }}</strong><span>{{ $consultation->type }} · {{ $consultation->created_at->diffForHumans() }}</span></div>
                        <span class="panel-badge panel-badge-warning">در انتظار</span>
                    </div>
                @empty
                    <div class="panel-empty">درخواست در انتظاری وجود ندارد.</div>
                @endforelse
            </div>
        </article>

        <article class="panel-card">
            <div class="panel-card-title"><h3>اطلاعیه‌های منتشرشده</h3><a href="{{ route('admin.announcements.index') }}" class="panel-btn panel-btn-ghost panel-btn-sm">مدیریت</a></div>
            <div class="panel-list">
                @forelse ($recentAnnouncements as $announcement)
                    <div class="panel-list-row">
                        <div class="panel-avatar">⚑</div>
                        <div class="panel-list-main"><strong>{{ $announcement->title }}</strong><span>{{ $announcement->published_at?->diffForHumans() ?? '—' }}</span></div>
                    </div>
                @empty
                    <div class="panel-empty">اطلاعیه منتشرشده‌ای وجود ندارد.</div>
                @endforelse
            </div>
        </article>
    </section>
@endsection
