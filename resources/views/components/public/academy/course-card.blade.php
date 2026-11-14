@props(['course'])
<article class="ac-card ac-course">
    <a class="ac-course-cover" href="{{ route('public.courses.show', $course->slug) }}" aria-label="مشاهده {{ $course->title }}">✦</a>
    <div class="ac-course-body">
        <div style="display:flex;gap:7px;flex-wrap:wrap">
            @if($course->grade)<span class="ac-pill">پایه {{ $course->grade }}</span>@endif
            @if($course->subject)<span class="ac-pill">{{ $course->subject }}</span>@endif
            @if($course->type === 'free')<span class="ac-pill">رایگان</span>@endif
        </div>
        <h3><a href="{{ route('public.courses.show', $course->slug) }}">{{ $course->title }}</a></h3>
        @if($course->description)<p class="ac-muted" style="font-size:13px;line-height:1.9">{{ \Illuminate\Support\Str::limit(strip_tags($course->description),105) }}</p>@endif
        <div class="ac-meta" style="margin-bottom:13px">
            @if($course->teacher)<span>{{ $course->teacher->name }}</span>@endif
            <span>{{ $course->sections->sum(fn($section) => $section->lessons_count ?? $section->lessons->count()) }} درس</span>
            @if(isset($course->enrollments_count))<span>{{ number_format($course->enrollments_count) }} ثبت‌نام</span>@endif
        </div>
        <div class="ac-price"><b>{{ $course->type === 'paid' ? number_format((int)$course->price).' تومان' : 'رایگان' }}</b><a class="ac-link" href="{{ route('public.courses.show', $course->slug) }}">جزئیات دوره ←</a></div>
        <form method="POST" action="{{ route('public.cart.add', $course) }}" style="margin-top:12px">@csrf<button class="ac-btn ac-btn-outline" style="width:100%" type="submit">افزودن به سبد خرید</button></form>
    </div>
</article>
