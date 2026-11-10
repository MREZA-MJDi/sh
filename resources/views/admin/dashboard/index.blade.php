@extends('layouts.panel')

@section('page-title', 'داشبورد مدیریت')
@section('page-description', 'نمای کلی امروز و وضعیت آموزشی آکادمی')

@section('content')
    <section class="panel-card panel-hero mb-4">
        <div>
            <span class="panel-hero-eyebrow">به پنل مدیریت آکادمی شیخان خوش آمدید</span>
            <h2>مدیریت امروز آکادمی</h2>
            <p>آمارها و فعالیت‌های واقعی ثبت‌شده در سامانه در این صفحه نمایش داده می‌شوند.</p>
        </div>
        <a href="{{ route('admin.classes.index') }}" class="panel-btn">مشاهده کلاس‌ها <span aria-hidden="true">←</span></a>
    </section>

    <section class="panel-grid panel-grid-4 mb-4">
        <article class="panel-card panel-stat panel-stat-indigo">
            <span class="panel-stat-label">دانش‌آموزان فعال</span>
            <strong class="panel-stat-value">{{ number_format($stats['students']) }}</strong>
            <span class="panel-stat-note">حساب‌های فعال دارای نقش دانش‌آموز</span>
        </article>
        <article class="panel-card panel-stat panel-stat-teal">
            <span class="panel-stat-label">کلاس‌های فعال</span>
            <strong class="panel-stat-value">{{ number_format($stats['classes']) }}</strong>
            <span class="panel-stat-note">{{ number_format($stats['teachers']) }} مدرس فعال · {{ number_format($stats['courses']) }} دوره منتشرشده</span>
        </article>
        <article class="panel-card panel-stat panel-stat-amber">
            <span class="panel-stat-label">میانگین حضور امروز</span>
            <strong class="panel-stat-value">{{ number_format($stats['attendance_rate']) }}٪</strong>
            <span class="panel-stat-note">بر اساس رکوردهای واقعی حضور و غیاب</span>
        </article>
        <article class="panel-card panel-stat panel-stat-red">
            <span class="panel-stat-label">تکالیف منتظر بررسی</span>
            <strong class="panel-stat-value">{{ number_format($stats['pending_homework']) }}</strong>
            <span class="panel-stat-note">پاسخ‌های تحویل‌شده دانش‌آموزان</span>
        </article>
    </section>

    <section class="panel-grid panel-grid-2 mb-4">
        <article class="panel-card">
            <div class="panel-card-title">
                <div><h3>حضور در هفت روز گذشته</h3><p>بر اساس رکوردهای واقعی حضور و غیاب</p></div>
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
                <div><h3>کلاس‌های امروز</h3><p>آخرین کلاس‌های فعال ثبت‌شده در سامانه</p></div>
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
                        <tr><td colspan="4" class="panel-empty">هنوز کلاسی برای نمایش ثبت نشده است.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>

    <section class="panel-grid panel-grid-3 mb-4">
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

    <section class="panel-card">
        <div class="panel-card-title"><div><h3>میانبرهای سریع</h3><p>دسترسی مستقیم به عملیات پرکاربرد مدیریت</p></div></div>
        <div class="panel-quick-actions">
            <a class="panel-btn" href="{{ route('admin.students.index') }}">+ ثبت دانش‌آموز</a>
            <a class="panel-btn panel-btn-secondary" href="{{ route('admin.classes.index') }}">+ کلاس جدید</a>
            <a class="panel-btn panel-btn-ghost" href="{{ route('admin.announcements.index') }}">ارسال اطلاعیه</a>
            <a class="panel-btn panel-btn-ghost" href="{{ route('admin.resources.index') }}">افزودن منبع</a>
        </div>
    </section>
@endsection
