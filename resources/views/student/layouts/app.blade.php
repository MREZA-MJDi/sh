<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="robots" content="noindex, nofollow, noarchive">
<title>@yield('title', 'پنل دانش‌آموز') · شیخان</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&display=swap">
@vite(['resources/css/student/app.css', 'resources/js/student/student.js'])
<script>try{var t=localStorage.getItem('sh-theme');if(t)document.documentElement.setAttribute('data-theme',t)}catch(e){}</script>
@stack('head')
</head>
<body>
@include('student.partials.icons')

<div class="app">
    @include('student.partials.sidebar')

    <main class="main">
        @include('student.partials.topbar')
        @if (session('status') || session('success') || session('error') || $errors->any())
            <div class="student-alert {{ $errors->any() || session('error') ? 'is-error' : 'is-success' }}" role="{{ $errors->any() || session('error') ? 'alert' : 'status' }}">
                @if (session('status'))<p>{{ session('status') }}</p>@endif
                @if (session('success'))<p>{{ session('success') }}</p>@endif
                @if (session('error'))<p>{{ session('error') }}</p>@endif
                @if ($errors->any())<ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
            </div>
        @endif
        @yield('content')
    </main>
</div>

<div id="toast" class="toast" role="status" aria-live="polite"></div>


@stack('scripts')
</body>
</html>
