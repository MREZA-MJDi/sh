
@props([
'type' => 'info',
'title' => null,
])

@php
    $styles = [
        'info' => 'border-sky-200 bg-sky-50 text-sky-900',
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-900',
        'danger' => 'border-red-200 bg-red-50 text-red-900',
    ];
@endphp

<div {{ $attributes->class([
    'rounded-xl border px-4 py-3',
    $styles[$type] ?? $styles['info'],
]) }} role="status">

    @if($title)
        <div class="font-extrabold">
            {{ $title }}
        </div>
    @endif

    <div class="{{ $title ? 'mt-1' : '' }} text-sm leading-7">
        {{ $slot }}
    </div>

</div>
