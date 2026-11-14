
@props([
'variant' => 'neutral',
])

@php
    $variants = [
        'neutral' => 'bg-slate-100 text-slate-700',
        'primary' => 'bg-indigo-50 text-indigo-700',
        'success' => 'bg-emerald-50 text-emerald-700',
        'warning' => 'bg-amber-50 text-amber-800',
        'danger' => 'bg-red-50 text-red-700',
        'info' => 'bg-sky-50 text-sky-700',
    ];
@endphp

<span {{ $attributes->class([
    'inline-flex min-h-6 items-center justify-center gap-1 rounded-full px-3 py-1 text-xs font-extrabold',
    $variants[$variant] ?? $variants['neutral'],
]) }}>
    {{ $slot }}
</span>
