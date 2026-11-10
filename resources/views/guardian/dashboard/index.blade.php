@extends('layouts.parent')

@section('title', 'کارتابل اولیاء')

@section('content')
    @php
        $faNumber = static fn ($value, int $precision = 0): string =>
            \App\Support\PersianDate::toPersianDigits(number_format((float) $value, $precision, '٫', '٬'));
    @endphp

    <section class="hero" id="overview" aria-labelledby="guardian-welcome-title">
        <svg class="av" viewBox="0 0 120 120" aria-hidden="true"><use href="#avatar"></use></svg>
        <div class="txt">
            <h2 id="guardian-welcome-title">سلام، {{ auth()->user()->name }}!</h2>
            @if($selected_child)
                <p>وضعیت آموزشی {{ $selected_child->name }}@if($selected_child->grade) · پایه‌ی {{ $selected_child->grade }}@endif را از این بخش پیگیری کنید.</p>
                <a class="btn" href="#classes">مشاهده‌ی کلاس‌ها</a>
                <a class="btn g" href="{{ route('guardian.consultations.create', ['child' => $selected_child->id]) }}">درخواست مشاوره</a>
            @else
                <p>هنوز دانش‌آموزی به این حساب متصل نشده است. برای اتصال فرزند با پشتیبانی آکادمی تماس بگیرید.</p>
            @endif
        </div>
    </section>

    <section class="stats" aria-label="خلاصه‌ی وضعیت تحصیلی فرزند">
        <x-stat tone="s1" icon="book" label="کلاس‌های فعال" :value="$faNumber($classroom_count)" hint="کلاس‌های ثبت‌شده برای فرزند" href="#classes"/>
        <x-stat tone="s2" icon="task" label="تکالیف باز" :value="$faNumber($pending_homework_count)" hint="تکالیف بدون پاسخ ثبت‌شده" href="#homework"/>
        <x-stat tone="s3" icon="cal" label="نرخ حضور" :value="$attendance_rate === null ? '—' : $faNumber($attendance_rate).'٪'" :hint="$faNumber($attendance_count).' جلسه‌ی ثبت‌شده'" href="#attendance"/>
        <x-stat tone="s4" icon="chart" label="نتایج اخیر" :value="$faNumber($results->count())" hint="نتایج قابل مشاهده‌ی فرزند" href="#results"/>
    </section>

    <section class="card student-course-progress" id="course-progress" aria-labelledby="guardian-course-progress-title">
        <div class="chead">
            <div><span class="student-section-eyebrow">گزارش تحصیلی فرزند</span><h2 id="guardian-course-progress-title">پیشرفت دوره‌ها و درس‌ها</h2></div>
        </div>
        <div class="student-course-progress__list">
            @forelse ($course_progress as $courseProgress)
                <article class="student-course-progress__item">
                    <div class="student-course-progress__head">
                        <strong>{{ $courseProgress->title }}</strong>
                        <span>{{ $faNumber($courseProgress->progress_percent) }}٪</span>
                    </div>
                    <div class="student-attendance-meter__track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $courseProgress->progress_percent }}">
                        <span style="width: {{ max(0, min(100, (int) $courseProgress->progress_percent)) }}%"></span>
                    </div>
                    <small class="mu">{{ $faNumber($courseProgress->completed_lessons) }} از {{ $faNumber($courseProgress->total_lessons) }} درس تکمیل‌شده</small>
                </article>
            @empty
                <div class="empty">برای دوره‌های ثبت‌نام‌شده هنوز درس منتشرشده‌ای ثبت نشده است.</div>
            @endforelse
        </div>
    </section>

    <section class="row">
        <section class="card" id="classes">
            <div class="chead"><h2>کلاس‌ها و دوره‌ها</h2><span class="pill">{{ $faNumber($classroom_count) }} کلاس</span></div>
            @forelse($classrooms as $classroom)
                <div class="trk">
                    <div class="th t-a"><x-icon name="book"/></div>
                    <div><b>{{ $classroom->name }}</b><span class="mu">{{ $classroom->course?->title ?? 'کلاس مستقل' }}</span><span class="mu">مدرس: {{ $classroom->teacher?->name ?? 'تعیین نشده' }}</span></div>
                </div>
            @empty
                <div class="empty">برای این فرزند کلاسی ثبت نشده است.</div>
            @endforelse
        </section>

        <section class="card" id="homework">
            <div class="chead"><h2>تکالیف نیازمند پیگیری</h2><span class="pill">{{ $faNumber($pending_homework_count) }} تکلیف باز</span></div>
            @forelse($homeworks as $homework)
                <div class="trk">
                    <div class="th t-c"><x-icon name="task"/></div>
                    <div>
                        <b>{{ $homework->title }}</b>
                        <span class="mu">{{ $homework->classroom?->name ?? '—' }}</span>
                        <span class="mu">مهلت: {{ $homework->due_at ? \App\Support\PersianDate::format($homework->due_at, 'l، d F Y ساعت H:i') : 'بدون مهلت' }}</span>
                    </div>
                    @if($homework->selected_student_submission_count > 0)<span class="tag">ارسال شده</span>@else<span class="tag w">در انتظار ارسال</span>@endif
                </div>
            @empty
                <div class="empty">تکلیف منتشرشده‌ای برای این فرزند ثبت نشده است.</div>
            @endforelse
        </section>
    </section>

    <section class="card" id="homework-reviews" aria-labelledby="guardian-homework-reviews-title">
        <div class="chead">
            <h2 id="guardian-homework-reviews-title">نتایج بررسی تکالیف</h2>
            <span class="pill">{{ $faNumber($reviewed_homework->count()) }} نتیجه</span>
        </div>
        @forelse ($reviewed_homework as $submission)
            <article class="student-homework-item">
                <div class="student-homework-item__head">
                    <div>
                        <h3>{{ $submission->homework?->title ?? 'تکلیف' }}</h3>
                        <span class="mu">{{ $submission->homework?->classroom?->name ?? 'کلاس آموزشی' }}</span>
                    </div>
                    @if ($submission->score !== null)
                        <strong class="student-homework-item__score">نمره {{ $faNumber($submission->score, 2) }}</strong>
                    @endif
                </div>
                <p class="mu">بررسی توسط: {{ $submission->reviewer?->name ?? 'مدرس یا مدیر آموزشگاه' }} · {{ \App\Support\PersianDate::format($submission->reviewed_at, 'Y/m/d H:i') }}</p>
                @if (filled($submission->feedback))
                    <p class="student-homework-item__answer">{{ $submission->feedback }}</p>
                @else
                    <p class="mu">بازخورد متنی برای این تکلیف ثبت نشده است.</p>
                @endif
            </article>
        @empty
            <div class="empty">هنوز نتیجه بررسی‌شده‌ای برای تکالیف این دانش‌آموز ثبت نشده است.</div>
        @endforelse
    </section>

    <section class="row">
        <section class="card" id="results">
            <div class="chead"><h2>نتایج آزمون‌ها</h2><span class="pill">{{ $faNumber($results->count()) }} نتیجه</span></div>
            <div class="tw">
                <table class="tbl">
                    <thead><tr><th scope="col">آزمون</th><th scope="col">دوره یا کلاس</th><th scope="col">نمره</th><th scope="col">تاریخ</th></tr></thead>
                    <tbody>
                    @forelse($results as $result)
                        <tr>
                            <td>{{ $result->exam?->title ?? 'آزمون حذف‌شده' }}</td>
                            <td>{{ $result->exam?->course?->title ?? $result->exam?->classroom?->name ?? '—' }}</td>
                            <td>{{ $result->score === null ? 'در انتظار نمره' : $faNumber($result->score, 2).' / '.$faNumber($result->max_score, 2) }}</td>
                            <td>{{ \App\Support\PersianDate::format($result->synced_at ?? $result->created_at, 'Y/m/d') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty">هنوز نتیجه‌ای ثبت نشده است.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card" id="attendance">
            <div class="chead"><h2>حضور و غیاب</h2><span class="pill">{{ $attendance_rate === null ? 'بدون داده' : $faNumber($attendance_rate).'٪ حضور' }}</span></div>
            @forelse($attendance as $record)
                <div class="trk">
                    <div class="th t-b"><x-icon name="cal"/></div>
                    <div><b>{{ $record->classroom?->name ?? 'کلاس' }}</b><span class="mu">{{ \App\Support\PersianDate::format($record->session_date, 'l، d F Y') }}</span></div>
                    @if($record->status === 'present')<span class="tag">حاضر</span>@elseif($record->status === 'late')<span class="tag w">با تأخیر</span>@else<span class="tag r">غایب</span>@endif
                </div>
            @empty
                <div class="empty">رکورد حضور و غیابی ثبت نشده است.</div>
            @endforelse
        </section>
    </section>

    <section class="row">
        <section class="card" id="resources">
            <div class="chead"><h2>منابع آموزشی</h2><span class="pill">{{ $faNumber($resource_count) }} منبع</span></div>
            @forelse($resources as $resource)
                <div class="trk">
                    <div class="th t-c"><x-icon name="file"/></div>
                    <div><b>{{ $resource->title }}</b><span class="mu">{{ $resource->course?->title ?? $resource->classroom?->name ?? $resource->kind }}</span></div>
                    <span class="tag">{{ $resource->is_free ? 'رایگان' : $faNumber($resource->price).' تومان' }}</span>
                    @if($resource->file_path && $selected_child)
                        <a class="btn g s" href="{{ route('guardian.resources.download', ['resource' => $resource, 'child' => $selected_child->id]) }}">دریافت منبع</a>
                    @endif
                </div>
            @empty
                <div class="empty">منبع آموزشی منتشرشده‌ای در دسترس نیست.</div>
            @endforelse
        </section>

        <section class="card" id="announcements">
            <div class="chead"><h2>اطلاعیه‌های آکادمی</h2></div>
            @forelse($announcements as $announcement)
                <article class="news">
                    <b>{{ $announcement->title }}</b>
                    <span class="mu">{{ \App\Support\PersianDate::format($announcement->published_at, 'l، d F Y ساعت H:i') }}</span>
                    @if(filled($announcement->body))<p>{{ \Illuminate\Support\Str::limit(strip_tags((string) $announcement->body), 180) }}</p>@endif
                </article>
            @empty
                <div class="empty">اطلاعیه‌ای برای نمایش وجود ندارد.</div>
            @endforelse
        </section>
    </section>
@endsection
