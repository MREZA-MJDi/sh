@props(['tone' => ''])
<span {{ $attributes->class(['tag', $tone]) }}>{{ $slot }}</span>
