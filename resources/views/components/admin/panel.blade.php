{{-- Container for every block. Named slots: actions (header), footer. `flush` removes body padding (tables). --}}
@props(['title' => null, 'desc' => null, 'flush' => false, 'icon' => null])
<section {{ $attributes->merge(['class' => 'adm-panel'.($flush ? ' is-flush' : '')]) }}>
  @if ($title || isset($actions))
    <header class="adm-panel-head">
      <div class="adm-panel-heading">
        @if($title)
          <span class="adm-panel-heading-icon"><x-admin.icon :name="$icon ?? (['edu'=>'book','track'=>'check','comm'=>'msg','shop'=>'card','users'=>'users'][$attributes->get('data-tone')] ?? 'box')"/></span>
          <div><h2>{{ $title }}</h2>@if($desc)<p>{{ $desc }}</p>@endif</div>
        @endif
      </div>
      @isset($actions)<div class="adm-actions">{{ $actions }}</div>@endisset
    </header>
  @endif
  <div class="adm-panel-body">{{ $slot }}</div>
  @isset($footer)<footer class="adm-panel-foot">{{ $footer }}</footer>@endisset
</section>
