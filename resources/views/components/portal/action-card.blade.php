@props(['href','title','icon'=>'folder','tone'=>'education','value'=>null,'hint'=>null])
<a href="{{ $href }}" {{ $attributes->merge(['class'=>'portal-action-card','data-tone'=>$tone]) }}>
 <span class="portal-state-icon"><x-portal.icon :name="$icon"/></span>
 <span class="portal-action-copy"><strong>{{ $title }}</strong>@if($hint)<small>{{ $hint }}</small>@endif</span>
 @if($value !== null)<b class="portal-action-value">{{ $value }}</b>@endif
 <x-portal.icon name="back" class="portal-action-arrow"/>
</a>
