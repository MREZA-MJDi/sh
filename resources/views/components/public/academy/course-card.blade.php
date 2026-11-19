@props(['course'])

@php($seasonLabels = ['spring' => 'بهار', 'summer' => 'تابستان', 'autumn' => 'پاییز', 'winter' => 'زمستان'])

<a class="ac-card ac-course" href="{{ route('public.courses.show', $course->slug) }}" aria-label="مشاهده دوره {{ $course->title }}">
    <div class="ac-course-cover" @if($course->teaserPosterUrl) style="background-image:linear-gradient(180deg,rgba(12,18,38,.04),rgba(12,18,38,.28)),url('{{ $course->teaserPosterUrl }}');background-size:cover;background-position:center" @endif>
        @unless($course->teaserPosterUrl)
            <span aria-hidden="true">{{ mb_substr($course->subject ?: $course->title, 0, 1) }}</span>
        @endunless
    </div>
    <div class="ac-course-body">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap">
            @if($course->grade)
                <span class="ac-pill">پایه {{ $course->grade }}</span>
            @endif
            @if($course->season && isset($seasonLabels[$course->season]))
                <span class="ac-pill">{{ $seasonLabels[$course->season] }}</span>
            @endif
            @if($course->type === 'free')
                <span class="ac-pill">رایگان</span>
            @else
                <span class="ac-pill ac-pill-orange">دوره تخصصی</span>
            @endif
        </div>
        <h3>{{ $course->title }}</h3>
        <div class="ac-meta">
            @if($course->subject)<span>{{ $course->subject }}</span>@endif
            @if($course->teacher?->name)<span>{{ $course->teacher->name }}</span>@endif
        </div>
        <div class="ac-price">
            <b>{{ $course->type === 'free' ? 'رایگان' : number_format((int) $course->price).' تومان' }}</b>
            <span class="ac-link">مشاهده دوره ←</span>
        </div>
    </div>
</a>
