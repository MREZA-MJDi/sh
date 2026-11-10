@props(['variant' => 'info'])
@php($variantClass = ['success' => 'panel-badge-success', 'warning' => 'panel-badge-warning', 'danger' => 'panel-badge-danger'][$variant] ?? '')
<span {{ $attributes->class(['panel-badge', $variantClass]) }}>{{ $slot }}</span>
