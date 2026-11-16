export function initVideoPlayers(root = document) {
    root.querySelectorAll('video[data-lazy-src]').forEach((video) => {
        const loadSource = () => {
            if (!video.src && video.dataset.lazySrc) {
                video.src = video.dataset.lazySrc;
            }
        };

        video.addEventListener('play', loadSource, { once: true });
    });
}
