@props(['name', 'small' => false, 'avatarUrl' => null, 'user' => null])
@php
  $url = $avatarUrl ?: ($user?->avatarUrl());
@endphp
<span {{ $attributes->merge(['class' => 'adm-avatar'.($small ? ' is-sm' : '')]) }} data-c="{{ crc32($name) % 5 }}" @if($url) style="overflow:hidden;padding:0" @endif>
  @if($url)
    <img src="{{ $url }}" alt="{{ $name }}" style="width:100%;height:100%;object-fit:cover;border-radius:50%">
  @else
    {{ mb_substr($name, 0, 1) }}
  @endif
</span>
