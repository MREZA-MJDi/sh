@if($registrationTicket ?? false)
@php
    $ticketEndsAt = $registrationTicket->ends_at?->toIso8601String();
    $ticketAction = $registrationTicket->action_url ?: route('public.courses.index');
    $ticketActionLabel = $registrationTicket->action_label ?: 'مشاهده دوره‌ها و ثبت‌نام';
@endphp
<div class="sh-ticket-overlay" data-sh-ticket data-ends-at="{{ $ticketEndsAt }}" hidden>
    <canvas class="sh-ticket-canvas" data-ticket-canvas aria-hidden="true"></canvas>
    <section class="sh-ticket" role="dialog" aria-modal="true" aria-labelledby="sh-ticket-title" aria-describedby="sh-ticket-description">
        <div class="sh-ticket-foil" aria-hidden="true"></div>
        <button class="sh-ticket-close" type="button" data-ticket-close aria-label="بستن اعلان">×</button>
        <div class="sh-ticket-main">
            <span class="sh-ticket-tag"><i aria-hidden="true"></i>{{ $registrationTicket->priority === 'urgent' ? 'فرصت محدود ثبت‌نام' : 'اطلاعیه ویژه آکادمی' }}</span>
            <div class="sh-ticket-mark" aria-hidden="true"><span>ADMIT</span><b>✦</b></div>
            <h2 id="sh-ticket-title">{{ $registrationTicket->title }}</h2>
            <p id="sh-ticket-description">{{ \Illuminate\Support\Str::limit(strip_tags($registrationTicket->body), 260) }}</p>
            <div class="sh-ticket-actions">
                <a class="sh-ticket-cta" href="{{ $ticketAction }}">{{ $ticketActionLabel }} <span aria-hidden="true">←</span></a>
                <a class="sh-ticket-secondary" href="{{ route('public.contact') }}">مشاوره و راهنمایی</a>
            </div>
        </div>
        <aside class="sh-ticket-stub">
            @if($ticketEndsAt)
                <div>
                    <span class="sh-ticket-label">پایان مهلت اطلاعیه</span>
                    <div class="sh-ticket-countdown" data-ticket-countdown>
                        <div><b data-days>۰۰</b><small>روز</small></div>
                        <div><b data-hours>۰۰</b><small>ساعت</small></div>
                        <div><b data-minutes>۰۰</b><small>دقیقه</small></div>
                        <div><b data-seconds>۰۰</b><small>ثانیه</small></div>
                    </div>
                </div>
            @else
                <div class="sh-ticket-no-expiry"><span class="sh-ticket-label">اطلاعیه آکادمی</span><strong>همراه مسیر یادگیری تو</strong><p>جزئیات و شرایط را در صفحه دوره‌ها ببین.</p></div>
            @endif
            <div class="sh-ticket-barcode" aria-hidden="true"></div>
            <span class="sh-ticket-id">SHEIKHAN · ACADEMY</span>
        </aside>
    </section>
