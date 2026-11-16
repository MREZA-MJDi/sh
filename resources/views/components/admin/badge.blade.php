{{-- tone: brand|ok|warn|bad|info|mute|tone --}}
@props(['tone' => 'brand'])
<span {{ $attributes->merge(['class' => 'adm-badge adm-badge-'.$tone]) }}>{{ $slot }}</span>
