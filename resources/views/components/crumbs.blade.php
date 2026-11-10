@props(['items' => []])
<nav class="crumbs" aria-label="مسیر صفحه">
  @foreach($items as $label => $url)
    <span>@if($url)<a href="{{ $url }}">{{ $label }}</a>@else{{ $label }}@endif</span>
  @endforeach
</nav>
