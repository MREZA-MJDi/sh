{{-- نیازمند: $course, $state, $completed --}}
@foreach ($course->sections as $section)
    <section style="margin-bottom:16px">
        <h3 style="margin:0 0 8px">{{ $section->title }}</h3>
        <ul style="list-style:none;margin:0;padding:0">
            @foreach ($section->lessons as $lesson)
                <li style="display:flex;gap:8px;align-items:center;padding:8px 0;border-bottom:1px solid #f1f5f9">
                    @if ($state['unlocked'])
                        <span aria-hidden="true">{{ in_array($lesson->id, $completed, true) ? '✅' : '▶️' }}</span>
                        <a href="{{ route('lessons.show', [$course->slug, $lesson->id]) }}" style="flex:1">{{ $lesson->title }}</a>
                    @else
                        <span aria-hidden="true">🔒</span>
                        <span style="flex:1">{{ $lesson->title }}</span>
                        @if ($lesson->is_free_preview) <small>پیش‌نمایش</small> @endif
                    @endif
                    <small style="color:#64748b">{{ intdiv($lesson->duration_seconds, 60) }} دقیقه</small>
                </li>
                @if ($lesson->description)
                    <li style="list-style:none;color:#475569;font-size:.92em;padding:0 28px 8px">{{ $lesson->description }}</li>
                @endif
            @endforeach
        </ul>
    </section>
@endforeach
