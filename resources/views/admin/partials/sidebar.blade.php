<aside class="adm-sidebar" id="adm-sidebar" aria-label="منوی اصلی">
  <a class="adm-brand" href="{{ url('admin') }}">
    <span class="adm-brand-mark">ش</span>
    <span><strong>آکادمی شیخان</strong><small>پنل مدیریت</small></span>
  </a>

  <nav class="adm-nav">
    <a class="adm-nav-link {{ $activeGroup === null ? 'is-active' : '' }}" href="{{ url($nav['home']['url']) }}">
      <x-admin.icon :name="$nav['home']['icon']"/><span>{{ $nav['home']['label'] }}</span>
    </a>

    @foreach ($nav['groups'] as $gi => $group)
      <details class="adm-nav-group" data-tone="{{ $group['tone'] }}" open>
        <summary><span class="adm-nav-dot"></span>{{ $group['label'] }}<x-admin.icon name="chev"/></summary>
        <div class="adm-nav-items">
          @foreach ($group['items'] as $item)
            <a class="adm-nav-link {{ request()->is($item['url'], $item['url'].'/*') ? 'is-active' : '' }}" href="{{ url($item['url']) }}" data-tone="{{ $group['tone'] }}">
              <x-admin.icon :name="$item['icon']"/><span>{{ $item['label'] }}</span>
              @if (!empty($item['badge']))<span class="adm-count">{{ $item['badge'] }}</span>@endif
            </a>
          @endforeach
        </div>
      </details>
    @endforeach
  </nav>

  <div class="adm-side-foot">
    <span class="adm-avatar">م</span>
    <div><b>مدیر سامانه</b><br><small>admin@sheikhan.ir</small></div>
  </div>
</aside>
