@auth
    @once
        <style>
            .learning-video-frame:fullscreen { display: grid; place-items: center; background: #000 }
            .learning-video-frame:fullscreen video { width: 100% !important; height: 100% !important; max-height: none !important; object-fit: contain !important }
            .learning-video-fullscreen { position: absolute; z-index: 3; inset-block-start: 12px; inset-inline-end: 12px; padding: 6px 10px; border: 0; border-radius: 8px; background: #132642e8; color: white; font: inherit; font-size: 12px; cursor: pointer }
        </style>
    @endonce
    @php($identity = \App\Services\Learning\ContentWatermark::identity(auth()->user()))
    <div aria-hidden="true" style="position:absolute;inset:0;z-index:2;pointer-events:none;overflow:hidden">
        <b style="position:absolute;inset-block-start:12px;inset-inline-start:12px;padding:4px 10px;border-radius:8px;background:#132642e8;color:white;font-size:12px">آکادمی شیخان</b>
        @foreach([[27, 12], [50, 39], [68, 17]] as [$top, $left])
            <span dir="ltr" style="position:absolute;top:{{ $top }}%;left:{{ $left }}%;max-width:55%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;transform:rotate(-12deg);color:#ffffff8c;text-shadow:0 1px 3px #000b;font-size:clamp(10px,1.5vw,16px);font-weight:700">{{ $identity }}</span>
        @endforeach
    </div>
    <button type="button" class="learning-video-fullscreen"
        onclick="if (document.fullscreenElement) document.exitFullscreen(); else this.parentElement.requestFullscreen?.();"
        aria-label="تغییر حالت تمام‌صفحه">تمام‌صفحه</button>
@endauth
