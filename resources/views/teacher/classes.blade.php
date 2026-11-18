@extends('teacher.layouts.app')
@section('title', 'کلاس‌ها و فیلم‌ها')
@section('content')
@php($seasonLabels = ['spring' => 'بهار', 'summer' => 'تابستان', 'autumn' => 'پاییز', 'winter' => 'زمستان'])
<div class="teacher-page-head"><div><span class="teacher-eyebrow">آموزش و اجرا</span><h1>کلاس‌ها و فیلم‌ها</h1><p>محتوای آموزشی و جلسه‌های کلاس‌های تخصیص‌یافته را پیگیری کنید.</p></div><div class="teacher-head-actions">@if(auth()->user()->hasPermissionTo('videos.manage.assigned'))<a class="b s" href="{{ route('teacher.videos') }}">بارگذاری ویدئو</a>@endif @if(auth()->user()->hasPermissionTo('resources.manage.assigned'))<a class="b s" href="{{ route('teacher.resources') }}">افزودن جزوه</a>@endif</div></div>
<section class="c teacher-assigned-classes">
    <div class="teacher-section-head"><div><h2>دوره‌ها و گروه‌های تخصیص‌یافته</h2><p>پایه و فصل هر گروه از اطلاعات دوره‌ی مرتبط نمایش داده می‌شود.</p></div></div>
    @forelse ($classrooms as $classroom)
        <div class="teacher-list-card">
            <div>
                <strong>{{ $classroom->name }}</strong>
                <small>{{ $classroom->course?->title ?? 'کلاس مستقل' }}@if($classroom->course?->grade) · پایه {{ $classroom->course->grade }}@endif @if($classroom->course?->season) · {{ $seasonLabels[$classroom->course->season] ?? $classroom->course->season }}@endif</small>
            </div>
            <div class="teacher-list-meta"><span>{{ $classroom->students_count }} دانش‌آموز</span><span>{{ $classroom->sessions_count }} جلسه</span></div>
        </div>
    @empty
        <p>هنوز گروه آموزشی به شما تخصیص داده نشده است.</p>
    @endforelse
</section>
<div class="g g2">
    <section class="c"><h3>مدیریت ویدئوهای آموزشی</h3><p>ویدئوهای دوره و کلاس را از مسیر آپلود امن بارگذاری کنید و وضعیت اتصال را پیگیری کنید.</p><a class="b s" href="{{ route('teacher.videos') }}">رفتن به بارگذاری ویدئو</a><a class="b s" href="{{ route('teacher.live') }}">مشاهده جلسات آنلاین</a></section>

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
                @if ($video->status === 'published')<i class="bd gn">منتشر شد</i>@else<i class="bd am">{{ $video->status }}</i>@endif
            </div>
        @empty
            <p>فیلمی برای کلاس‌های شما ثبت نشده است.</p>
        @endforelse
    </section>

    <section class="c"><div class="teacher-section-head"><h3>منابع و جزوه‌ها</h3><a href="{{ route('teacher.resources') }}">مدیریت منابع</a></div>
        @forelse ($resources as $resource)
            <div class="row"><div>{{ $resource->title }}<small>{{ $resource->kind }}</small></div>
                <i class="bd {{ $resource->status === 'published' ? 'gn' : 'am' }}">{{ $resource->status }}</i>
            </div>
        @empty
            <p>منبعی برای کلاس‌های شما ثبت نشده است.</p>
        @endforelse
    </section>
</div>
{{ $classrooms->links() }}
@endsection
