@if($registrationTicket ?? false)
@php
    $ticketAction = $registrationTicket->action_url ?: route('public.courses.index');
    $ticketActionLabel = $registrationTicket->action_label ?: 'مشاهده دوره‌ها و ثبت‌نام';
    $ticketDescription = trim(strip_tags($registrationTicket->body));
@endphp
<section class="sh-ticket-section" aria-labelledby="sh-ticket-title" data-sh-ticket>
    <div class="sh-ticket-shell">
        <article class="sh-ticket" data-ticket-card>
            <div class="sh-ticket-foil" aria-hidden="true"></div>

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
    const storageKey = 'sh-ticket-dismissed-{{ $registrationTicket->getKey() }}';

    const storage = {
        isDismissed() {
            try { return sessionStorage.getItem(storageKey) === '1'; }
            catch (_) { return false; }
        },
        dismiss() {
            try { sessionStorage.setItem(storageKey, '1'); } catch (_) {}
        },
        restore() {
            try { sessionStorage.removeItem(storageKey); } catch (_) {}
        }
    };

    function showTicket() {
        storage.restore();
        root.hidden = false;
        root.classList.remove('is-dismissed');
        card.hidden = false;
        reopenButton.hidden = true;
        card.classList.remove('is-leaving');
        card.classList.add('is-entering');
        window.setTimeout(() => card.classList.remove('is-entering'), 420);
        closeButton.focus({ preventScroll: true });
    }

    function hideTicket() {
        if (card.hidden || card.classList.contains('is-leaving')) return;
        card.classList.add('is-leaving');
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        window.setTimeout(() => {
            card.hidden = true;
            card.classList.remove('is-leaving');
            root.classList.add('is-dismissed');
            reopenButton.hidden = false;
            storage.dismiss();
            reopenButton.focus({ preventScroll: true });
        }, reducedMotion ? 0 : 180);
    }

    closeButton.addEventListener('click', hideTicket);
    reopenButton.addEventListener('click', showTicket);
    root.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !card.hidden) {
            event.preventDefault();
            hideTicket();
        }
    });

    if (storage.isDismissed()) {
        card.hidden = true;
        reopenButton.hidden = false;
        root.classList.add('is-dismissed');
    }
})();
</script>
@endpush
@endif
