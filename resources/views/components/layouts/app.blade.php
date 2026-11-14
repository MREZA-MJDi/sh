@props(['title' => 'دوره جامع راه‌اندازی کسب‌وکار اینترنتی'])
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white font-[Vazirmatn,Tahoma,sans-serif]">
    <main>{{ $slot }}</main>
</body>
</html>
