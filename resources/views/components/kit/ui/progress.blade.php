
@props([
'value' => 0,
'label' => null,
'showValue' => true,
])

@php
    $value = max(0, min(100, (float) $value));
@endphp

<div class="w-full">

    @if($label || $showValue)

        <div class="mb-2 flex items-center justify-between gap-4">

            @if($label)
                <span class="text-sm font-bold text-slate-700">
                    {{ $label }}
                </span>
            @else
                <span></span>
            @endif

            @if($showValue)
                <span class="text-xs font-black text-slate-500">
                    {{ number_format($value, 0) }}٪
                </span>
            @endif

        </div>

    @endif

    <div
            class="h-2 overflow-hidden rounded-full bg-slate-100"
            role="progressbar"
            aria-valuemin="0"
            aria-valuemax="100"
            aria-valuenow="{{ $value }}"
            @if($label) aria-label="{{ $label }}" @endif
    >
        <div
                class="h-full rounded-full bg-indigo-600 transition-all duration-500"
                style="width: {{ $value }}%"
        ></div>
    </div>

</div>
