@extends('teacher.layouts.app')
@section('title', 'کلاس‌ها و فیلم‌ها')
@section('content')
@php($seasonLabels = ['spring' => 'بهار', 'summer' => 'تابستان', 'autumn' => 'پاییز', 'winter' => 'زمستان'])
<div class="teacher-page-head"><div><span class="teacher-eyebrow">آموزش و اجرا</span><h1>کلاس‌ها و فیلم‌ها</h1><p>محتوای آموزشی و جلسه‌های کلاس‌های تخصیص‌یافته را پیگیری کنید.</p></div><div class="teacher-head-actions">@if(auth()->user()->hasPermissionTo('videos.manage.assigned'))<a class="b s" href="{{ route('teacher.videos') }}">بارگذاری ویدئو</a>@endif @if(auth()->user()->hasPermissionTo('resources.manage.assigned'))<a class="b s" href="{{ route('teacher.resources') }}">افزودن جزوه</a>@endif</div></div>
<section class="c teacher-assigned-classes">
    <div class="teacher-section-head"><div><h2>دوره‌ها و گروه‌های تخصیص‌یافته</h2><p>پایه و فصل هر گروه از اطلاعات دوره‌ی مرتبط نمایش داده می‌شود.</p></div><span class="teacher-result-count">{{ $classrooms->total() }} کلاس</span></div>
    <form method="GET" action="{{ route('teacher.classes') }}" class="teacher-list-filters">
        <label class="teacher-search-field"><span class="sr-only">جست‌وجوی کلاس یا دوره</span><input type="search" name="q" value="{{ request('q') }}" maxlength="100" placeholder="جست‌وجوی نام کلاس یا دوره"></label>
        <label><span class="sr-only">مرتب‌سازی</span><select name="sort"><option value="name" @selected(request('sort', 'name') === 'name')>نام کلاس</option><option value="students" @selected(request('sort') === 'students')>بیشترین دانش‌آموز</option><option value="recent" @selected(request('sort') === 'recent')>آخرین تغییر</option></select></label>
        <button class="b s" type="submit">اعمال</button><a class="b s" href="{{ route('teacher.classes') }}">پاک‌کردن</a>
    </form>
    @forelse ($classrooms as $classroom)
        <div class="teacher-list-card">
            <div>
                <strong>{{ $classroom->name }}</strong>
                <small>{{ $classroom->course?->title ?? 'کلاس مستقل' }}@if($classroom->course?->grade) · پایه {{ $classroom->course->grade }}@endif @if($classroom->course?->season) · {{ $seasonLabels[$classroom->course->season] ?? $classroom->course->season }}@endif</small>
            </div>
            <div class="teacher-list-meta"><span>{{ $classroom->students_count }} دانش‌آموز</span><span>{{ $classroom->sessions_count }} جلسه</span></div>
        </div>
    @empty
        <div class="teacher-empty-state"><strong>هنوز گروه آموزشی به شما تخصیص داده نشده است</strong><span>بعد از تخصیص کلاس، فهرست گروه‌ها و جلسه‌های شما در این بخش نمایش داده می‌شود.</span></div>
    @endforelse
</section>
<div class="g g2">
    <section class="c"><h3>مدیریت ویدئوهای آموزشی</h3><p>ویدئوهای دوره و کلاس را از مسیر آپلود امن بارگذاری کنید و وضعیت اتصال را پیگیری کنید.</p>@if(auth()->user()->hasPermissionTo('videos.manage.assigned'))<a class="b s" href="{{ route('teacher.videos') }}">رفتن به بارگذاری ویدئو</a>@endif @if(auth()->user()->hasPermissionTo('classes.view.assigned'))<a class="b s" href="{{ route('teacher.live') }}">مشاهده جلسات آنلاین</a>@endif</section>

    <section class="c"><h3>لینک کلاس آنلاین</h3>
        @forelse ($classrooms as $classroom)
            @foreach ($classroom->sessions as $session)
                <div class="row"><div>{{ $session->title ?: $classroom->name }}<small>{{ $session->starts_at?->format('Y/m/d H:i') }}</small></div>
                    @if ($session->meeting_url)<a class="b s" href="{{ $session->meeting_url }}" target="_blank" rel="noopener">ورود</a>
                    @elseif ($classroom->meeting_url)<a class="b s" href="{{ $classroom->meeting_url }}" target="_blank" rel="noopener">ورود</a>
                    @else<i class="bd am">لینک ثبت نشده</i>@endif
                </div>
            @endforeach
        @empty
            <p>کلاسی ثبت نشده است.</p>
        @endforelse
    </section>

    <section class="c"><h3>فیلم‌های ضبط‌شده</h3>
        @forelse ($videos as $video)
            <div class="row"><div>{{ $video->title ?? 'ویدئوی کلاس' }}<small>{{ $video->created_at?->format('Y/m/d') }}</small></div>
                @php($videoStatus = $video->approval_status === 'pending'
                    ? ['در انتظار تأیید', 'am']
                    : (['active' => ['آماده پخش', 'gn'], 'processing' => ['در حال پردازش', 'bl'], 'failed' => ['خطا در پردازش', 'rd'], 'waiting_contract' => ['در انتظار اتصال سرویس', 'am']][$video->status] ?? ['در حال بررسی', 'am']))
                <i class="bd {{ $videoStatus[1] }}">{{ $videoStatus[0] }}</i>
            </div>
        @empty
            <p>فیلمی برای کلاس‌های شما ثبت نشده است.</p>
        @endforelse
    </section>

    <section class="c"><div class="teacher-section-head"><h3>منابع و جزوه‌ها</h3>@if(auth()->user()->hasPermissionTo('resources.manage.assigned'))<a href="{{ route('teacher.resources') }}">مدیریت منابع</a>@endif</div>
        @forelse ($resources as $resource)
            <div class="row"><div>{{ $resource->title }}<small>{{ $resource->kind }}</small></div>
                <i class="bd {{ $resource->status === 'published' ? 'gn' : 'am' }}">{{ ['published' => 'منتشرشده', 'draft' => 'پیش‌نویس', 'archived' => 'بایگانی‌شده'][$resource->status] ?? 'در حال بررسی' }}</i>
            </div>
        @empty
            <p>منبعی برای کلاس‌های شما ثبت نشده است.</p>
        @endforelse
    </section>
</div>
{{ $classrooms->links() }}
@endsection
