const RATES = [0.75, 1, 1.25, 1.5, 2];

const fmt = (s) => {
    if (!isFinite(s)) return '00:00';
    const m = String(Math.floor(s / 60)).padStart(2, '0');
    const sec = String(Math.floor(s % 60)).padStart(2, '0');
    return `${m}:${sec}`;
};

export function initVideoPlayers() {
    document.querySelectorAll('[data-video-player]').forEach(setup);
}

function setup(root) {
    const v = root.querySelector('video');
    const q = (name) => root.querySelector(`[data-${name}]`);
    const bigPlay = q('big-play');
    const toggleBtn = q('toggle');
    const seek = q('seek');
    const timeEl = q('time');
    const vol = q('volume');
    const rateBtn = q('rate');
    const fsBtn = q('fullscreen');
    let rate = 1;

    const toggle = () => (v.paused ? v.play() : v.pause());
    const skip = (s) => {
        v.currentTime = Math.max(0, Math.min(v.duration || 0, v.currentTime + s));
    };
    const updateTime = () => {
        timeEl.textContent = `${fmt(v.currentTime)} / ${fmt(v.duration)}`;
    };

    v.addEventListener('click', toggle);
    bigPlay.addEventListener('click', toggle);
    toggleBtn.addEventListener('click', toggle);
    q('back').addEventListener('click', () => skip(-10));
    q('forward').addEventListener('click', () => skip(10));

    v.addEventListener('play', () => {
        toggleBtn.textContent = '❚❚';
        bigPlay.hidden = true;
    });
    v.addEventListener('pause', () => {
        toggleBtn.textContent = '▶';
        bigPlay.hidden = false;
    });
    v.addEventListener('loadedmetadata', () => {
        seek.max = v.duration;
        updateTime();
    });
    v.addEventListener('timeupdate', () => {
        seek.value = v.currentTime;
        updateTime();
    });
    v.addEventListener('ended', () => {
        root.dispatchEvent(new CustomEvent('player:ended', { bubbles: true }));
    });

    seek.addEventListener('input', () => (v.currentTime = Number(seek.value)));
    vol.addEventListener('input', () => {
        v.volume = Number(vol.value);
        v.muted = Number(vol.value) === 0;
    });
    rateBtn.addEventListener('click', () => {
        rate = RATES[(RATES.indexOf(rate) + 1) % RATES.length];
        v.playbackRate = rate;
        rateBtn.textContent = `${rate}x`;
    });
    fsBtn.addEventListener('click', () => {
        document.fullscreenElement ? document.exitFullscreen() : root.requestFullscreen();
    });
    root.addEventListener('keydown', (e) => {
        if (e.target !== root) return;
        if (e.code === 'Space') { e.preventDefault(); toggle(); }
        if (e.code === 'ArrowLeft') skip(-5);
        if (e.code === 'ArrowRight') skip(5);
    });

    root.player = {
        load(src, autoplay = false) {
            v.src = src;
            v.load();
            seek.value = 0;
            v.playbackRate = rate;
            if (autoplay) v.play().catch(() => {});
        },
    };
}
