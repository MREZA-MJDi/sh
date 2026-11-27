{{-- Page tabs: <x-admin.tabs :items="['a'=>['label'=>'…','icon'=>'file','count'=>3]]" active="a" tone="track"/> ; panels use data-tab-panel="a" and class adm-hide when inactive --}}
@props(['items', 'active', 'tone' => 'home'])
<div class="adm-tabs adm-tabbar" data-tabs data-tone="{{ $tone }}" role="tablist">
  @foreach ($items as $key => $item)
    <button type="button" role="tab" class="adm-tab {{ $key === $active ? 'is-active' : '' }}" data-tab="{{ $key }}">
      <x-admin.icon :name="$item['icon'] ?? 'file'"/> {{ $item['label'] }}
      @if (isset($item['count']) && $item['count'] > 0)<x-admin.badge tone="warn">{{ $item['count'] }}</x-admin.badge>@endif
    </button>
  @endforeach
</div>
