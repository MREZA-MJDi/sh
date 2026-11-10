@extends('layouts.student')

@section('title', 'مرکز یادگیری')

@section('content')
    @php
        $faNumber = static fn ($value, int $precision = 0): string =>
            \App\Support\PersianDate::toPersianDigits(number_format((float) $value, $precision, '٫', '٬'));
    @endphp

    <section class="hero" id="overview" aria-labelledby="student-welcome-title">
        <svg class="av" viewBox="0 0 120 120" aria-hidden="true"><use href="#avatar"></use></svg>
        <div class="txt">
            <h2 id="student-welcome-title">سلام، {{ auth()->user()->name }}!</h2>
            <p>{{ $faNumber($classroom_count) }} کلاس فعال و {{ $faNumber($pending_homework_count) }} تکلیف باز در کارتابل شما ثبت شده است.</p>
            <a class="btn" href="#classes">مشاهده‌ی کلاس‌های من</a>
            @can('homework.view.own')<a class="btn g" href="#homework">پیگیری تکالیف</a>@endcan
        </div>
    </section>

    <section class="stats" aria-label="خلاصه‌ی وضعیت آموزشی">
        <x-stat tone="s1" icon="book" label="کلاس‌های فعال" :value="$faNumber($classroom_count)" hint="کلاس‌های ثبت‌شده برای شما" href="#classes"/>
        <x-stat tone="s2" icon="task" label="تکالیف باز" :value="$faNumber($pending_homework_count)" hint="تکالیف بدون پاسخ ثبت‌شده" href="#homework"/>
        <x-stat tone="s3" icon="cal" label="نرخ حضور" :value="$attendance_rate === null ? '—' : $faNumber($attendance_rate).'٪'" :hint="$faNumber($attendance_count).' جلسه‌ی ثبت‌شده'" href="#attendance"/>
        <x-stat tone="s4" icon="chart" label="نتایج اخیر" :value="$faNumber($results->count())" hint="آخرین نتایج قابل مشاهده" href="#results"/>
    </section>

    @can('classes.view.enrolled')
        <section class="card" id="classes" aria-labelledby="student-classes-title">
            <div class="chead"><h2 id="student-classes-title">کلاس‌های من</h2><span class="pill">{{ $faNumber($classroom_count) }} کلاس</span></div>
            @if($classrooms->isNotEmpty())
                <div class="g3">
                    @foreach($classrooms as $classroom)
                        @php $tones = ['t-a', 't-b', 't-c', 't-d']; @endphp
                        <article class="cc">
                            <div class="th big {{ $tones[$loop->index % count($tones)] }}"><x-icon name="book"/></div>
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
            <div class="chead"><h2 id="student-homework-title">تکالیف من</h2><span class="pill">{{ $faNumber($pending_homework_count) }} تکلیف باز</span></div>
            <div class="stack">
                @forelse($homeworks as $homework)
                    @php $submission = $homework->submissions->first(); @endphp
                    <article class="card cc">
                        <div class="chead">
                            <div><h3>{{ $homework->title }}</h3><span class="mu">{{ $homework->classroom?->name ?? '—' }}</span></div>
                            @if($submission?->status === 'graded')
                                <span class="tag d">بررسی‌شده</span>
                            @elseif($submission)
                                <span class="tag">ارسال شده</span>
                            @elseif($homework->due_at?->isPast())
                                <span class="tag r">مهلت گذشته</span>
                            @else
                                <span class="tag w">در انتظار ارسال</span>
                            @endif
                        </div>
                        <p>{{ $homework->instructions }}</p>
                        <span class="mu">مهلت: {{ $homework->due_at ? \App\Support\PersianDate::format($homework->due_at, 'l، d F Y ساعت H:i') : 'بدون مهلت' }}</span>

                        @if($submission?->status === 'graded')
                            @if($submission->score !== null)<p><b>نمره: {{ $faNumber($submission->score, 2) }}</b></p>@endif
                            @if(filled($submission->feedback))<div class="info">{{ $submission->feedback }}</div>@endif
                        @elseif($submission)
                            @foreach($submission->files as $file)
                                <div class="trk">
                                    <div class="th t-c"><x-icon name="file"/></div>
                                    <div><b>{{ $file->original_name }}</b><span class="mu">{{ $faNumber(round($file->size / 1048576, 1), 1) }} مگابایت</span></div>
                                    <a class="btn g s" href="{{ route('student.homework.submission-files.download', $file) }}">دریافت فایل</a>
                                </div>
                            @endforeach
                            <p>{{ $submission->answer }}</p>
                        @endif

                        @can('homework.submit')
                            @if(! $submission || ($submission->reviewed_at === null && $submission->status !== 'graded'))
                                <details>
                                    <summary class="btn g s">{{ $submission ? 'ویرایش پاسخ' : 'ارسال پاسخ' }}</summary>
                                    <form method="POST" action="{{ route('student.homework.submissions.store', $homework) }}" enctype="multipart/form-data" class="stack">
                                        @csrf
                                        <label class="lbl" for="answer-{{ $homework->id }}">پاسخ متنی</label>
                                        <textarea class="fld" id="answer-{{ $homework->id }}" name="answer" rows="3" maxlength="12000">{{ old('answer', $submission?->answer) }}</textarea>
                                        <label class="lbl" for="files-{{ $homework->id }}">پیوست‌ها (حداکثر ۵ فایل، هرکدام تا ۱۰ مگابایت)</label>
                                        <input class="fld" id="files-{{ $homework->id }}" type="file" name="files[]" multiple accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png">
                                        <button class="btn s" type="submit">ثبت پاسخ</button>
                                    </form>
                                    @if($submission)
                                        <form method="POST" action="{{ route('student.homework.submissions.destroy', $homework) }}" onsubmit="return confirm('پاسخ این تکلیف حذف شود؟')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn bad s" type="submit">حذف پاسخ</button>
                                        </form>
                                    @endif
                                </details>
                            @endif
                        @endcan
                    </article>
                @empty
                    <div class="empty">تکلیف منتشرشده‌ای برای کلاس‌های شما ثبت نشده است.</div>
                @endforelse
            </div>
        </section>
    @endcan

    @can('attendance.view.own')
        <section class="card" id="attendance" aria-labelledby="student-attendance-title">
            <div class="chead"><h2 id="student-attendance-title">حضور و غیاب</h2><span class="pill">{{ $attendance_rate === null ? 'بدون داده' : $faNumber($attendance_rate).'٪ حضور' }}</span></div>
            <div class="tw">
                <table class="tbl">
                    <thead><tr><th scope="col">کلاس</th><th scope="col">تاریخ جلسه</th><th scope="col">وضعیت</th></tr></thead>
                    <tbody>
                    @forelse($attendance as $record)
                        <tr>
                            <td>{{ $record->classroom?->name ?? '—' }}</td>
                            <td>{{ \App\Support\PersianDate::format($record->session_date, 'l، d F Y') }}</td>
                            <td>
                                @if($record->status === 'present')<span class="tag">حاضر</span>
                                @elseif($record->status === 'late')<span class="tag w">با تأخیر</span>
                                @else<span class="tag r">غایب</span>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="empty">رکوردی از حضور و غیاب برای شما ثبت نشده است.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endcan

    @can('exams.view.own')
        <section class="card" id="results" aria-labelledby="student-results-title">
            <div class="chead"><h2 id="student-results-title">نتایج آزمون‌ها</h2><span class="pill">{{ $faNumber($results->count()) }} نتیجه‌ی اخیر</span></div>
            <div class="tw">
                <table class="tbl">
                    <thead><tr><th scope="col">آزمون</th><th scope="col">دوره یا کلاس</th><th scope="col">نمره</th><th scope="col">زمان ثبت</th></tr></thead>
                    <tbody>
                    @forelse($results as $result)
                        <tr>
                            <td>{{ $result->exam?->title ?? 'آزمون حذف‌شده' }}</td>
                            <td>{{ $result->exam?->course?->title ?? $result->exam?->classroom?->name ?? '—' }}</td>
                            <td>{{ $result->score === null ? 'در انتظار نمره' : $faNumber($result->score, 2).' / '.$faNumber($result->max_score, 2) }}</td>
                            <td>{{ \App\Support\PersianDate::format($result->synced_at ?? $result->created_at, 'Y/m/d') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty">هنوز نتیجه‌ای برای حساب شما ثبت نشده است.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endcan

    @can('resources.view.enrolled')
        <section class="card" id="resources" aria-labelledby="student-resources-title">
            <div class="chead"><h2 id="student-resources-title">جزوه‌ها و منابع آموزشی</h2><span class="pill">{{ $faNumber($resource_count) }} منبع</span></div>
            <div class="g3">
                @forelse($resources as $resource)
                    <article class="cc">
                        <div class="th big t-c"><x-icon name="file"/></div>
                        <h3>{{ $resource->title }}</h3>
                        <p>{{ $resource->kind }}</p>
                        <span class="mu">{{ $resource->is_free ? 'رایگان' : $faNumber((int) $resource->price).' تومان' }}</span>
                    </article>
                @empty
                    <div class="empty">منبع منتشرشده‌ای برای کلاس‌های شما وجود ندارد.</div>
                @endforelse
            </div>
        </section>
    @endcan

    @can('announcements.view')
        <section class="card" id="announcements" aria-labelledby="student-announcements-title">
            <div class="chead"><h2 id="student-announcements-title">اطلاعیه‌های آکادمی</h2></div>
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
    @endcan

    @can('notes.manage.own')
        <section class="card" id="notes" aria-labelledby="student-notes-title">
            <div class="chead"><h2 id="student-notes-title">یادداشت‌های من</h2></div>
            <form method="POST" action="{{ route('student.notes.store') }}" class="stack">
                @csrf
                <label class="sr" for="new-note">یادداشت جدید</label>
                <textarea class="fld" id="new-note" name="body" rows="2" maxlength="2000" placeholder="یادداشت جدید..."></textarea>
                <div class="frm"><button class="btn s" type="submit">افزودن یادداشت</button></div>
            </form>
            <div class="g3">
                @forelse($notes as $note)
                    <article class="note {{ $note->tone }}">
                        <p>{{ $note->body }}</p>
                        <span class="mu">{{ \App\Support\PersianDate::format($note->created_at, 'Y/m/d') }}</span>
                        <details>
                            <summary class="btn g s">ویرایش</summary>
                            <form method="POST" action="{{ route('student.notes.update', $note) }}" class="stack">
                                @csrf
                                @method('PATCH')
                                <textarea class="fld" name="body" rows="3" maxlength="2000" required>{{ $note->body }}</textarea>
                                <button class="btn s" type="submit">ذخیره</button>
                            </form>
                        </details>
                        <form method="POST" action="{{ route('student.notes.destroy', $note) }}" onsubmit="return confirm('یادداشت حذف شود؟')">
                            @csrf
                            @method('DELETE')
                            <button type="submit">حذف</button>
                        </form>
                    </article>
                @empty
                    <div class="empty">هنوز یادداشتی ثبت نکرده‌اید.</div>
                @endforelse
            </div>
        </section>
    @endcan
@endsection
