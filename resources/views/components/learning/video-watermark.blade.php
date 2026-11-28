@auth
    @once
        <style>
            .learning-video-frame:fullscreen { display: grid; place-items: center; background: #000 }
            .learning-video-frame:fullscreen video { width: 100% !important; height: 100% !important; max-height: none !important; object-fit: contain !important }
            .learning-video-watermark { position: absolute; inset: 0; z-index: 2; display: grid; place-items: center; pointer-events: none; overflow: hidden }
            .learning-video-watermark span {
                max-width: 82%; padding: .35em .8em; border-radius: 999px;
                background: #13264220; color: #ffffff68; text-shadow: 0 1px 4px #0006;
                font-size: clamp(11px, 2.4vw, 26px); font-weight: 700;
                overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
                filter: blur(.45px); transform: rotate(-12deg);
            }
            .learning-video-fullscreen { position: absolute; z-index: 3; inset-block-start: 12px; inset-inline-end: 12px; padding: 6px 10px; border: 0; border-radius: 8px; background: #132642e8; color: white; font: inherit; font-size: 12px; cursor: pointer }
        </style>
    @endonce
    @php($identity = \App\Services\Learning\ContentWatermark::identity(auth()->user()))
    <div class="learning-video-watermark" aria-hidden="true">
        <span>آکادمی شیخان · <bdi dir="ltr">{{ $identity }}</bdi></span>
    </div>
    <button type="button" class="learning-video-fullscreen"
        onclick="if (document.fullscreenElement) document.exitFullscreen(); else this.parentElement.requestFullscreen?.();"
        aria-label="تغییر حالت تمام‌صفحه">تمام‌صفحه</button>
@endauth
