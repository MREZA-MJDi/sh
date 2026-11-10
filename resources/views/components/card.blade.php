@props(['title' => null])
<section {{ $attributes->class(['card']) }}>
  @if($title || isset($actions))
    <div class="chead"><h2>{{ $title }}</h2>@isset($actions)<div class="acts">{{ $actions }}</div>@endisset</div>
  @endif
  {{ $slot }}
</section>
