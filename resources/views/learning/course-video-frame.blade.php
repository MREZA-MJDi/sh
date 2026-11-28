<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $video->title }}</title>
    <style>
        html, body, .learning-video-frame { width: 100%; height: 100%; margin: 0; background: #0b1220 }
        .learning-video-frame { position: relative }
        video { display: block; width: 100%; height: 100%; object-fit: contain }
    </style>
</head>
<body>
    <div class="learning-video-frame">
        <video controls controlslist="nodownload nofullscreen noremoteplayback" disablepictureinpicture disableremoteplayback playsinline preload="none"
            aria-label="{{ $video->title }}"></video>
        <x-learning.video-watermark :source="route('student.videos.stream', $video)" :fullscreen="false"/>
    </div>
</body>
</html>
