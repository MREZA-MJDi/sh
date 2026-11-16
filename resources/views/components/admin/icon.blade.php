@props(['name'])
<svg {{ $attributes->merge(['class' => 'adm-icon']) }} data-ic="{{ $name }}" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-{{ $name }}"/></svg>
