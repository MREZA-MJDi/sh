@extends('layouts.student')

@section('title', 'مرکز یادگیری')

@section('content')
    @php
        $faNumber = static fn ($value, int $precision = 0): string =>
            \App\Support\PersianDate::toPersianDigits(number_format((float) $value, $precision, '٫', '٬'));

        $firstPendingHomework = $homeworks->first(
            fn ($homework) => $homework->submissions->isEmpty()
        );

        if ($firstPendingHomework) {
            $nextAction = [
                'eyebrow' => 'قدم بعدی یادگیری',
                'title' => 'یک تکلیف جدید برای ارسال داری',
                'description' => 'تکلیف «' . $firstPendingHomework->title . '» هنوز پاسخی ندارد. آن را باز کن و پاسخ خودت را قبل از پایان مهلت ثبت کن.',
                'label' => 'رفتن به تکالیف',
                'href' => '#homework',
                'icon' => 'task',
            ];
        } elseif ($classrooms->isNotEmpty()) {
            $nextAction = [
                'eyebrow' => 'ادامه مسیر آموزشی',
                'title' => 'کلاس‌های فعال خودت را دنبال کن',
                'description' => 'فهرست کلاس‌ها و مدرس‌های متصل به حساب تو در همین صفحه در دسترس است.',
                'label' => 'مشاهده کلاس‌های من',
                'href' => '#classes',
                'icon' => 'book',
            ];
        } elseif ($resources->isNotEmpty()) {
            $nextAction = [
                'eyebrow' => 'منابع آموزشی',
                'title' => 'برای شروع، منابع آموزشی را ببین',
                'description' => 'جزوه‌ها و فایل‌هایی که برای حساب تو منتشر شده‌اند از این بخش قابل دریافت هستند.',
                'label' => 'مشاهده منابع',
                'href' => '#resources',
                'icon' => 'file',
            ];
        } else {
            $nextAction = [
                'eyebrow' => 'خوش آمدی',
                'title' => 'مسیر یادگیری تو از اینجا شروع می‌شود',
                'description' => 'به‌محض اتصال کلاس یا انتشار تکلیف و منبع آموزشی، اطلاعات آن در این کارتابل نمایش داده می‌شود.',
                'label' => 'رفتن به ابتدای صفحه',
                'href' => '#overview',
                'icon' => 'rocket',
            ];
        }
    @endphp

    <section class="hero student-welcome" id="overview" aria-labelledby="student-welcome-title">
        <div class="txt">
            <span class="student-welcome__eyebrow">کارتابل آموزشی شخصی تو</span>
            <h2 id="student-welcome-title">سلام، {{ auth()->user()->name }}!</h2>
            <p>وضعیت کلاس‌ها، تکالیف، حضور و نتایج آموزشی‌ات را یک‌جا دنبال کن.</p>
            <div class="student-welcome__actions">
                <a class="btn" href="{{ $nextAction['href'] }}">{{ $nextAction['label'] }}</a>
                @can('messages.view.own')
                    <a class="btn g" href="{{ route('student.messages.index') }}">پیام‌های من</a>
                @endcan
            </div>
        </div>
        <div class="student-welcome__art" aria-hidden="true">
            <div class="student-welcome__art-orbit"></div>
            <div class="student-welcome__art-icon"><x-icon name="book"/></div>
            <span class="student-welcome__art-dot student-welcome__art-dot--one"></span>
            <span class="student-welcome__art-dot student-welcome__art-dot--two"></span>
        </div>
    </section>

    <section class="student-next-step card" aria-labelledby="student-next-step-title">
        <div class="student-next-step__icon"><x-icon :name="$nextAction['icon']"/></div>
        <div class="student-next-step__copy">
            <span class="student-section-eyebrow">{{ $nextAction['eyebrow'] }}</span>
            <h2 id="student-next-step-title">{{ $nextAction['title'] }}</h2>
            <p>{{ $nextAction['description'] }}</p>
        </div>
        <a class="btn s" href="{{ $nextAction['href'] }}">{{ $nextAction['label'] }}</a>
    </section>

    @can('announcements.view')
        <section class="card student-announcements" id="announcements" aria-labelledby="student-announcements-preview-title">
                <div class="chead">
                    <div>
                        <span class="student-section-eyebrow">از طرف آموزشگاه</span>
                        <h2 id="student-announcements-preview-title">اطلاعیه‌های مهم</h2>
                    </div>
                    <span class="pill">{{ $faNumber($announcements->count()) }} اطلاعیه</span>
                </div>
                <div class="student-announcements__list">
                    @forelse ($announcements as $announcement)
                        <article class="student-announcement">
                            <span class="student-announcement__icon"><x-icon name="bell"/></span>
                            <div class="student-announcement__copy">
                                <h3>{{ $announcement->title }}</h3>
                                @if (filled($announcement->body))
                                    <p>{{ \Illuminate\Support\Str::limit(strip_tags((string) $announcement->body), 150) }}</p>
                                @endif
                                <time datetime="{{ $announcement->published_at?->toIso8601String() }}">
                                    {{ \App\Support\PersianDate::format($announcement->published_at, 'l، d F Y ساعت H:i') }}
                                </time>
                            </div>
                        </article>
                    @empty
                        <div class="empty">در حال حاضر اطلاعیه‌ای برای نمایش وجود ندارد.</div>
                    @endforelse
                </div>
        </section>
    @endcan

    <section class="stats student-dashboard-stats" aria-label="خلاصه وضعیت آموزشی">
        <x-stat tone="s1" icon="book" label="کلاس‌های فعال" :value="$faNumber($classroom_count)" hint="کلاس‌های متصل به حساب تو" href="#classes"/>
        <x-stat tone="s2" icon="task" label="تکالیف باز" :value="$faNumber($pending_homework_count)" hint="تکالیف بدون پاسخ ثبت‌شده" href="#homework"/>
        <x-stat tone="s3" icon="cal" label="نرخ حضور" :value="$attendance_rate === null ? '—' : $faNumber($attendance_rate).'٪'" :hint="$faNumber($attendance_count).' جلسه ثبت‌شده'" href="#attendance"/>
        <x-stat tone="s4" icon="chart" label="نتایج اخیر" :value="$faNumber($results->count())" hint="نتایج قابل مشاهده آزمون‌ها" href="#results"/>
    </section>

    @can('lessons.view')
        <section class="card student-course-progress" id="course-progress" aria-labelledby="student-course-progress-title">
            <div class="chead">
                <div><span class="student-section-eyebrow">مسیر یادگیری</span><h2 id="student-course-progress-title">پیشرفت دوره‌های من</h2></div>
                <a class="btn g s" href="{{ route('student.courses.index') }}">همه دوره‌ها</a>
            </div>
            <div class="student-course-progress__list">
                @forelse ($course_progress as $courseProgress)
                    <a class="student-course-progress__item" href="{{ route('student.courses.show', $courseProgress->id) }}">
                        <div class="student-course-progress__head">
                            <strong>{{ $courseProgress->title }}</strong>
                            <span>{{ $faNumber($courseProgress->progress_percent) }}٪</span>
                        </div>
                        <div class="student-attendance-meter__track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $courseProgress->progress_percent }}">
                            <span style="width: {{ max(0, min(100, (int) $courseProgress->progress_percent)) }}%"></span>
                        </div>
                        <small class="mu">{{ $faNumber($courseProgress->completed_lessons) }} از {{ $faNumber($courseProgress->total_lessons) }} درس تکمیل‌شده</small>
                    </a>
                @empty
                    <div class="empty">برای دوره‌های ثبت‌نام‌شده هنوز درس منتشرشده‌ای ثبت نشده است.</div>
                @endforelse
            </div>
        </section>
    @endcan

    <div class="row student-dashboard-primary">
        @can('classes.view.enrolled')
            <section class="card" id="classes" aria-labelledby="student-classes-title">
                <div class="chead">
                    <div>
                        <span class="student-section-eyebrow">برنامه آموزشی</span>
                        <h2 id="student-classes-title">کلاس‌های من</h2>
                    </div>
                    <span class="pill">{{ $faNumber($classroom_count) }} کلاس</span>
                </div>
                <div class="student-class-list">
                    @forelse ($classrooms as $classroom)
                        <article class="student-class-item">
                            <span class="student-class-item__icon"><x-icon name="book"/></span>
                            <div class="student-class-item__body">
                                <h3>{{ $classroom->name }}</h3>
                                <p>{{ $classroom->course?->title ?? 'بدون دوره متصل' }}</p>
                                <span class="mu">
                                    مدرس: {{ $classroom->teacher?->name ?? 'تعیین نشده' }}
                                    @if ($classroom->course?->subject)
                                        · {{ $classroom->course->subject }}
                                    @endif
                                    @if ($classroom->course?->grade)
                                        · پایه {{ $classroom->course->grade }}
                                    @endif
                                </span>
                            </div>
                            <span class="tag">فعال</span>
                        </article>
                    @empty
                        <div class="empty">هنوز کلاسی برای این حساب ثبت نشده است.</div>
                    @endforelse
                </div>
            </section>
        @endcan

        @can('homework.view.own')
            <section class="card" id="homework" aria-labelledby="student-homework-title">
                <div class="chead">
                    <div>
                        <span class="student-section-eyebrow">کارهای در انتظار</span>
                        <h2 id="student-homework-title">تکالیف من و مهلت‌ها</h2>
                    </div>
                    <span class="pill">{{ $faNumber($pending_homework_count) }} تکلیف باز</span>
                </div>
                <div class="student-homework-list">
                    @forelse ($homeworks as $homework)
                        @php($submission = $homework->submissions->first())
                        <article class="student-homework-item">
                            <div class="student-homework-item__head">
                                <div>
                                    <h3>{{ $homework->title }}</h3>
                                    <span class="mu">{{ $homework->classroom?->name ?? 'کلاس آموزشی' }}</span>
                                </div>
                                @if ($submission?->reviewed_at !== null || $submission?->status === 'reviewed')
                                    <span class="tag d">بررسی‌شده</span>
                                @elseif ($submission)
                                    <span class="tag">ارسال شده</span>
                                @elseif ($homework->due_at?->isPast())
                                    <span class="tag r">مهلت گذشته</span>
                                @else
                                    <span class="tag w">در انتظار ارسال</span>
                                @endif
                            </div>
                            @if (filled($homework->instructions))
                                <p class="student-homework-item__description">{{ \Illuminate\Support\Str::limit(strip_tags((string) $homework->instructions), 170) }}</p>
                            @endif
                            <div class="student-homework-item__footer">
                                <span class="mu">
                                    مهلت:
                                    {{ $homework->due_at ? \App\Support\PersianDate::format($homework->due_at, 'l، d F Y ساعت H:i') : 'بدون مهلت' }}
                                </span>
                            </div>

                            @if ($submission?->reviewed_at !== null || $submission?->status === 'reviewed')
                                <div class="student-homework-item__review" role="status">
                                    <div class="student-homework-item__review-head">
                                        <strong>نتیجه بررسی مدرس</strong>
                                        @if ($submission->score !== null)
                                            <span class="student-homework-item__score">نمره {{ $faNumber($submission->score, 2) }}</span>
                                        @endif
                                    </div>
                                    @if (filled($submission->feedback))
                                        <p>{{ $submission->feedback }}</p>
                                    @else
                                        <p>تکلیف بررسی شده است؛ بازخورد متنی ثبت نشده است.</p>
                                    @endif
                                </div>
                            @endif

                            @if ($submission)
                                @foreach ($submission->files as $file)
                                    <div class="trk">
                                        <div class="th t-c"><x-icon name="file"/></div>
                                        <div><b>{{ $file->original_name }}</b><span class="mu">{{ $faNumber(round($file->size / 1048576, 1), 1) }} مگابایت</span></div>
                                        <a class="btn g s" href="{{ route('student.homework.submission-files.download', $file) }}">دریافت فایل</a>
                                    </div>
                                @endforeach
                                @if (filled($submission->answer))
                                    <p class="student-homework-item__answer">{{ $submission->answer }}</p>
                                @endif
                            @endif

                            @can('homework.submit')
                                @if (! $submission || ($submission->reviewed_at === null && ! in_array($submission->status, ['reviewed', 'graded'], true)))
                                    <details class="student-homework-item__details">
                                        <summary class="btn g s">{{ $submission ? 'ویرایش پاسخ' : 'ارسال پاسخ' }}</summary>
                                        <form method="POST" action="{{ route('student.homework.submissions.store', $homework) }}" enctype="multipart/form-data" class="stack">
                                            @csrf
                                            <label class="lbl" for="answer-{{ $homework->id }}">پاسخ متنی</label>
                                            <textarea class="fld" id="answer-{{ $homework->id }}" name="answer" rows="3" maxlength="12000">{{ old('answer', $submission?->answer) }}</textarea>
                                            <label class="lbl" for="files-{{ $homework->id }}">پیوست‌ها (حداکثر ۵ فایل، هرکدام تا ۱۰ مگابایت)</label>
                                            <input class="fld" id="files-{{ $homework->id }}" type="file" name="files[]" multiple accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png">
                                            <button class="btn s" type="submit">ثبت پاسخ</button>
                                        </form>
                                        @if ($submission)
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
                        <div class="empty">تکلیف منتشرشده‌ای برای کلاس‌های تو ثبت نشده است.</div>
                    @endforelse
                </div>
            </section>
        @endcan
    </div>

    <div class="row student-dashboard-secondary">
        @can('attendance.view.own')
            <section class="card" id="attendance" aria-labelledby="student-attendance-title">
                <div class="chead">
                    <div>
                        <span class="student-section-eyebrow">حضور در کلاس‌ها</span>
                        <h2 id="student-attendance-title">گزارش حضور و غیاب</h2>
                    </div>
                    <span class="pill">{{ $attendance_rate === null ? 'بدون داده' : $faNumber($attendance_rate).'٪ حضور' }}</span>
                </div>
                @if ($attendance_rate !== null)
                    <div class="student-attendance-meter">
                        <div class="student-attendance-meter__labels">
                            <span>میزان حضور ثبت‌شده</span>
                            <strong>{{ $faNumber($attendance_rate) }}٪</strong>
                        </div>
                        <div class="student-attendance-meter__track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $attendance_rate }}" aria-label="نرخ حضور">
                            <span style="width: {{ max(0, min(100, $attendance_rate)) }}%"></span>
                        </div>
                    </div>
                @endif
                <div class="tw">
                    <table class="tbl">
                        <thead><tr><th scope="col">کلاس</th><th scope="col">تاریخ جلسه</th><th scope="col">وضعیت</th></tr></thead>
                        <tbody>
                            @forelse ($attendance as $record)
                                <tr>
                                    <td>{{ $record->classroom?->name ?? '—' }}</td>
                                    <td>{{ \App\Support\PersianDate::format($record->session_date, 'l، d F Y') }}</td>
                                    <td>
                                        @if ($record->status === 'present')<span class="tag">حاضر</span>
                                        @elseif ($record->status === 'late')<span class="tag w">با تأخیر</span>
                                        @else<span class="tag r">غایب</span>@endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="empty">رکوردی از حضور و غیاب برای تو ثبت نشده است.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endcan

        @can('exams.view.own')
            <section class="card" id="results" aria-labelledby="student-results-title">
                <div class="chead">
                    <div>
                        <span class="student-section-eyebrow">ارزیابی آموزشی</span>
                        <h2 id="student-results-title">نتایج آزمون‌ها</h2>
                    </div>
                    <span class="pill">{{ $faNumber($results->count()) }} نتیجه اخیر</span>
                </div>
                <div class="tw">
                    <table class="tbl">
                        <thead><tr><th scope="col">آزمون</th><th scope="col">دوره یا کلاس</th><th scope="col">نمره</th><th scope="col">زمان ثبت</th></tr></thead>
                        <tbody>
                            @forelse ($results as $result)
                                <tr>
                                    <td>{{ $result->exam?->title ?? 'آزمون حذف‌شده' }}</td>
                                    <td>{{ $result->exam?->course?->title ?? $result->exam?->classroom?->name ?? '—' }}</td>
                                    <td>
                                {{ $result->score === null ? 'در انتظار نمره' : $faNumber($result->score, 2).' / '.$faNumber($result->max_score, 2) }}
                                @if (filled($result->feedback))
                                    <div class="mu">بازخورد مدرس: {{ $result->feedback }}</div>
                                @endif
                            </td>
                                    <td>{{ \App\Support\PersianDate::format($result->synced_at ?? $result->created_at, 'Y/m/d') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="empty">هنوز نتیجه‌ای برای حساب تو ثبت نشده است.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endcan
    </div>

    @can('resources.view.enrolled')
        <section class="card" id="resources" aria-labelledby="student-resources-title">
            <div class="chead">
                <div>
                    <span class="student-section-eyebrow">مطالعه و مرور</span>
                    <h2 id="student-resources-title">جزوه‌ها و منابع آموزشی</h2>
                </div>
                <span class="pill">{{ $faNumber($resource_count) }} منبع</span>
            </div>
            <div class="g3 student-resource-grid">
                @forelse ($resources as $resource)
                    <article class="cc student-resource-card">
                        <div class="th big t-c"><x-icon name="file"/></div>
                        <h3>{{ $resource->title }}</h3>
                        <p>{{ $resource->kind }}</p>
                        <span class="mu">{{ $resource->is_free ? 'رایگان' : $faNumber((int) $resource->price).' تومان' }}</span>
                        @if ($resource->file_path)
                            <a class="btn g s" href="{{ route('student.resources.download', $resource) }}">دریافت منبع</a>
                        @endif
                    </article>
                @empty
                    <div class="empty">منبع منتشرشده‌ای برای کلاس‌های تو وجود ندارد.</div>
                @endforelse
            </div>
        </section>
    @endcan



    @can('notes.manage.own')
        <section class="card" id="notes" aria-labelledby="student-notes-title">
            <div class="chead">
                <div>
                    <span class="student-section-eyebrow">یادداشت‌های شخصی</span>
                    <h2 id="student-notes-title">یادداشت‌های من</h2>
                </div>
            </div>
            <form method="POST" action="{{ route('student.notes.store') }}" class="stack">
                @csrf
                <label class="sr" for="new-note">یادداشت جدید</label>
                <textarea class="fld" id="new-note" name="body" rows="2" maxlength="2000" placeholder="یادداشت جدید..."></textarea>
                <div class="frm"><button class="btn s" type="submit">افزودن یادداشت</button></div>
            </form>
            <div class="g3 student-note-grid">
                @forelse ($notes as $note)
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
                    <div class="empty">هنوز یادداشتی ثبت نکرده‌ای.</div>
                @endforelse
            </div>
        </section>
    @endcan
@endsection
