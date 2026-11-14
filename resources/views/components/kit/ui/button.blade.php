
@props([
'type' => 'button',
'variant' => 'primary',
'size' => 'md',
'disabled' => false,
'block' => false,
'href' => null,
'external' => false,
])

@php
    $base = 'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-xl font-bold transition duration-200 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-indigo-100';

    $variants = [
        'primary' => 'bg-indigo-600 text-white shadow-sm hover:-translate-y-0.5 hover:bg-indigo-700',
        'secondary' => 'border border-slate-200 bg-white text-slate-800 hover:bg-slate-50',
        'outline' => 'border border-indigo-200 bg-white text-indigo-700 hover:bg-indigo-50',
        'ghost' => 'bg-transparent text-slate-700 hover:bg-slate-100',
        'danger' => 'bg-red-600 text-white hover:bg-red-700',
        'success' => 'bg-emerald-600 text-white hover:bg-emerald-700',
        'link' => 'bg-transparent px-0 text-indigo-600 hover:text-indigo-800',
    ];

    $sizes = [
        'sm' => 'min-h-9 px-3 text-xs',
        'md' => 'min-h-11 px-4 text-sm',
        'lg' => 'min-h-12 px-5 text-base',
        'xl' => 'min-h-14 px-6 text-base',
    ];

    $classes = trim(
        $base.' '.
        ($variants[$variant] ?? $variants['primary']).' '.
        ($sizes[$size] ?? $sizes['md']).' '.
        ($block ? 'w-full' : '').' '.
        ($disabled ? 'pointer-events-none cursor-not-allowed opacity-50' : '')
    );
@endphp

@if($href)

    <a
            href="{{ $href }}"
            @if($external) target="_blank" rel="noopener noreferrer" @endif
            @if($disabled) aria-disabled="true" tabindex="-1" @endif
            {{ $attributes->class($classes) }}
    >
        <span>{{ $slot }}</span>
    </a>

@else

    <button
            type="{{ $type }}"
            @disabled($disabled)
            {{ $attributes->class($classes) }}
    >
        <span>{{ $slot }}</span>
    </button>

@endif
