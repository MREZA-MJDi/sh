@props(['label', 'value', 'hint' => null, 'icon' => 'chart', 'tone' => 'home', 'href' => null])
@php($tag = $href ? 'a' : 'div')
<{{ $tag }} @if($href) href="{{ $href }}" @endif class="adm-stat {{ $href ? 'is-link' : '' }}" data-tone="{{ $tone }}">
  <span class="adm-stat-ico"><x-admin.icon :name="$icon"/></span>
  <div class="adm-stat-copy"><span>{{ $label }}</span><strong class="adm-num">{{ $value }}</strong>@if ($hint)<small>{{ $hint }}</small>@endif</div>
</{{ $tag }}>
