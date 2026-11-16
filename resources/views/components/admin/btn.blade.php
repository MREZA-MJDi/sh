{{-- <x-admin.btn variant="primary|soft|line|bad|ok|ghost" size="md|sm|icon" icon="plus" href="…"> --}}
@props(['variant' => 'primary', 'size' => 'md', 'icon' => null, 'href' => null, 'type' => 'button'])
@php $cls = 'adm-btn adm-btn-'.$variant.($size === 'sm' ? ' adm-btn-sm' : '').($size === 'icon' ? ' adm-btn-icon' : ''); @endphp
@if ($href)
<a href="{{ $href }}" {{ $attributes->merge(['class' => $cls]) }}>@if ($icon)<x-admin.icon :name="$icon"/>@endif{{ $slot }}</a>
@else
<button type="{{ $type }}" {{ $attributes->merge(['class' => $cls]) }}>@if ($icon)<x-admin.icon :name="$icon"/>@endif{{ $slot }}</button>
@endif
