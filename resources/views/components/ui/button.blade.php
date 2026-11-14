@props(['variant' => 'primary', 'href' => null])
@php
    $styles = [
        'primary' => 'bg-[#0E7C66] text-white hover:bg-[#0B6553]',
        'outline' => 'border border-[#16202A] text-[#16202A] hover:bg-[#16202A] hover:text-white',
    ][$variant];
    $tag = $href ? 'a' : 'button';
    $defaults = ['class' => "inline-block rounded-lg px-6 py-3 font-bold transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0E7C66] $styles"]
        + ($href ? ['href' => $href] : ['type' => 'button']);
@endphp
<{{ $tag }} {{ $attributes->merge($defaults) }}>{{ $slot }}</{{ $tag }}>
