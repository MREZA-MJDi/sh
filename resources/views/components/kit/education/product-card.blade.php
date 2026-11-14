
@props([
'title' => 'محصول آموزشی',
'price' => null,
'category' => null,
'image' => null,
'description' => null,
'href' => null,
])

<article {{ $attributes->class([
    'group flex h-full min-w-0 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl'
]) }}>

    @if($href)
        <a href="{{ $href }}" aria-label="مشاهده {{ $title }}">
            @endif

            <div class="relative aspect-[4/3] overflow-hidden bg-slate-100">

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
                    <span class="absolute right-3 top-3 rounded-full bg-white/95 px-3 py-1.5 text-xs font-bold text-indigo-700 shadow-sm">
                    {{ $category }}
                </span>
                @endif

            </div>

            @if($href)
        </a>
    @endif

    <div class="flex flex-1 flex-col p-5">

        <h3 class="line-clamp-2 text-base font-black leading-7 text-slate-900">

            @if($href)
                <a href="{{ $href }}" class="hover:text-indigo-600">
                    {{ $title }}
                </a>
            @else
                {{ $title }}
            @endif

        </h3>

        @if($description)
            <p class="mt-2 line-clamp-2 text-sm leading-7 text-slate-600">
                {{ $description }}
            </p>
        @endif

        <div class="mt-auto flex items-end justify-between gap-4 border-t border-slate-100 pt-5 mt-5">

            <div>
                <span class="block text-xs font-semibold text-slate-500">
                    قیمت
                </span>

                <strong class="mt-1 block text-base font-black text-indigo-600">
                    {{ $price ?: 'تماس بگیرید' }}
                </strong>
            </div>

            @if($href)
                <a href="{{ $href }}"
                   class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 transition group-hover:-translate-x-1">
                    ←
                </a>
            @endif

        </div>

    </div>

</article>
