@props(['icon' => 'video', 'tone' => 'a', 'title', 'sub' => null, 'href' => null, 'action' => 'play'])
<div class="trk">
  <div class="th t-{{ $tone }}"><x-icon :name="$icon"/></div>
  <div><b>{{ $title }}</b>@if($sub)<span class="mu">{{ $sub }}</span>@endif</div>
  {{ $slot }}
  @if($href)<a class="pl" href="{{ $href }}" aria-label="{{ $title }}"><x-icon :name="$action"/></a>@endif
</div>
