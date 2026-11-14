
@props([
'name' => 'نام مدرس',
'role' => 'مدرس',
'avatar' => null,
'bio' => null,
'courses' => 0,
'href' => '#',
])

@php
    $displayName = trim((string) $name) ?: 'مدرس';
    $initial = mb_substr($displayName, 0, 1);
@endphp

<article {{ $attributes->class([
    'group h-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl'
]) }}>

    <a href="{{ $href }}" aria-label="مشاهده پروفایل {{ $displayName }}">

        <div class="relative flex h-56 items-center justify-center overflow-hidden bg-gradient-to-br from-indigo-50 to-sky-100">

            @if($avatar)

                <img
                        src="{{ $avatar }}"
                        alt="تصویر {{ $displayName }}"
                        class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                        loading="lazy"
                >

            @else

                <span class="text-7xl font-black text-indigo-400">
                    {{ $initial }}
                </span>

            @endif

        </div>

    </a>

    <div class="p-5">

        <h3 class="text-lg font-black text-slate-900">
            <a href="{{ $href }}" class="hover:text-indigo-600">
                {{ $displayName }}
            </a>
        </h3>

        @if($role)
            <p class="mt-1 text-sm font-bold text-indigo-600">
                {{ $role }}
            </p>
        @endif

        @if($bio)
            <p class="mt-3 text-sm leading-7 text-slate-600">
                {{ \Illuminate\Support\Str::limit(strip_tags((string) $bio), 150) }}
            </p>
        @endif

        <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4">

            <span class="text-xs font-bold text-slate-500">
                {{ number_format((int) $courses) }} دوره آموزشی
            </span>

            <a href="{{ $href }}" class="text-sm font-black text-indigo-600">
                پروفایل مدرس ←
            </a>

        </div>

    </div>

</article>
