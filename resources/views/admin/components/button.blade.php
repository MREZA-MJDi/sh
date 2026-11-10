@props(['href' => null, 'variant' => 'primary', 'size' => null, 'type' => 'button'])
@php($variantClass = ['secondary' => 'panel-btn-secondary', 'success' => 'panel-btn-success', 'danger' => 'panel-btn-danger', 'ghost' => 'panel-btn-ghost'][$variant] ?? '')
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class(['panel-btn', $variantClass, $size === 'sm' ? 'panel-btn-sm' : '']) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class(['panel-btn', $variantClass, $size === 'sm' ? 'panel-btn-sm' : '']) }}>{{ $slot }}</button>
@endif
