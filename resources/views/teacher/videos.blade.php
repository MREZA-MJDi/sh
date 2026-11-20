@extends('teacher.layouts.app')

@section('title', 'ویدئوهای آموزشی')

@section('content')
@php
    $seasonLabels = ['spring' => 'بهار', 'summer' => 'تابستان', 'autumn' => 'پاییز', 'winter' => 'زمستان'];
@endphp

<div class="teacher-page-head">
    <div>
        <span class="teacher-eyebrow">محتوای آموزشی</span>
        <h1>بارگذاری فیلم‌ها</h1>
        <p>ویدئو فقط به دوره‌ها و کلاس‌های تخصیص‌یافته به شما متصل می‌شود. فایل‌ها در فضای خصوصی ذخیره و به‌صورت محلی پردازش می‌شوند؛ اتصال SpotPlayer موجود به API رسمی نیاز دارد.</p>
    </div>
</div>

<div class="g g2">
    <section class="c">
        <h2>ثبت ویدئوی جدید</h2>
        <form class="teacher-form" data-learning-scope method="POST" action="{{ route('teacher.spotplayer.videos.store') }}" enctype="multipart/form-data">
            @csrf
            <label>عنوان ویدئو</label>
            <input name="title" maxlength="180" required value="{{ old('title') }}">

            <label>دوره</label>
            <select name="course_id" required>
                <option value="">انتخاب دوره</option>
                @foreach($courses as $course)
                    <option value="{{ $course->id }}" @selected((string) old('course_id') === (string) $course->id)>
                        {{ $course->title }}
                        @if($course->subject) · {{ $course->subject }}@endif
                        @if($course->grade) · پایه {{ $course->grade }}@endif
                        @if($course->season) · {{ $seasonLabels[$course->season] ?? $course->season }}@endif
                    </option>
                @endforeach
            </select>

            <label>کلاس</label>
            <select name="classroom_id">
                <option value="">کل دوره</option>
                @foreach($classrooms as $classroom)
                    <option value="{{ $classroom->id }}" @selected((string) old('classroom_id') === (string) $classroom->id) data-course-id="{{ $classroom->course_id }}">{{ $classroom->name }}</option>
                @endforeach
            </select>

            <label>جلسه</label>
            <select name="class_session_id">
                <option value="">انتخاب جلسه</option>
                @foreach($classrooms as $classroom)
                    @foreach($classroom->sessions as $session)
                        <option value="{{ $session->id }}" @selected((string) old('class_session_id') === (string) $session->id) data-classroom-id="{{ $classroom->id }}">
                            {{ $session->title ?: $classroom->name }} · {{ $session->starts_at?->format('Y/m/d H:i') }}
                        </option>
                    @endforeach
                @endforeach
            </select>

            <label>شناسه ویدئوی SpotPlayer (در صورت نداشتن فایل)</label>
            <input name="external_id" maxlength="190" required value="{{ old('external_id') }}">
            <input type="hidden" name="status" value="waiting_contract">
            <button class="b" type="submit">ثبت شناسه ویدئوی موجود</button>
            <small class="teacher-help">برای ثبت ویدئوی موجود در SpotPlayer از شناسه خارجی استفاده کنید. برای بارگذاری فایل از آپلود امن تکه‌ای در بخش بعد استفاده کنید.</small>
        </form>

        <hr>
        <h2>بارگذاری امن فایل جلسه</h2>
        <div
            data-course-upload
            data-learning-scope
            data-target="spotplayer_video"
            data-course-id="0"
            data-start-url="{{ route('teacher.uploads.start') }}"
            data-chunk-url="{{ route('teacher.uploads.chunk') }}"
            data-complete-url="{{ route('teacher.uploads.complete') }}"
            data-cancel-url="{{ route('teacher.uploads.cancel') }}"
            data-resume-url="{{ route('teacher.uploads.resume') }}"
            data-status-url="{{ route('teacher.uploads.status') }}"
            data-max-mb="{{ config('learning.max_lesson_mb') }}"
            data-chunk-mb="{{ config('learning.chunk_mb') }}"
        >
            <label>عنوان جلسه</label>
            <input data-upload-title maxlength="180" placeholder="مثلاً جلسه اول: مبانی ریاضی" required>

            <label>دوره</label>
            <select data-upload-course required>
                <option value="">انتخاب دوره</option>
                @foreach($courses as $course)
                    <option value="{{ $course->id }}">
                        {{ $course->title }}
                        @if($course->subject) · {{ $course->subject }}@endif
                        @if($course->grade) · پایه {{ $course->grade }}@endif
                    </option>
                @endforeach
            </select>

            <label>کلاس</label>
            <select data-upload-classroom>
                <option value="">کل دوره</option>
                @foreach($classrooms as $classroom)
                    <option value="{{ $classroom->id }}" data-course-id="{{ $classroom->course_id }}">{{ $classroom->name }}</option>
                @endforeach
            </select>

            <label>جلسه</label>
            <select data-upload-session>
                <option value="">انتخاب جلسه</option>
                @foreach($classrooms as $classroom)
                    @foreach($classroom->sessions as $session)
                        <option value="{{ $session->id }}" data-classroom-id="{{ $classroom->id }}">
                            {{ $session->title ?: $classroom->name }} · {{ $session->starts_at?->format('Y/m/d H:i') }}
                        </option>
                    @endforeach
                @endforeach
            </select>

            <label>شناسه خارجی SpotPlayer (اختیاری)</label>
            <input data-upload-external maxlength="190" placeholder="در صورت نیاز به اتصال بعدی">

            <label>فایل ویدیوی جلسه</label>
            <input type="file" data-upload-file accept="video/mp4,video/quicktime,video/webm,video/x-matroska,video/x-m4v">
            <small class="teacher-help">حداکثر {{ config('learning.max_lesson_mb') }} مگابایت. آپلود قابل ادامه، ذخیره خصوصی و پردازش خودکار دارد.</small>

            <div class="teacher-progress" data-upload-progress hidden>
                <span data-upload-bar style="width:0"></span>
            </div>
            <p class="teacher-help" data-upload-status role="status" aria-live="polite">پس از انتخاب فایل، پیش‌نمایش و وضعیت آپلود در همین بخش نمایش داده می‌شود.</p>
            <button class="b" type="button" data-upload-start>بارگذاری و پردازش ویدیو</button>
        </div>
    </section>

    <section class="c">
        <div class="teacher-section-head"><div><h2>ویدئوهای ثبت‌شده</h2><p>{{ $videos->total() }} ویدئو · قابل جست‌وجو و مرتب‌سازی</p></div></div>
        <form method="GET" action="{{ route('teacher.videos') }}" class="teacher-list-filters">
            <label class="teacher-search-field"><span class="sr-only">جست‌وجوی ویدئو</span><input type="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="جست‌وجوی عنوان ویدئو"></label>
            <label><span class="sr-only">مرتب‌سازی</span><select name="sort"><option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>جدیدترین</option><option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>قدیمی‌ترین</option><option value="title" @selected(($filters['sort'] ?? '') === 'title')>عنوان</option></select></label>
            <button class="b s" type="submit">اعمال</button><a class="b s" href="{{ route('teacher.videos') }}">پاک‌کردن</a>
        </form>
        @forelse($videos as $video)
            @php
                $statusLabel = match ($video->approval_status) {
                    'pending' => 'در انتظار تأیید مدیر',
                    'rejected' => 'رد شده؛ نیازمند اصلاح',
                    default => match ($video->status) {
                        'active' => 'تأیید شده · آماده پخش',
                        'processing' => 'تأیید شده · در حال پردازش',
                        'failed' => 'پردازش ناموفق',
                        'waiting_contract' => 'در انتظار قرارداد',
                        default => ['draft' => 'پیش‌نویس', 'published' => 'منتشرشده'][$video->status] ?? 'در حال بررسی',
                    },
                };
            @endphp
            <div class="teacher-list-card">
                <div>
                    <strong>{{ $video->title }}</strong>
                    <small>
                        {{ $video->course?->title }}
                        @if($video->course?->grade) · پایه {{ $video->course->grade }}@endif
                        @if($video->course?->season) · {{ $seasonLabels[$video->course->season] ?? $video->course->season }}@endif
                        · {{ $video->classroom?->name ?? 'کل دوره' }}
                        @if($video->classSession) · {{ $video->classSession->title }}@endif
                    </small>
                    @if($video->processing_error)
                        <small class="teacher-help">{{ $video->processing_error }}</small>
                    @endif
                    @if($video->approval_status === 'rejected')
                        <small class="teacher-help">دلیل مدیر: {{ $video->review_note ?: 'نیازمند اصلاح' }}</small>
                    @endif
                </div>

                <div class="teacher-list-meta">
                    <span>{{ $statusLabel }}</span>

                    @if($video->approval_status === 'rejected')
                        <form method="POST" action="{{ route('teacher.spotplayer.videos.resubmit', $video) }}">
                            @csrf
                            <button class="b" type="submit">ارسال مجدد برای تأیید</button>
                        </form>
                    @endif

                    @if($video->file_path)
                        <a href="{{ route('teacher.spotplayer.videos.download', $video) }}">
                            {{ $video->status === 'active' ? 'دریافت فایل پردازش‌شده' : 'دریافت فایل مبدأ' }}
                        </a>
                        @if($video->status === 'failed')
                            <form method="POST" action="{{ route('teacher.spotplayer.videos.retry-processing', $video) }}">
                                @csrf
                                <button class="b" type="submit">تلاش مجدد پردازش</button>
                            </form>
                        @endif
                    @else
                        <span>شناسه خارجی: {{ $video->external_id ?: 'ثبت نشده' }}</span>
                    @endif
                </div>
            </div>
        @empty
            <p>هنوز ویدئویی برای دوره‌های شما ثبت نشده است. می‌توانید ویدیو را در سطح کل دوره ثبت کنید یا در صورت نیاز آن را به یک کلاس و جلسه مشخص متصل کنید.</p>
        @endforelse

        {{ $videos->links() }}
    </section>
</div>
@endsection
