@extends('layouts.panel')

@section('page-title', 'داشبورد اولیا')
@section('page-description', 'گزارش وضعیت آموزشی فرزندان متصل به حساب شما')

@section('content')
    <section class="panel-card panel-hero panel-section-anchor mb-4" id="overview">
        <div>
            <span class="text-xs text-indigo-100">خوش آمدید {{ auth()->user()->name }}</span>
            <h2>پیگیری آموزش فرزندان</h2>
            <p>اطلاعات زیر فقط از پرونده فرزندانی خوانده می‌شود که به این حساب متصل شده‌اند.</p>
        </div>
        <span class="panel-hero-meta">{{ now()->translatedFormat('l، d F Y') }}</span>
    </section>

    @can('students.view.linked')
        <section class="panel-card panel-section-anchor mb-4" id="children">
            <div class="panel-card-title"><div><h3>فرزندان متصل‌شده</h3><p>فهرست ارتباط‌های ثبت‌شده در سامانه</p></div><span class="panel-badge">{{ number_format($children->count()) }} نفر</span></div>
            <div class="panel-grid panel-grid-3">
                @forelse ($children as $child)
                    <article class="panel-subcard"><strong>{{ $child->name }}</strong><span>{{ $child->grade ?? 'پایه ثبت نشده' }}</span></article>
                @empty
                    <div class="panel-empty">هنوز فرزندی به این حساب متصل نشده است.</div>
                @endforelse
            </div>
        </section>
    @endcan

    @can('attendance.view.children')
        <section class="panel-card panel-section-anchor mb-4" id="attendance">
            <div class="panel-card-title"><div><h3>آخرین رکوردهای حضور و غیاب</h3><p>رکوردهای فرزندان متصل به این حساب</p></div></div>
            <div class="panel-table-wrap">
                <table class="panel-table"><thead><tr><th>دانش‌آموز</th><th>کلاس</th><th>تاریخ</th><th>وضعیت</th></tr></thead><tbody>
                @forelse ($attendance as $record)
                    <tr><td>{{ $record->student?->name ?? '—' }}</td><td>{{ $record->classroom?->name ?? '—' }}</td><td>{{ $record->session_date?->translatedFormat('Y/m/d') ?? '—' }}</td><td>{{ $record->status }}</td></tr>
                @empty
                    <tr><td colspan="4" class="panel-empty">رکورد حضور و غیابی برای فرزندان این حساب ثبت نشده است.</td></tr>
                @endforelse
                </tbody></table>
            </div>
        </section>
    @endcan

    @can('homework.view.children')
        <section class="panel-card panel-section-anchor mb-4" id="homework">
            <div class="panel-card-title"><div><h3>تکالیف فرزندان</h3><p>تکالیف منتشرشده برای کلاس‌های فرزندان</p></div></div>
            <div class="panel-table-wrap">
                <table class="panel-table"><thead><tr><th>تکلیف</th><th>کلاس</th><th>مهلت</th></tr></thead><tbody>
                @forelse ($homeworks as $homework)
                    <tr><td>{{ $homework->title }}</td><td>{{ $homework->classroom?->name ?? '—' }}</td><td>{{ $homework->due_at?->translatedFormat('Y/m/d H:i') ?? '—' }}</td></tr>
                @empty
                    <tr><td colspan="3" class="panel-empty">تکلیف منتشرشده‌ای برای نمایش وجود ندارد.</td></tr>
                @endforelse
                </tbody></table>
            </div>
        </section>
    @endcan

    @can('exams.view.children')
        <section class="panel-card panel-section-anchor mb-4" id="results">
            <div class="panel-card-title"><div><h3>نتایج آزمون‌ها</h3><p>نتایج دریافتی فرزندان متصل به این حساب</p></div></div>
            <div class="panel-table-wrap">
                <table class="panel-table"><thead><tr><th>دانش‌آموز</th><th>آزمون</th><th>نمره</th></tr></thead><tbody>
                @forelse ($results as $result)
                    <tr><td>{{ $result->student?->name ?? '—' }}</td><td>{{ $result->exam?->title ?? '—' }}</td><td>{{ $result->score === null ? 'در انتظار نمره' : number_format((float) $result->score, 2).' / '.number_format((float) $result->max_score, 2) }}</td></tr>
                @empty
                    <tr><td colspan="3" class="panel-empty">نتیجه‌ای برای نمایش وجود ندارد.</td></tr>
                @endforelse
                </tbody></table>
            </div>
        </section>
    @endcan

    @can('announcements.view')
        <section class="panel-card panel-section-anchor" id="announcements">
            <div class="panel-card-title"><div><h3>اطلاعیه‌های آکادمی</h3><p>اطلاعیه‌های منتشرشده برای اولیا</p></div></div>
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
