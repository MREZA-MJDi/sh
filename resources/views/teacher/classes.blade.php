@extends('teacher.layouts.app')
@section('title', 'کلاس‌ها و فیلم‌ها')
@section('content')
<h2>کلاس‌ها و فیلم‌ها</h2>
<div class="g g2">
    <section class="c"><h3>ویدئوهای کلاس</h3>
        <p>ویدئوهای ثبت‌شده برای کلاس‌های تحت مدیریت شما در بخش زیر نمایش داده می‌شوند.</p>
        <a class="b s" href="{{ url('teacher/live') }}">مشاهده جلسات آنلاین</a>
    </section>

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
        @php $videos = $classrooms->getCollection()->flatMap(fn ($classroom) => $classroom->spotPlayerVideos); @endphp
        @forelse ($videos as $video)
            <div class="row"><div>{{ $video->title ?? 'ویدئوی کلاس' }}<small>{{ $video->created_at?->format('Y/m/d') }}</small></div>
                @if ($video->status === 'published')<i class="bd gn">منتشر شد</i>@else<i class="bd am">{{ $video->status }}</i>@endif
            </div>
        @empty
            <p>فیلمی برای کلاس‌های شما ثبت نشده است.</p>
        @endforelse
    </section>

    <section class="c"><h3>منابع و جزوه‌ها</h3>
        @php $resources = $classrooms->getCollection()->flatMap(fn ($classroom) => $classroom->learningResources); @endphp
        @forelse ($resources as $resource)
            <div class="row"><div>{{ $resource->title }}<small>{{ $resource->kind }} · {{ $resource->classroom?->name ?? '' }}</small></div>
                <i class="bd {{ $resource->status === 'published' ? 'gn' : 'am' }}">{{ $resource->status }}</i>
            </div>
        @empty
            <p>منبعی برای کلاس‌های شما ثبت نشده است.</p>
        @endforelse
    </section>
</div>
{{ $classrooms->links() }}
@endsection
