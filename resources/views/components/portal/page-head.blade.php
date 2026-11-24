@props(['title', 'icon' => 'folder', 'tone' => 'education', 'back' => null, 'backLabel' => 'بازگشت'])
<header {{ $attributes->merge(['class'=>'portal-page-head','data-tone'=>$tone]) }}>
 <div class="portal-heading">
  <span class="portal-state-icon"><x-portal.icon :name="$icon"/></span>
  <div>@if($back)<a class="portal-back" href="{{ $back }}"><x-portal.icon name="back"/>{{ $backLabel }}</a>@endif<h1>{{ $title }}</h1>@isset($subtitle)<p>{{ $subtitle }}</p>@endisset</div>
 </div>
 @if($slot->isNotEmpty())<div class="portal-head-actions">{{ $slot }}</div>@endif
</header>
