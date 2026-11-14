
@props([
'items' => [],
'homeUrl' => null,
'homeLabel' => 'خانه',
])

@php
    $homeUrl = $homeUrl ?: url('/');
@endphp

<nav
        class="flex items-center gap-2 overflow-x-auto whitespace-nowrap text-sm"
        aria-label="مسیر صفحه"
        dir="rtl"
>

    <a href="{{ $homeUrl }}"
       class="shrink-0 text-slate-500 transition hover:text-indigo-600">
        {{ $homeLabel }}
    </a>

    @foreach($items as $item)

        <span class="shrink-0 text-slate-300" aria-hidden="true">/</span>

        @if(!empty($item['url']))

            <a href="{{ $item['url'] }}"
               class="shrink-0 text-slate-500 transition hover:text-indigo-600">
                {{ $item['label'] }}
            </a>

        @else

            <span class="max-w-[16rem] truncate font-bold text-slate-900"
                  aria-current="page">
                {{ $item['label'] }}
            </span>

        @endif

    @endforeach

</nav>
