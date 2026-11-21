@props(['label', 'value', 'hint' => null, 'icon' => 'chart', 'tone' => 'home', 'href' => null])
@if($href)
  <a class="adm-stat is-link" href="{{ $href }}" data-tone="{{ $tone }}">
    <span class="adm-stat-ico"><x-admin.icon :name="$icon"/></span>
    <div class="adm-stat-copy"><span>{{ $label }}</span><strong class="adm-num">{{ $value }}</strong>@if ($hint)<small>{{ $hint }}</small>@endif</div>
  </a>
@else
  <div class="adm-stat" data-tone="{{ $tone }}">
    <span class="adm-stat-ico"><x-admin.icon :name="$icon"/></span>
    <div class="adm-stat-copy"><span>{{ $label }}</span><strong class="adm-num">{{ $value }}</strong>@if ($hint)<small>{{ $hint }}</small>@endif</div>
  </div>
@endif
