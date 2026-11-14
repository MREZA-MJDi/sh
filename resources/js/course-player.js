export function initCoursePlayer() {
    const root = document.querySelector('[data-course-player]');
    if (!root) return;

    const player = root.querySelector('[data-video-player]');
    const items = [...root.querySelectorAll('[data-lesson]')];
    const titleEl = root.querySelector('[data-lesson-title]');
    const descEl = root.querySelector('[data-lesson-desc]');
    const doneBtn = root.querySelector('[data-mark-done]');
    const nextBtn = root.querySelector('[data-next]');
    const bar = root.querySelector('[data-progress-bar]');
    const progressText = root.querySelector('[data-progress-text]');
    const progressEl = root.querySelector('[data-progress]');

    const done = new Set();
    let index = 0;

    const render = () => {
        const pct = Math.round((done.size / items.length) * 100);
        bar.style.width = `${pct}%`;
        progressEl.setAttribute('aria-valuenow', pct);
        progressText.textContent = `${done.size} از ${items.length} جلسه (${pct}٪)`;
        doneBtn.textContent = done.has(index) ? 'دیده شده ✓' : 'علامت‌گذاری به‌عنوان دیده‌شده';
        nextBtn.hidden = index === items.length - 1;
    };

    const select = (i, autoplay = false) => {
        index = i;
        const el = items[i];
        items.forEach((b, j) => {
            if (j === i) b.setAttribute('aria-current', 'true');
            else b.removeAttribute('aria-current');
        });
        titleEl.textContent = el.dataset.title;
        descEl.textContent = `توضیحات جلسهٔ «${el.dataset.title}» اینجا نمایش داده می‌شود.`;
        player.player.load(el.dataset.src, autoplay);
        render();
    };

    const markDone = () => {
        done.add(index);
        items[index].querySelector('[data-check]').textContent = '✓';
        items[index].dataset.done = '';
        render();
    };

    items.forEach((b, i) => b.addEventListener('click', () => select(i)));
    doneBtn.addEventListener('click', markDone);
    nextBtn.addEventListener('click', () => select(index + 1));
    player.addEventListener('player:ended', () => {
        markDone();
        if (index < items.length - 1) select(index + 1, true);
    });

    select(0);
}
