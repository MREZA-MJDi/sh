@props(['course'])

@php
    $gradients = [
        'ریاضی' => ['linear-gradient(135deg,#2563eb,#7c3aed)', 'chart'],
        'فیزیک' => ['linear-gradient(135deg,#0d9488,#0f766e)', 'rocket'],
        'شیمی' => ['linear-gradient(135deg,#f97316,#db2777)', 'pen'],
        'زیست' => ['linear-gradient(135deg,#16a34a,#0d9488)', 'user'],
        'زبان' => ['linear-gradient(135deg,#0891b2,#2563eb)', 'lang'],
        'ادبیات' => ['linear-gradient(135deg,#7c3aed,#db2777)', 'pen'],
    ];

    $subj = $course->subject ?? '';
    $style = $gradients[$subj] ?? ['linear-gradient(135deg,#0d9488,#1e3a8a)', 'cap'];
    $bgGrad = $style[0];
    $icon = $style[1];
    $lessonsCount = (int) $course->sections->sum('lessons_count');
    $isFree = ($course->type === 'free');
    $badgeClass = $isFree ? 'bd-free' : '';
    $badgeText = $isFree ? 'رایگان' : ($course->grade ? 'پایه '.$course->grade : ($course->subject ?: 'ویژه'));
@endphp

<article class="card cc">
    <a class="th" href="{{ route('public.courses.show', $course->slug) }}" style="background:{{ $bgGrad }}" aria-label="مشاهده {{ $course->title }}">
        <svg class="i"><use href="#{{ $icon }}"/></svg>
        <span class="bd {{ $badgeClass }}">{{ $badgeText }}</span>
    </a>
    <div class="cb">
        <h3>
            <a href="{{ route('public.courses.show', $course->slug) }}">
                {{ $course->title }}
            </a>
        </h3>
        @if($course->teacher)
            <small style="color:var(--mute);margin-bottom:8px;display:block">
                مدرس: {{ $course->teacher->name }}
            </small>
        @endif
        <div class="mt">
            <span>
                <svg class="i"><use href="#clock"/></svg>
                {{ $lessonsCount > 0 ? $lessonsCount . ' جلسه' : 'جامع' }}
            </span>
            <span style="color:#f59e0b">
                <svg class="i"><use href="#star"/></svg>
                ۴٫۸
            </span>
            <span>
                <svg class="i"><use href="#user"/></svg>
                {{ number_format($course->enrollments_count ?? 0) }} دانشجو
            </span>
        </div>
        <div class="pr">
            <div>
                @if($course->type === 'free')
                    <b style="color:var(--pri)">رایگان</b>
                @else
                    <b>{{ number_format((int)$course->price) }}</b>
                    <small>تومان</small>
                @endif
            </div>
            <a class="btn s" href="{{ route('public.courses.show', $course->slug) }}">
                مشاهده
            </a>
        </div>
    </div>
</article>
