{{-- Container for every block. Named slots: actions (header), footer. `flush` removes body padding (tables). --}}
@props(['title' => null, 'desc' => null, 'flush' => false])
<section {{ $attributes->merge(['class' => 'adm-panel'.($flush ? ' is-flush' : '')]) }}>
  @if ($title || isset($actions))
    <header class="adm-panel-head">
      <div><h2>{{ $title }}</h2>@if ($desc)<p>{{ $desc }}</p>@endif</div>
      @isset($actions)<div class="adm-actions">{{ $actions }}</div>@endisset
    </header>
  @endif
  <div class="adm-panel-body">{{ $slot }}</div>
  @isset($footer)<footer class="adm-panel-foot">{{ $footer }}</footer>@endisset
</section>
