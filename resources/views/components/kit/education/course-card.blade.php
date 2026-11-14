
@props([
'title' => 'دوره آموزشی',
'description' => null,
'image' => null,
'category' => null,
'teacher' => null,
'lessons' => null,
'duration' => null,
'price' => null,
'level' => null,
'grades' => [],
'href' => '#',
])

<article {{ $attributes->class([
    'group flex h-full min-w-0 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl'
]) }}>

    <a href="{{ $href }}" aria-label="مشاهده دوره {{ $title }}">

        <div class="relative aspect-video overflow-hidden bg-slate-100">

            @if($image)

                <img
                        src="{{ $image }}"
                        alt="{{ $title }}"
                        class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                        loading="lazy"
                        decoding="async"
                >

            @else

                <div class="flex h-full items-center justify-center bg-gradient-to-br from-indigo-50 to-sky-50 text-4xl font-black text-indigo-500">
                    ش
                </div>

            @endif

            @if($category)
                <span class="absolute right-4 top-4 rounded-full bg-white/95 px-3 py-1.5 text-xs font-bold text-indigo-700 shadow-sm">
                    {{ $category }}
                </span>
            @endif

            @if($price === 'رایگان')
                <span class="absolute left-4 top-4 rounded-full bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white">
                    رایگان
                </span>
            @endif

        </div>

    </a>

    <div class="flex flex-1 flex-col p-5">

        <div class="flex flex-wrap items-center gap-2">

            @foreach(array_slice($grades, 0, 3) as $grade)
                <x-kit.ui.badge>{{ $grade }}</x-kit.ui.badge>
            @endforeach

            @if(empty($grades) && $level)
                <x-kit.ui.badge>سطح {{ $level }}</x-kit.ui.badge>
            @endif

            @if($price !== null)
                <span class="ms-auto text-sm font-black text-indigo-600">
                    {{ $price }}
                </span>
            @endif

        </div>

        <h3 class="mt-4 text-xl font-black leading-snug text-slate-900">
            <a href="{{ $href }}" class="transition hover:text-indigo-600">
                {{ $title }}
            </a>
        </h3>

        @if($description)
            <p class="mt-2 line-clamp-2 text-sm leading-7 text-slate-600">
                {{ $description }}
            </p>
        @endif

        @if($teacher)
            <div class="mt-4 flex items-center gap-2 text-xs font-semibold text-slate-500">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-50 text-indigo-600">
                    م
                </span>
                {{ $teacher }}
            </div>
        @endif

        <div class="mt-auto flex flex-wrap items-center gap-3 border-t border-slate-100 pt-4 text-xs font-semibold text-slate-500">

            @if($lessons !== null)
                <span>{{ $lessons }} درس</span>
            @endif

            @if($duration)
                <span>{{ $duration }}</span>
            @endif

            <a href="{{ $href }}" class="ms-auto font-black text-indigo-600">
                مشاهده دوره ←
            </a>

        </div>

    </div>

</article>
