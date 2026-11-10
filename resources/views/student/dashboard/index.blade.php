@extends('layouts.panel')

@section('page-title', 'داشبورد دانش‌آموز')
@section('page-description', 'نمای یکپارچه کلاس‌ها، تکالیف و پیشرفت آموزشی شما')

@section('content')
    <section class="panel-card panel-hero panel-section-anchor mb-4" id="overview">
        <div>
            <span class="text-xs text-indigo-100">سلام {{ auth()->user()->name }}</span>
            <h2>مسیر یادگیری تو از همین‌جا ادامه پیدا می‌کند</h2>
            <p>وضعیت و فعالیت‌های آموزشی ثبت‌شده برای حساب خودت را اینجا می‌بینی.</p>
        </div>
        <span class="panel-hero-meta">{{ now()->translatedFormat('l، d F Y') }}</span>
    </section>

    <section class="panel-grid panel-grid-4 mb-4">
        <article class="panel-card panel-stat" style="--stat-color:#5065d8;--stat-soft:#edf0ff">
            <div class="panel-stat-symbol">▣</div>
            <span class="panel-stat-label">کلاس‌های فعال من</span>
            <strong class="panel-stat-value">{{ number_format($classroom_count) }}</strong>
            <span class="panel-stat-note">کلاس‌هایی که برایت ثبت شده‌اند</span>
        </article>
        <article class="panel-card panel-stat" style="--stat-color:#d18b11;--stat-soft:#fff5dc">
            <div class="panel-stat-symbol">✎</div>
            <span class="panel-stat-label">تکالیف انجام‌نشده</span>
            <strong class="panel-stat-value">{{ number_format($pending_homework_count) }}</strong>
            <span class="panel-stat-note">تکالیف منتشرشده بدون پاسخ ثبت‌شده</span>
        </article>
        <article class="panel-card panel-stat" style="--stat-color:#0b9a89;--stat-soft:#e2f6f2">
            <div class="panel-stat-symbol">✓</div>
            <span class="panel-stat-label">نرخ حضور</span>
            <strong class="panel-stat-value">{{ $attendance_rate === null ? '—' : number_format($attendance_rate).'٪' }}</strong>
            <span class="panel-stat-note">{{ number_format($attendance_count) }} جلسه دارای رکورد حضور</span>
        </article>
        <article class="panel-card panel-stat" style="--stat-color:#7b55c7;--stat-soft:#f1ebff">
            <div class="panel-stat-symbol">★</div>
            <span class="panel-stat-label">نتایج ثبت‌شده</span>
            <strong class="panel-stat-value">{{ number_format($results->count()) }}</strong>
            <span class="panel-stat-note">آخرین نتایج آزمون‌های ثبت‌شده</span>
        </article>
    </section>

    @can('classes.view.enrolled')
        <section class="panel-card panel-section-anchor mb-4" id="classes">
            <div class="panel-card-title">
                <div><h3>کلاس‌های من</h3><p>کلاس‌های فعال مرتبط با حساب دانش‌آموزی</p></div>
                <span class="panel-badge">{{ number_format($classroom_count) }} کلاس</span>
            </div>
            <div class="panel-grid panel-grid-3">
                @forelse ($classrooms as $classroom)
                    <article class="panel-subcard">
                        <strong>{{ $classroom->name }}</strong>
                        <span>{{ $classroom->course?->title ?? 'بدون دوره متصل' }}</span>
                        <small>مدرس: {{ $classroom->teacher?->name ?? 'تعیین نشده' }}</small>
                    </article>
                @empty
                    <div class="panel-empty">هنوز کلاسی برای این حساب ثبت نشده است.</div>
                @endforelse
            </div>
        </section>
    @endcan

    @can('homework.view.own')
        <section class="panel-card panel-section-anchor mb-4" id="homework">
            <div class="panel-card-title"><div><h3>تکالیف اخیر</h3><p>وضعیت پاسخ‌های ثبت‌شده برای تکالیف کلاس‌های شما</p></div></div>
            <div class="panel-table-wrap">
                <table class="panel-table" data-searchable-table>
                    <thead><tr><th>عنوان تکلیف</th><th>کلاس</th><th>مهلت</th><th>وضعیت پاسخ</th></tr></thead>
                    <tbody>
                    @forelse ($homeworks as $homework)
                        <tr>
                            <td>{{ $homework->title }}</td>
                            <td>{{ $homework->classroom?->name ?? '—' }}</td>
                            <td>{{ $homework->due_at?->translatedFormat('Y/m/d H:i') ?? 'بدون مهلت' }}</td>
                            <td>
                                @if ($homework->submitted_by_student_count > 0)
                                    <span class="panel-badge panel-badge-success">ارسال شده</span>
                                @else
                                    <span class="panel-badge panel-badge-warning">نیازمند انجام</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="panel-empty">تکلیف منتشرشده‌ای برای کلاس‌های شما ثبت نشده است.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endcan

    @can('attendance.view.own')
        <section class="panel-card panel-section-anchor mb-4" id="attendance">
            <div class="panel-card-title"><div><h3>آخرین وضعیت حضور و غیاب</h3><p>رکوردهای ثبت‌شده برای خود دانش‌آموز</p></div></div>
            <div class="panel-table-wrap">
                <table class="panel-table">
                    <thead><tr><th>کلاس</th><th>تاریخ</th><th>وضعیت</th></tr></thead>
                    <tbody>
                    @forelse ($attendance as $record)
                        <tr>
                            <td>{{ $record->classroom?->name ?? '—' }}</td>
                            <td>{{ $record->session_date?->translatedFormat('Y/m/d') ?? '—' }}</td>
                            <td>
                                @if ($record->status === 'present')
                                    <span class="panel-badge panel-badge-success">حاضر</span>
                                @elseif ($record->status === 'late')
                                    <span class="panel-badge panel-badge-warning">با تأخیر</span>
                                @else
                                    <span class="panel-badge panel-badge-danger">غایب</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="panel-empty">رکوردی از حضور و غیاب برای شما ثبت نشده است.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endcan

    @can('exams.view.own')
        <section class="panel-card panel-section-anchor mb-4" id="results">
            <div class="panel-card-title"><div><h3>نتایج آزمون‌ها</h3><p>نتایج دریافت‌شده و ثبت‌شده در سامانه</p></div></div>
            <div class="panel-table-wrap">
                <table class="panel-table">
                    <thead><tr><th>آزمون</th><th>دوره / کلاس</th><th>نمره</th><th>زمان ثبت</th></tr></thead>
                    <tbody>
                    @forelse ($results as $result)
                        <tr>
                            <td>{{ $result->exam?->title ?? 'آزمون حذف‌شده' }}</td>
                            <td>{{ $result->exam?->course?->title ?? $result->exam?->classroom?->name ?? '—' }}</td>
                            <td>{{ $result->score === null ? 'در انتظار نمره' : number_format((float) $result->score, 2).' / '.number_format((float) $result->max_score, 2) }}</td>
                            <td>{{ $result->synced_at?->translatedFormat('Y/m/d') ?? $result->created_at?->translatedFormat('Y/m/d') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="panel-empty">هنوز نتیجه‌ای برای حساب شما ثبت نشده است.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endcan

    @can('resources.view.enrolled')
        <section class="panel-card panel-section-anchor mb-4" id="resources">
            <div class="panel-card-title"><div><h3>منابع آموزشی اخیر</h3><p>منابع منتشرشده در کلاس‌های ثبت‌شده شما</p></div><span class="panel-badge">{{ number_format($resource_count) }} منبع</span></div>
            <div class="panel-list">
                @forelse ($resources as $resource)
                    <div class="panel-list-row">
                        <div class="panel-avatar">▧</div>
                        <div class="panel-list-main"><strong>{{ $resource->title }}</strong><span>{{ $resource->kind }} · {{ $resource->is_free ? 'رایگان' : number_format((int) $resource->price).' تومان' }}</span></div>
                    </div>
                @empty
                    <div class="panel-empty">منبع منتشرشده‌ای برای کلاس‌های شما وجود ندارد.</div>
                @endforelse
            </div>
        </section>
    @endcan

    @can('announcements.view')
        <section class="panel-card panel-section-anchor" id="announcements">
            <div class="panel-card-title"><div><h3>اطلاعیه‌های آکادمی</h3><p>اطلاعیه‌های منتشرشده برای دانش‌آموزان</p></div></div>
            <div class="panel-list">
                @forelse ($announcements as $announcement)
                    <div class="panel-list-row">
                        <div class="panel-avatar">⚑</div>
                        <div class="panel-list-main"><strong>{{ $announcement->title }}</strong><span>{{ $announcement->published_at?->diffForHumans() ?? '—' }}</span></div>
                    </div>
                @empty
                    <div class="panel-empty">اطلاعیه‌ای برای نمایش وجود ندارد.</div>
                @endforelse
            </div>
        </section>
    @endcan
@endsection
