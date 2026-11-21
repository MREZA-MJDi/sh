@if($registrationTicket ?? false)
@php
    $ticketAction = $registrationTicket->action_url ?: route('public.courses.index');
    $ticketActionLabel = $registrationTicket->action_label ?: 'مشاهده دوره‌ها و ثبت‌نام';
    $ticketDescription = trim(strip_tags($registrationTicket->body));
@endphp
<section class="sh-ticket-section" aria-labelledby="sh-ticket-title" data-sh-ticket>
    <div class="sh-ticket-shell">
        <article class="sh-ticket" data-ticket-card>
            <canvas class="sh-ticket-canvas" data-ticket-canvas aria-hidden="true"></canvas>
            <div class="sh-ticket-foil" aria-hidden="true"></div>
            <div class="sh-ticket-glow" aria-hidden="true"></div>

            <button class="sh-ticket-close" type="button" data-ticket-close aria-label="بستن اطلاعیه">×</button>

            <div class="sh-ticket-main">
                <span class="sh-ticket-tag"><i aria-hidden="true"></i>اطلاعیه ویژه آکادمی شیخان</span>
                <div class="sh-ticket-mark" aria-hidden="true">
                    <span>ADMIT</span><b>✦</b><small>SHEIKHAN ACADEMY</small>
                </div>
                <h2 id="sh-ticket-title">{{ $registrationTicket->title }}</h2>
                <p id="sh-ticket-description">{{ \Illuminate\Support\Str::limit($ticketDescription, 420) }}</p>
                <div class="sh-ticket-actions">
                    <a class="sh-ticket-cta" data-ticket-cta href="{{ $ticketAction }}">
                        <span>{{ $ticketActionLabel }}</span><span class="sh-ticket-cta-arrow" aria-hidden="true">←</span>
                    </a>
                    <a class="sh-ticket-secondary" href="{{ route('public.contact') }}">مشاوره و راهنمایی</a>
                </div>
            </div>

            <aside class="sh-ticket-stub" aria-label="اطلاعات آکادمی">
                <div class="sh-ticket-seal" aria-hidden="true"><span>✦</span></div>
                <span class="sh-ticket-stub-kicker">YOUR NEXT CHAPTER</span>
                <strong>مسیر یادگیری از همین‌جا شروع می‌شود</strong>
                <p>برای آشنایی با دوره‌ها و جزئیات اطلاعیه، ادامه را ببین.</p>
                <div class="sh-ticket-barcode" aria-hidden="true"></div>
                <span class="sh-ticket-id">SHEIKHAN · ACADEMY</span>
            </aside>
        </article>
    </div>

    <button class="sh-ticket-reopen" type="button" data-ticket-reopen hidden>
        <i aria-hidden="true"></i><span>مشاهده اطلاعیه ویژه</span>
    </button>
    <span class="sh-ticket-live-region" data-ticket-status role="status" aria-live="polite"></span>
</section>

