@extends('layouts.portal')

@section('title', 'کارتابل اولیا')

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

    <section class="hero" id="overview" aria-labelledby="guardian-welcome-title">
        <svg class="av" viewBox="0 0 120 120" aria-hidden="true">
            <use href="#avatar"></use>
        </svg>
        <div class="txt">
            <h2 id="guardian-welcome-title">سلام، {{ auth()->user()->name }}!</h2>
            @if($selectedChild)
                <p>گزارش آموزشی {{ $selectedChild->name }}، دانش‌آموز {{ $selectedChild->grade ?? 'بدون پایه‌ی ثبت‌شده' }}.</p>
                <a class="btn" href="#children">مشاهده‌ی فرزندان</a>
            @else
                <p>هنوز دانش‌آموزی به حساب شما متصل نشده است. برای اتصال فرزند با مدیر سامانه تماس بگیرید.</p>
            @endif
        </div>
    </section>

    @if($selectedChild)
        <section class="stats" aria-label="خلاصه‌ی وضعیت آموزشی فرزند">
            <x-stat tone="s1" icon="book" label="کلاس‌های فعال" :value="$faNumber($classroom_count)" hint="کلاس‌های فرزند انتخاب‌شده" href="#classes"/>
            <x-stat tone="s2" icon="task" label="تکالیف باز" :value="$faNumber($pending_homework_count)" hint="بدون پاسخ ثبت‌شده" href="#homework"/>
            <x-stat tone="s3" icon="cal" label="نرخ حضور" :value="$attendance_rate === null ? '—' : $faNumber($attendance_rate).'٪'" :hint="$faNumber($attendance_count).' جلسه‌ی ثبت‌شده'" href="#attendance"/>
            <x-stat tone="s4" icon="chart" label="نتایج اخیر" :value="$faNumber($results->count())" hint="نتایج ثبت‌شده برای فرزند" href="#results"/>
        </section>
    @else
        <section class="card">
            <div class="empty">پس از اتصال فرزند به حساب شما، گزارش‌های آموزشی در این بخش نمایش داده می‌شوند.</div>
        </section>
    @endif

    <section class="card" id="children" aria-labelledby="guardian-children-title">
        <div class="chead">
            <h2 id="guardian-children-title">فرزندان متصل‌شده</h2>
            <span class="pill">{{ $faNumber($children->count()) }} نفر</span>
        </div>

        @forelse($children as $child)
            <a class="trk" href="{{ route('guardian.dashboard', ['child' => $child->id]) }}" @if($selectedChild?->id === $child->id) aria-current="true" @endif>
                <div class="th t-a"><x-icon name="user"/></div>
                <div>
                    <b>{{ $child->name }}</b>
                    <span class="mu">{{ $child->grade ?? 'پایه ثبت نشده' }}</span>
                </div>
                @if($selectedChild?->id === $child->id)
                    <span class="tag">انتخاب‌شده</span>
                @else
                    <span class="pill">مشاهده</span>
                @endif
            </a>
        @empty
            <div class="empty">هنوز فرزندی به این حساب متصل نشده است.</div>
        @endforelse
    </section>

    @if($selectedChild)
        <section class="card" id="classes" aria-labelledby="guardian-classes-title">
            <div class="chead">
                <h2 id="guardian-classes-title">کلاس‌های فرزند</h2>
                <span class="pill">{{ $faNumber($classroom_count) }} کلاس</span>
            </div>

            @forelse($classrooms as $classroom)
                @php
                    $days = data_get($classroom->schedule, 'days', []);
                    $time = data_get($classroom->schedule, 'time');
                    $dayNames = [
                        'sat' => 'شنبه', 'sun' => 'یکشنبه', 'mon' => 'دوشنبه',
                        'tue' => 'سه‌شنبه', 'wed' => 'چهارشنبه', 'thu' => 'پنجشنبه', 'fri' => 'جمعه',
                    ];
                @endphp
                <div class="trk">
                    <div class="th t-b"><x-icon name="book"/></div>
                    <div>
                        <b>{{ $classroom->name }}</b>
                        <span class="mu">{{ $classroom->course?->title ?? 'بدون دوره‌ی متصل' }} · مدرس: {{ $classroom->teacher?->name ?? 'تعیین نشده' }}</span>
                        @if($days || $time)
                            <span class="mu">
                                {{ collect($days)->map(fn ($day) => $dayNames[$day] ?? null)->filter()->join('، ') }}
                                @if($time) · ساعت {{ \App\Support\PersianDate::time($time) }}@endif
                            </span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="empty">کلاس فعالی برای فرزند انتخاب‌شده ثبت نشده است.</div>
            @endforelse
        </section>

        @can('homework.view.children')
            <section class="card" id="homework" aria-labelledby="guardian-homework-title">
                <div class="chead">
                    <h2 id="guardian-homework-title">تکالیف فرزند</h2>
                    <span class="pill">{{ $faNumber($pending_homework_count) }} تکلیف باز</span>
                </div>
                <div class="tw">
                    <table class="tbl">
                        <thead><tr><th scope="col">عنوان تکلیف</th><th scope="col">کلاس</th><th scope="col">مهلت</th><th scope="col">وضعیت</th></tr></thead>
                        <tbody>
                            @forelse($homeworks as $homework)
                                <tr>
                                    <td>{{ $homework->title }}</td>
                                    <td>{{ $homework->classroom?->name ?? '—' }}</td>
                                    <td>{{ $homework->due_at ? \App\Support\PersianDate::format($homework->due_at, 'yyyy/MM/dd HH:mm') : 'بدون مهلت' }}</td>
                                    <td>
                                        @if($homework->submitted_by_child_count > 0)
                                            <span class="tag">ارسال شده</span>
                                        @elseif($homework->due_at && $homework->due_at->isPast())
                                            <span class="tag r">مهلت گذشته</span>
                                        @else
                                            <span class="tag w">نیازمند انجام</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="empty">تکلیف منتشرشده‌ای برای فرزند شما ثبت نشده است.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endcan

        @can('attendance.view.children')
            <section class="card" id="attendance" aria-labelledby="guardian-attendance-title">
                <div class="chead">
                    <h2 id="guardian-attendance-title">حضور و غیاب</h2>
                    <span class="pill">{{ $attendance_rate === null ? 'بدون داده' : $faNumber($attendance_rate).'٪ حضور' }}</span>
                </div>
                <div class="tw">
                    <table class="tbl">
                        <thead><tr><th scope="col">کلاس</th><th scope="col">تاریخ</th><th scope="col">وضعیت</th></tr></thead>
                        <tbody>
                            @forelse($attendance as $record)
                                <tr>
                                    <td>{{ $record->classroom?->name ?? '—' }}</td>
                                    <td>{{ \App\Support\PersianDate::format($record->session_date, 'yyyy/MM/dd') }}</td>
                                    <td>
                                        @if($record->status === 'present') <span class="tag">حاضر</span>
                                        @elseif($record->status === 'late') <span class="tag w">با تأخیر</span>
                                        @else <span class="tag r">غایب</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="empty">رکورد حضور و غیابی ثبت نشده است.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endcan

        @can('exams.view.children')
            <section class="card" id="results" aria-labelledby="guardian-results-title">
                <div class="chead">
                    <h2 id="guardian-results-title">نتایج آزمون‌ها</h2>
                    <span class="pill">{{ $faNumber($results->count()) }} نتیجه‌ی اخیر</span>
                </div>
                <div class="tw">
                    <table class="tbl">
                        <thead><tr><th scope="col">آزمون</th><th scope="col">درس / کلاس</th><th scope="col">نمره</th><th scope="col">تاریخ ثبت</th></tr></thead>
                        <tbody>
                            @forelse($results as $result)
                                <tr>
                                    <td>{{ $result->exam?->title ?? 'آزمون حذف‌شده' }}</td>
                                    <td>{{ $result->exam?->course?->title ?? $result->exam?->classroom?->name ?? '—' }}</td>
                                    <td>{{ $result->score === null ? 'در انتظار نمره' : $faNumber($result->score, 2).' / '.$faNumber($result->max_score, 2) }}</td>
                                    <td>{{ \App\Support\PersianDate::format($result->synced_at ?? $result->created_at, 'yyyy/MM/dd') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="empty">نتیجه‌ای برای فرزند شما ثبت نشده است.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endcan

        @can('resources.view.enrolled')
            <section class="card" id="resources" aria-labelledby="guardian-resources-title">
                <div class="chead">
                    <h2 id="guardian-resources-title">منابع آموزشی</h2>
                    <span class="pill">{{ $faNumber($resource_count) }} منبع</span>
                </div>
                @forelse($resources as $resource)
                    <div class="trk">
                        <div class="th t-c"><x-icon name="file"/></div>
                        <div>
                            <b>{{ $resource->title }}</b>
                            <span class="mu">{{ $resource->kind }} · {{ $resource->is_free ? 'رایگان' : $faNumber((int) $resource->price).' تومان' }}</span>
                        </div>
                    </div>
                @empty
                    <div class="empty">منبع آموزشی منتشرشده‌ای برای فرزند شما وجود ندارد.</div>
                @endforelse
            </section>
        @endcan
    @endif

    @can('announcements.view')
        <section class="card" id="announcements" aria-labelledby="guardian-announcements-title">
            <div class="chead">
                <h2 id="guardian-announcements-title">اطلاعیه‌های آکادمی</h2>
                <span class="pill">{{ $faNumber($announcements->count()) }} اطلاعیه‌ی اخیر</span>
            </div>
            @forelse($announcements as $announcement)
                <article class="news">
                    <b>{{ $announcement->title }}</b>
                    <span class="mu">{{ $announcement->published_at?->locale('fa')->diffForHumans() ?? '—' }}</span>
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
