@extends('layouts.student')

@section('title', 'مرکز یادگیری')

@section('content')
    @php
        $faNumber = static function ($value, int $precision = 0): string {
            $formatted = number_format((float) $value, $precision, '٫', '٬');

            return strtr($formatted, [
                '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
                '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
            ]);
        };
    @endphp

    <section class="hero" id="overview" aria-labelledby="student-welcome-title">
        <svg class="av" viewBox="0 0 120 120" aria-hidden="true">
            <use href="#avatar"></use>
        </svg>
        <div class="txt">
            <h2 id="student-welcome-title">سلام، {{ auth()->user()->name }}!</h2>
            <p>
                {{ $faNumber($classroom_count) }} کلاس فعال و
                {{ $faNumber($pending_homework_count) }} تکلیف باز در کارتابل شما ثبت شده است.
            </p>
            @can('classes.view.enrolled')
                <a class="btn" href="#classes">مشاهده‌ی کلاس‌های من</a>
            @endcan
            @can('homework.view.own')
                <a class="btn g" href="#homework">پیگیری تکالیف</a>
            @endcan
        </div>
    </section>

    <section class="stats" aria-label="خلاصه‌ی وضعیت آموزشی">
        <x-stat
            tone="s1"
            icon="book"
            label="کلاس‌های فعال"
            :value="$faNumber($classroom_count)"
            hint="کلاس‌های ثبت‌شده برای شما"
            href="#classes"
        />
        <x-stat
            tone="s2"
            icon="task"
            label="تکالیف باز"
            :value="$faNumber($pending_homework_count)"
            hint="تکالیف بدون پاسخ ثبت‌شده"
            href="#homework"
        />
        <x-stat
            tone="s3"
            icon="cal"
            label="نرخ حضور"
            :value="$attendance_rate === null ? '—' : $faNumber($attendance_rate).'٪'"
            :hint="$faNumber($attendance_count).' جلسه‌ی ثبت‌شده'"
            href="#attendance"
        />
        <x-stat
            tone="s4"
            icon="chart"
            label="نتایج اخیر"
            :value="$faNumber($results->count())"
            hint="آخرین نمرات ثبت‌شده"
            href="#results"
        />
    </section>

    @can('classes.view.enrolled')
        <section class="card" id="classes" aria-labelledby="student-classes-title">
            <div class="chead">
                <h2 id="student-classes-title">کلاس‌های من</h2>
                <span class="pill">{{ $faNumber($classroom_count) }} کلاس</span>
            </div>

            @if($classrooms->isNotEmpty())
                <div class="g3">
                    @foreach($classrooms as $classroom)
                        @php
                            $tones = ['t-a', 't-b', 't-c', 't-d'];
                            $tone = $tones[$loop->index % count($tones)];
                        @endphp
                        <article class="cc">
                            <div class="th big {{ $tone }}">
                                <x-icon name="book"/>
                            </div>
                            <h3>{{ $classroom->name }}</h3>
                            <p>{{ $classroom->course?->title ?? 'بدون دوره‌ی متصل' }}</p>
                            <span class="mu">مدرس: {{ $classroom->teacher?->name ?? 'تعیین نشده' }}</span>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="empty">هنوز کلاسی برای این حساب ثبت نشده است.</div>
            @endif
        </section>
    @endcan

    @can('homework.view.own')
        <section class="card" id="homework" aria-labelledby="student-homework-title">
            <div class="chead">
                <h2 id="student-homework-title">تکالیف اخیر</h2>
                <span class="pill">{{ $faNumber($pending_homework_count) }} تکلیف باز</span>
            </div>

            <div class="tw">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th scope="col">عنوان تکلیف</th>
                            <th scope="col">کلاس</th>
                            <th scope="col">مهلت</th>
                            <th scope="col">وضعیت پاسخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($homeworks as $homework)
                            <tr>
                                <td>{{ $homework->title }}</td>
                                <td>{{ $homework->classroom?->name ?? '—' }}</td>
                                <td>{{ $homework->due_at?->translatedFormat('Y/m/d H:i') ?? 'بدون مهلت' }}</td>
                                <td>
                                    @if($homework->submitted_by_student_count > 0)
                                        <span class="tag">ارسال شده</span>
                                    @else
                                        <span class="tag w">نیازمند انجام</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="empty">تکلیف منتشرشده‌ای برای کلاس‌های شما ثبت نشده است.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endcan

    @can('attendance.view.own')
        <section class="card" id="attendance" aria-labelledby="student-attendance-title">
            <div class="chead">
                <h2 id="student-attendance-title">آخرین وضعیت حضور و غیاب</h2>
                <span class="pill">
                    {{ $attendance_rate === null ? 'بدون داده' : $faNumber($attendance_rate).'٪ حضور' }}
                </span>
            </div>

            <div class="tw">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th scope="col">کلاس</th>
                            <th scope="col">تاریخ جلسه</th>
                            <th scope="col">وضعیت</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendance as $record)
                            <tr>
                                <td>{{ $record->classroom?->name ?? '—' }}</td>
                                <td>{{ $record->session_date?->translatedFormat('Y/m/d') ?? '—' }}</td>
                                <td>
                                    @if($record->status === 'present')
                                        <span class="tag">حاضر</span>
                                    @elseif($record->status === 'late')
                                        <span class="tag w">با تأخیر</span>
                                    @else
                                        <span class="tag r">غایب</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="empty">رکوردی از حضور و غیاب برای شما ثبت نشده است.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endcan

    @can('exams.view.own')
        <section class="card" id="results" aria-labelledby="student-results-title">
            <div class="chead">
                <h2 id="student-results-title">نتایج آزمون‌ها</h2>
                <span class="pill">{{ $faNumber($results->count()) }} نتیجه‌ی اخیر</span>
            </div>

            <div class="tw">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th scope="col">آزمون</th>
                            <th scope="col">دوره یا کلاس</th>
                            <th scope="col">نمره</th>
                            <th scope="col">زمان ثبت</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($results as $result)
                            <tr>
                                <td>{{ $result->exam?->title ?? 'آزمون حذف‌شده' }}</td>
                                <td>{{ $result->exam?->course?->title ?? $result->exam?->classroom?->name ?? '—' }}</td>
                                <td>
                                    {{ $result->score === null
                                        ? 'در انتظار نمره'
                                        : $faNumber($result->score, 2).' / '.$faNumber($result->max_score, 2) }}
                                </td>
                                <td>{{ $result->synced_at?->translatedFormat('Y/m/d') ?? $result->created_at?->translatedFormat('Y/m/d') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="empty">هنوز نتیجه‌ای برای حساب شما ثبت نشده است.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endcan

    @can('resources.view.enrolled')
        <section class="card" id="resources" aria-labelledby="student-resources-title">
            <div class="chead">
                <h2 id="student-resources-title">جزوه‌ها و منابع آموزشی</h2>
                <span class="pill">{{ $faNumber($resource_count) }} منبع</span>
            </div>

            @if($resources->isNotEmpty())
                <div class="g3">
                    @foreach($resources as $resource)
                        <article class="cc">
                            <div class="th big t-c"><x-icon name="file"/></div>
                            <h3>{{ $resource->title }}</h3>
                            <p>{{ $resource->kind }}</p>
                            <span class="mu">
                                {{ $resource->is_free ? 'رایگان' : $faNumber((int) $resource->price).' تومان' }}
                            </span>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="empty">منبع منتشرشده‌ای برای کلاس‌های شما وجود ندارد.</div>
            @endif
        </section>
    @endcan

    @can('announcements.view')
        <section class="card" id="announcements" aria-labelledby="student-announcements-title">
            <div class="chead">
                <h2 id="student-announcements-title">اطلاعیه‌های آکادمی</h2>
                <span class="pill">{{ $faNumber($announcements->count()) }} اطلاعیه‌ی اخیر</span>
            </div>

            @forelse($announcements as $announcement)
                <article class="news">
                    <b>{{ $announcement->title }}</b>
                    <span class="mu">{{ $announcement->published_at?->diffForHumans() ?? '—' }}</span>
                    @if(filled($announcement->body))
                        <p>{{ \Illuminate\Support\Str::limit(strip_tags((string) $announcement->body), 160) }}</p>
                    @endif
                </article>
            @empty
                <div class="empty">اطلاعیه‌ای برای نمایش وجود ندارد.</div>
            @endforelse
        </section>
    @endcan
@endsection
