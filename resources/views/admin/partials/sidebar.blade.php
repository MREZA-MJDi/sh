@php
  $sidebarUser = auth()->user();
  $sidebarRole = $sidebarUser?->hasRole('admin') ? 'مدیر سامانه' : ($sidebarUser?->hasRole('teacher') ? 'مدرس' : ($sidebarUser?->hasRole('seo') ? 'کارشناس سئو' : 'کاربر'));
  $sidebarInitial = mb_substr($sidebarUser?->name ?? 'م', 0, 1);
@endphp
<aside class="adm-sidebar" id="adm-sidebar" aria-label="منوی اصلی">
  <a class="adm-brand" href="{{ url('admin') }}">
    <span class="adm-brand-mark">ش</span>
    <span><strong>آکادمی شیخان</strong><small>{{ $sidebarRole }}</small></span>
  </a>
  <button type="button" class="adm-sidebar-mini-toggle" data-admin-sidebar-mini-toggle aria-pressed="false" title="جمع یا باز کردن منو" aria-label="جمع یا باز کردن منو">
    <x-admin.icon name="panel-left-close"/>
  </button>

  <nav class="adm-nav">
    <a class="adm-nav-link {{ $activeGroup === null ? 'is-active' : '' }}" href="{{ url($nav['home']['url']) }}" title="{{ $nav['home']['label'] }}" @if($activeGroup === null) aria-current="page" @endif>
      <x-admin.icon :name="$nav['home']['icon']"/><span>{{ $nav['home']['label'] }}</span>
    </a>

    @foreach ($nav['groups'] as $gi => $group)
      <details class="adm-nav-group" data-tone="{{ $group['tone'] }}" open>
        <summary data-admin-nav-group-toggle><span class="adm-nav-dot"></span>{{ $group['label'] }}<x-admin.icon name="chev"/></summary>
        <div class="adm-nav-items">
          @foreach ($group['items'] as $item)
            <a class="adm-nav-link {{ request()->is($item['url'], $item['url'].'/*') ? 'is-active' : '' }}" href="{{ url($item['url']) }}" title="{{ $item['label'] }}" data-tone="{{ $group['tone'] }}" @if(request()->is($item['url'], $item['url'].'/*')) aria-current="page" @endif>
              <x-admin.icon :name="$item['icon']"/><span>{{ $item['label'] }}</span>
              @if (!empty($item['badge']))<span class="adm-count">{{ $item['badge'] }}</span>@endif
            </a>
          @endforeach
        </div>
      </details>
    @endforeach
  </nav>

  <div class="adm-side-foot">
    <span class="adm-avatar">{{ $sidebarInitial }}</span>
    <div><b>{{ $sidebarUser?->name ?? $sidebarRole }}</b><br><small>{{ $sidebarUser?->email ?? '' }}</small></div>
  </div>
</aside>
