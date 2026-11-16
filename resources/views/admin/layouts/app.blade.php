<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title') — پنل مدیریت آکادمی شیخان</title>
  <script>try{var t=localStorage.getItem('adm-theme');if(t)document.documentElement.dataset.theme=t}catch(e){}</script>
  @vite(['resources/css/admin/app.css', 'resources/js/admin/app.js'])
  @stack('head')
</head>
<body>
  @include('admin.partials.icons')

  @php
    $nav = include resource_path('views/admin/nav.php');
    $activeGroup = $activeItem = null;
    foreach ($nav['groups'] as $gi => $group) {
      foreach ($group['items'] as $ii => $item) {
        if (request()->is($item['url'], $item['url'].'/*')) { $activeGroup = $gi; $activeItem = $ii; }
      }
    }
  @endphp

  <div class="adm-shell" data-shell>
    @include('admin.partials.sidebar')
    <div class="adm-scrim" data-menu-close></div>

    <div class="adm-main">
      @include('admin.partials.topbar')
      <main class="adm-container" id="main">
        @include('admin.partials.flash')
        @yield('content')
      </main>
    </div>
  </div>

  @stack('modals')
  <div class="adm-toasts" id="adm-toasts" aria-live="polite"></div>
  @stack('scripts')
</body>
</html>
