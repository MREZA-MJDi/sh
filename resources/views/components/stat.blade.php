@props(['tone' => 's1', 'icon' => 'chart', 'label', 'value', 'hint' => null, 'href' => null])
@if($href)
<a href="{{ $href }}" {{ $attributes->class(['st', $tone]) }}>
@else
<div {{ $attributes->class(['st', $tone]) }}>
@endif
  <div class="ic"><x-icon :name="$icon"/></div>
  <small>{{ $label }}</small><b>{{ $value }}</b>@if($hint)<span>{{ $hint }}</span>@endif
@if($href)</a>@else</div>@endif
