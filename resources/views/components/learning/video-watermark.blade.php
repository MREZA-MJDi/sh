@props(['source' => null, 'fullscreen' => true])
@auth
    @once
        <style>
            .learning-video-frame { -webkit-touch-callout: none; user-select: none; -webkit-user-select: none }
            @media print { .learning-video-frame { display: none !important } }
            .learning-video-frame:fullscreen { display: grid; place-items: center; background: #000 }
            .learning-video-frame:fullscreen video { width: 100% !important; height: 100% !important; max-height: none !important; object-fit: contain !important }
            .learning-video-watermark { position: absolute; inset: 0; z-index: 2; display: grid; place-items: center; pointer-events: none; overflow: hidden }
            .learning-video-brand { position: absolute; z-index: 3; inset-block-start: 12px; left: 50%; transform: translateX(-50%); pointer-events: none }
            .learning-video-prepare { position: absolute; z-index: 4; top: 50%; left: 50%; transform: translate(-50%, -50%); padding: .7em 1em; border: 0; border-radius: .65em; background: #132642; color: #fff; font: inherit; cursor: pointer }
            .learning-video-watermark span {
                position: absolute; max-width: 46%; padding: .35em .8em; border-radius: 999px;
                background: #13264220; color: #ffffff68; text-shadow: 0 1px 4px #0006;
                font-size: clamp(11px, 2.4vw, 26px); font-weight: 700;
                overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
                filter: blur(.45px); transform: translate(-50%, -50%) rotate(-12deg);
            }
            .learning-video-watermark .mark-upper { left: 22%; top: 28% }
            .learning-video-watermark .mark-middle { left: 50%; top: 50% }
            .learning-video-watermark .mark-lower { left: 82%; top: 71%; max-width: 32% }
            .learning-video-fullscreen { position: absolute; z-index: 3; inset-block-start: 12px; inset-inline-end: 12px; padding: 6px 10px; border: 0; border-radius: 8px; background: #132642e8; color: white; font: inherit; font-size: 12px; cursor: pointer }
        </style>
        <script>
            for (const eventName of ['contextmenu', 'dragstart']) {
                document.addEventListener(eventName, (event) => {
                    if (event.target.closest('.learning-video-frame')) event.preventDefault();
                });
            }
            document.addEventListener('keydown', (event) => {
                const frame = document.fullscreenElement?.closest('.learning-video-frame')
                    || event.target.closest('.learning-video-frame');
                if (frame && (event.ctrlKey || event.metaKey) && ['s', 'p'].includes(event.key.toLowerCase())) {
                    event.preventDefault();
                }
            });
            document.addEventListener('click', async (event) => {
                const button = event.target.closest('[data-viewer-video]');
                if (!button || button.disabled) return;
                const player = button.parentElement.querySelector('video');
                const url = button.dataset.viewerVideo;
                button.disabled = true;
                button.textContent = 'در حال آماده‌سازی ویدیو…';

                try {
                    for (let attempt = 0; attempt < 180; attempt++) {
                        const result = await fetch(url, { method: 'HEAD', cache: 'no-store', redirect: 'manual' });
                        if (result.ok && result.headers.get('Content-Type')?.startsWith('video/')) {
                            player.src = url;
                            button.remove();
                            player.play().catch(() => {});
                            return;
                        }
                        if (result.status !== 503 || !result.headers.has('Retry-After')) break;
                        await new Promise((resolve) => setTimeout(resolve, 4000));
                    }
                } catch {
                    // Keep the controls available for a manual retry.
                }
                button.textContent = 'پخش آماده نیست. دوباره تلاش کنید';
                button.disabled = false;
            });
        </script>
    @endonce
    @php($identity = \App\Services\Learning\ContentWatermark::identity(auth()->user()))
    <x-learning.academy-badge class="learning-video-brand"/>
    @if($source)
        <button type="button" class="learning-video-prepare" data-viewer-video="{{ $source }}" aria-live="polite">پخش ویدیو</button>
    @else
    <div class="learning-video-watermark" aria-hidden="true">
        <span class="mark-upper"><bdi dir="ltr">{{ $identity }}</bdi></span>
        <span class="mark-middle"><bdi dir="ltr">{{ $identity }}</bdi></span>
        <span class="mark-lower"><bdi dir="ltr">{{ $identity }}</bdi></span>
    </div>
    @endif
    @if($fullscreen)
    <button type="button" class="learning-video-fullscreen"
        onclick="if (document.fullscreenElement) document.exitFullscreen(); else this.parentElement.requestFullscreen?.();"
        aria-label="تغییر حالت تمام‌صفحه">تمام‌صفحه</button>
    @endif
@endauth
