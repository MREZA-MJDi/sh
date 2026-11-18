<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="robots" content="noindex, nofollow, noarchive">
  <title>@yield('title') — پنل مدیریت آکادمی شیخان</title>
  <script>try{var t=localStorage.getItem('adm-theme');if(t)document.documentElement.dataset.theme=t}catch(e){}</script>
  @vite(['resources/css/admin/app.css', 'resources/js/admin/app.js'])
  @stack('head')
</head>
<body>
  @include('admin.partials.icons')

  @php
    $nav = include resource_path('views/admin/nav.php');
    $user = auth()->user();
    $isAdmin = $user?->hasRole('admin') ?? false;
    if ($user?->hasRole('seo') && ! $user?->hasRole('admin')) {
      $nav['home']['url'] = 'admin/seo';
      $nav['home']['label'] = 'مرکز مدیریت سئو';
    }
    $permissionMap = [
      'admin/students' => ['users.view', 'users.manage', 'students.view.assigned'],
      'admin/teachers' => ['users.manage'],
      'admin/parents' => ['users.manage'],
      'admin/courses' => ['courses.manage', 'courses.view.assigned'],
      'admin/classes' => ['classes.manage', 'classes.manage.assigned', 'classes.view.assigned'],
      'admin/spotplayer' => ['integrations.manage', 'videos.manage.assigned', 'videos.view.assigned'],
      
      'admin/attendance' => ['attendance.manage.all', 'attendance.manage.assigned', 'attendance.view.assigned'],
      'admin/homework' => ['homework.manage.all', 'homework.manage.assigned', 'homework.view.assigned'],
      'admin/exams' => ['exams.manage', 'exams.manage.assigned'],
      'admin/online-classes' => ['classes.manage', 'classes.view.assigned', 'classes.manage.assigned'],
      'admin/quiz24' => ['integrations.manage'],
      'admin/contact-messages' => ['messages.view.all'],
      'admin/parent-meetings' => ['classes.manage', 'classes.manage.assigned'],
      'admin/seo' => ['seo.manage'],
      'admin/seo/articles' => ['seo.manage'],
      'admin/seo/banners' => ['seo.manage'],
      'admin/seo/testimonials' => ['seo.manage'],
      'admin/messages' => ['messages.view.all', 'messages.view.assigned'],
      'admin/consultations' => ['consultations.manage'],
      'admin/announcements' => ['announcements.manage', 'announcements.view'],
      'admin/enrollments' => ['courses.manage', 'users.manage'],
      'admin/payments' => ['users.manage'],
      'admin/resources' => ['resources.manage', 'resources.manage.assigned'],
    ];
    $nav['groups'] = collect($nav['groups'])->map(function ($group) use ($user, $isAdmin, $permissionMap) {
      $group['items'] = collect($group['items'])->filter(function ($item) use ($user, $isAdmin, $permissionMap) {
        $permissions = $permissionMap[$item['url']] ?? [];
        if ($isAdmin && count($permissions) === 0) return true;
        return $user && count($permissions) > 0 && $user->hasAnyPermission($permissions);
      })->values()->all();

      return $group;
    })->filter(fn ($group) => count($group['items']) > 0)->values()->all();

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
