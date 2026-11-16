{{-- نمودار SVG. JS آن را از data-chart-config می‌کشد.
     type: line | bar | donut | radar
     line/bar/radar: $labels, $series[{name, c:'--pri', data:[..], area?, dash?}], $options[min,max,stacked]
     donut: $parts[{l, v, c}], $big, $small --}}
@php
    $payload = [
        'labels'  => $labels ?? [],
        'series'  => $series ?? [],
        'options' => $options ?? [],
        'parts'   => $parts ?? [],
        'big'     => $big ?? '',
        'small'   => $small ?? '',
    ];
@endphp
<div data-chart="{{ $type }}" data-chart-config="{{ json_encode($payload, JSON_UNESCAPED_UNICODE) }}"><p style="color:var(--mu)">داده‌ای برای نمایش نیست.</p></div>