</div>
<button class="sh-ticket-reopen" type="button" data-ticket-reopen hidden><i aria-hidden="true"></i>مشاهده اطلاعیه ویژه</button>
@push('scripts')
<script>
(function () {
    const root = document.querySelector('[data-sh-ticket]');
    if (!root || root.dataset.initialized === '1') return;
    root.dataset.initialized = '1';

    const closeButton = root.querySelector('[data-ticket-close]');
    const reopenButton = document.querySelector('[data-ticket-reopen]');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const storageKey = 'sh-ticket-dismissed-{{ $registrationTicket->id }}';
    const endValue = root.dataset.endsAt;
    const endTime = endValue ? Date.parse(endValue) : null;
    let interval = null;
    let previousFocus = null;
    let frame = null;

    const digits = value => Number(value).toLocaleString('fa-IR', {minimumIntegerDigits: 2, useGrouping: false});

    function updateCountdown() {
        if (!endTime || !Number.isFinite(endTime)) return;
        const remaining = Math.max(0, Math.floor((endTime - Date.now()) / 1000));
        const values = {
            days: Math.floor(remaining / 86400),
            hours: Math.floor((remaining % 86400) / 3600),
            minutes: Math.floor((remaining % 3600) / 60),
            seconds: remaining % 60
        };
        Object.entries(values).forEach(([key, value]) => {
            const node = root.querySelector('[data-' + key + ']');
            if (node) node.textContent = digits(value);
        });
        if (remaining === 0) {
            clearInterval(interval);
            root.hidden = true;
            reopenButton.hidden = true;
            document.body.classList.remove('sh-ticket-open');
            stopCanvas();
        }
    }

    function stopCanvas() {
        if (frame) cancelAnimationFrame(frame);
        frame = null;
    }

    function startCanvas() {
        const canvas = root.querySelector('[data-ticket-canvas]');
        const context = canvas?.getContext('2d');
        if (!canvas || !context || reducedMotion) return;
        const dpr = Math.min(window.devicePixelRatio || 1, 1.5);
        const width = window.innerWidth;
        const height = window.innerHeight;
        canvas.width = Math.floor(width * dpr);
        canvas.height = Math.floor(height * dpr);
        context.setTransform(dpr, 0, 0, dpr, 0, 0);
        const palette = ['#22e1ff', '#ff3d81', '#ffc83d', '#7a5cff'];
        const particles = Array.from({length: width < 700 ? 28 : 48}, (_, index) => ({
            x: Math.random() * width, y: Math.random() * height,
            vx: (Math.random() - .5) * .28, vy: (Math.random() - .5) * .28,
            color: palette[index % palette.length]
        }));
        function draw() {
            if (root.hidden) return;
            context.clearRect(0, 0, width, height);
            particles.forEach((point, index) => {
                point.x += point.vx; point.y += point.vy;
                if (point.x < 0 || point.x > width) point.vx *= -1;
                if (point.y < 0 || point.y > height) point.vy *= -1;
                context.fillStyle = point.color;
                context.globalAlpha = .65;
                context.beginPath(); context.arc(point.x, point.y, 1.5, 0, Math.PI * 2); context.fill();
                for (let next = index + 1; next < particles.length; next++) {
                    const other = particles[next];
                    const distance = Math.hypot(point.x - other.x, point.y - other.y);
                    if (distance < 105) {
                        context.globalAlpha = (1 - distance / 105) * .16;
                        context.strokeStyle = point.color;
                        context.beginPath(); context.moveTo(point.x, point.y); context.lineTo(other.x, other.y); context.stroke();
                    }
                }
            });
            frame = requestAnimationFrame(draw);
        }
        draw();
    }

    function openTicket() {
        previousFocus = document.activeElement;
        root.hidden = false;
        reopenButton.hidden = true;
        document.body.classList.add('sh-ticket-open');
        updateCountdown();
        if (endTime) interval = window.setInterval(updateCountdown, 1000);
        startCanvas();
        closeButton.focus({preventScroll: true});
    }

    function closeTicket() {
        root.classList.add('is-closing');
        window.setTimeout(() => {
            root.hidden = true;
            root.classList.remove('is-closing');
            reopenButton.hidden = false;
            document.body.classList.remove('sh-ticket-open');
            clearInterval(interval);
            stopCanvas();
            previousFocus?.focus?.({preventScroll: true});
        }, reducedMotion ? 0 : 260);
        try { window.sessionStorage.setItem(storageKey, '1'); } catch (_) {}
    }

    closeButton?.addEventListener('click', closeTicket);
    reopenButton?.addEventListener('click', openTicket);
    root.addEventListener('click', event => { if (event.target === root) closeTicket(); });
    document.addEventListener('keydown', event => {
        if (root.hidden) return;
        if (event.key === 'Escape') closeTicket();
        if (event.key === 'Tab') {
            const focusable = Array.from(root.querySelectorAll('a[href], button:not([disabled])'));
            if (!focusable.length) return;
            const first = focusable[0], last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    });
    window.addEventListener('pagehide', () => { clearInterval(interval); stopCanvas(); });

    let dismissed = false;
    try { dismissed = window.sessionStorage.getItem(storageKey) === '1'; } catch (_) {}
    if (dismissed) reopenButton.hidden = false;
    else window.setTimeout(openTicket, reducedMotion ? 250 : 650);
})();
</script>
@endpush
@endif
