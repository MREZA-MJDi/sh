@props(['label', 'value', 'hint' => null, 'icon' => 'chart', 'tone' => 'home'])
<div class="adm-stat" data-tone="{{ $tone }}">
  <span class="adm-stat-ico"><x-admin.icon :name="$icon"/></span>
  <div><span>{{ $label }}</span><strong class="adm-num">{{ $value }}</strong>@if ($hint)<small>{{ $hint }}</small>@endif</div>
</div>