@push('scripts')
<script>
(() => {
    const root = document.querySelector('[data-sh-ticket]');
    if (!root || root.dataset.initialized === '1') return;
    root.dataset.initialized = '1';

    const card = root.querySelector('[data-ticket-card]');
    const closeButton = root.querySelector('[data-ticket-close]');
    const reopenButton = root.querySelector('[data-ticket-reopen]');
    const cta = root.querySelector('[data-ticket-cta]');
    const canvas = root.querySelector('[data-ticket-canvas]');
    const context = canvas?.getContext('2d');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const storageKey = 'sh-ticket-dismissed-{{ $registrationTicket->getKey() }}';
    const colors = ['#22e1ff', '#ff3d81', '#ffc83d', '#7a5cff'];
    let points = [];
    let animationFrame = 0;
    let resizeObserver = null;
    let previousFocus = null;
    let isDestroyed = false;

    const safeStorage = {
        get() { try { return window.sessionStorage.getItem(storageKey) === '1'; } catch (_) { return false; } },
        set() { try { window.sessionStorage.setItem(storageKey, '1'); } catch (_) { /* Storage may be unavailable. */ } },
        clear() { try { window.sessionStorage.removeItem(storageKey); } catch (_) { /* Storage may be unavailable. */ } }
    };

    function resizeCanvas() {
        if (!canvas || !context || isDestroyed) return;
        const bounds = card.getBoundingClientRect();
        const ratio = Math.min(window.devicePixelRatio || 1, 1.5);
        canvas.width = Math.max(1, Math.round(bounds.width * ratio));
        canvas.height = Math.max(1, Math.round(bounds.height * ratio));
        canvas.style.width = bounds.width + 'px';
        canvas.style.height = bounds.height + 'px';
        context.setTransform(ratio, 0, 0, ratio, 0, 0);
        points = Array.from({ length: bounds.width < 640 ? 20 : 34 }, (_, index) => ({
            x: Math.random() * bounds.width,
            y: Math.random() * bounds.height,
            vx: (Math.random() - 0.5) * 0.32,
            vy: (Math.random() - 0.5) * 0.32,
            color: colors[index % colors.length]
        }));
    }

    function drawParticles() {
        if (!context || card.hidden || document.hidden || reducedMotion || isDestroyed) return;
        const width = canvas.clientWidth;
        const height = canvas.clientHeight;
        context.clearRect(0, 0, width, height);

        points.forEach((point, index) => {
            point.x += point.vx;
            point.y += point.vy;
            if (point.x < 0 || point.x > width) point.vx *= -1;
            if (point.y < 0 || point.y > height) point.vy *= -1;

            context.globalAlpha = 0.62;
            context.fillStyle = point.color;
            context.beginPath();
            context.arc(point.x, point.y, 1.45, 0, Math.PI * 2);
            context.fill();

            for (let next = index + 1; next < points.length; next += 1) {
                const target = points[next];
                const distance = Math.hypot(point.x - target.x, point.y - target.y);
                if (distance >= 108) continue;
                context.globalAlpha = (1 - distance / 108) * 0.17;
                context.strokeStyle = point.color;
                context.lineWidth = 0.7;
                context.beginPath();
                context.moveTo(point.x, point.y);
                context.lineTo(target.x, target.y);
                context.stroke();
            }
        });
        context.globalAlpha = 1;
        animationFrame = window.requestAnimationFrame(drawParticles);
    }

    function stopParticles() {
        if (animationFrame) window.cancelAnimationFrame(animationFrame);
        animationFrame = 0;
    }

    function startParticles() {
        if (reducedMotion || !context || animationFrame || card.hidden) return;
        animationFrame = window.requestAnimationFrame(drawParticles);
    }

    function showTicket({ restoreFocus = false } = {}) {
        safeStorage.clear();
        root.hidden = false;
        card.hidden = false;
        reopenButton.hidden = true;
        card.classList.remove('is-leaving');
        card.classList.add('is-entering');
        resizeCanvas();
        startParticles();
        window.setTimeout(() => card.classList.remove('is-entering'), 700);
        if (restoreFocus) closeButton.focus({ preventScroll: true });
    }

    function hideTicket() {
        if (card.hidden || card.classList.contains('is-leaving')) return;
        previousFocus = document.activeElement;
        stopParticles();
        card.classList.remove('is-entering');
        card.classList.add('is-leaving');
        window.setTimeout(() => {
            card.hidden = true;
            card.classList.remove('is-leaving');
            reopenButton.hidden = false;
            safeStorage.set();
            reopenButton.focus({ preventScroll: true });
        }, reducedMotion ? 0 : 280);
    }

    function resetTilt() {
        card.style.removeProperty('transform');
        card.style.removeProperty('--ticket-x');
        card.style.removeProperty('--ticket-y');
    }

    function handlePointerMove(event) {
        if (reducedMotion || card.hidden || window.matchMedia('(max-width: 760px)').matches || event.pointerType === 'touch') return;
        const bounds = card.getBoundingClientRect();
        const x = (event.clientX - bounds.left) / bounds.width;
        const y = (event.clientY - bounds.top) / bounds.height;
        card.style.setProperty('--ticket-x', (x * 100) + '%');
        card.style.setProperty('--ticket-y', (y * 100) + '%');
        card.style.transform = 'perspective(1400px) rotateX(' + ((0.5 - y) * 5) + 'deg) rotateY(' + ((x - 0.5) * 6) + 'deg)';
    }

    function handleMagneticMove(event) {
        if (reducedMotion || !cta || window.matchMedia('(max-width: 760px)').matches || event.pointerType === 'touch') return;
        const bounds = cta.getBoundingClientRect();
        const dx = event.clientX - (bounds.left + bounds.width / 2);
        const dy = event.clientY - (bounds.top + bounds.height / 2);
        cta.style.setProperty('--magnetic-x', (dx * 0.12) + 'px');
        cta.style.setProperty('--magnetic-y', (dy * 0.16) + 'px');
    }

    function resetMagnetic() {
        if (!cta) return;
        cta.style.removeProperty('--magnetic-x');
        cta.style.removeProperty('--magnetic-y');
    }

    function createClickSpark(event) {
        if (reducedMotion || !cta) return;
        const bounds = cta.getBoundingClientRect();
        const spark = document.createElement('span');
        spark.className = 'sh-ticket-spark';
        spark.setAttribute('aria-hidden', 'true');
        spark.style.left = (event.clientX - bounds.left) + 'px';
        spark.style.top = (event.clientY - bounds.top) + 'px';
        cta.append(spark);
        spark.addEventListener('animationend', () => spark.remove(), { once: true });
    }

    closeButton.addEventListener('click', hideTicket);
    reopenButton.addEventListener('click', () => showTicket({ restoreFocus: true }));
    card.addEventListener('pointermove', handlePointerMove);
    card.addEventListener('pointerleave', resetTilt);
    cta?.addEventListener('pointermove', handleMagneticMove);
    cta?.addEventListener('pointerleave', resetMagnetic);
    cta?.addEventListener('click', createClickSpark);

    const onVisibilityChange = () => {
        if (document.hidden) stopParticles();
        else startParticles();
    };
    document.addEventListener('visibilitychange', onVisibilityChange);
    window.addEventListener('pagehide', () => {
        stopParticles();
        isDestroyed = true;
    }, { once: true });

    if ('ResizeObserver' in window) {
        resizeObserver = new ResizeObserver(resizeCanvas);
        resizeObserver.observe(card);
    } else {
        window.addEventListener('resize', resizeCanvas, { passive: true });
    }

    if (safeStorage.get()) {
        card.hidden = true;
        reopenButton.hidden = false;
    } else {
        showTicket();
    }
})();
</script>
@endpush
@endif
