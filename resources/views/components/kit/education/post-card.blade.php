
@props([
'title',
'excerpt' => null,
'category' => null,
'date' => null,
'image' => null,
'href' => '#',
])

<article {{ $attributes->class([
    'group flex h-full min-w-0 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl'
]) }}>

    <a href="{{ $href }}" aria-label="مطالعه {{ $title }}">

        <div class="relative aspect-video overflow-hidden bg-slate-100">

            @if($image)

                <img
                        src="{{ $image }}"
                        alt="{{ $title }}"
                        class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                        loading="lazy"
                >

            @else

                <div class="flex h-full items-center justify-center bg-gradient-to-br from-indigo-50 to-sky-50 text-4xl font-black text-indigo-400">
                    ش
                </div>

            @endif

            @if($category)
                <span class="absolute right-4 top-4 rounded-full bg-white/95 px-3 py-1.5 text-xs font-extrabold text-indigo-700 shadow-sm">
                    {{ $category }}
                </span>
            @endif

        </div>

    </a>

    <div class="flex flex-1 flex-col p-5">

        <div class="flex items-center justify-between gap-3">

            @if($date)
                <time class="text-xs font-semibold text-slate-500">
                    {{ $date }}
                </time>
            @endif

            <span class="ms-auto text-xs font-bold text-indigo-600">
                مقاله
            </span>

        </div>

        <h3 class="mt-4 text-lg font-black leading-snug text-slate-900">
            <a href="{{ $href }}" class="transition hover:text-indigo-600">
                {{ $title }}
            </a>
        </h3>

        @if($excerpt)
            <p class="mt-3 line-clamp-3 text-sm leading-7 text-slate-600">
                {{ $excerpt }}
            </p>
        @endif

        <div class="mt-auto pt-5">
            <a href="{{ $href }}" class="inline-flex items-center gap-2 text-sm font-black text-indigo-600">
                مطالعه مقاله
                <span>←</span>
            </a>
        </div>

    </div>

</article>
