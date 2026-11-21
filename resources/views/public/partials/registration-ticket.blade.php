@if($registrationTicket ?? false)
@php
    $ticketEndsAt = $registrationTicket->ends_at?->toIso8601String();
    $ticketAction = $registrationTicket->action_url ?: route('public.courses.index');
    $ticketActionLabel = $registrationTicket->action_label ?: 'مشاهده دوره‌ها و ثبت‌نام';
@endphp
<section class="sh-ticket-section" aria-labelledby="sh-ticket-title" data-sh-ticket data-ends-at="{{ $ticketEndsAt }}">
    <div class="sh-ticket-shell">
        <article class="sh-ticket" data-ticket-card>
            <canvas class="sh-ticket-canvas" data-ticket-canvas aria-hidden="true"></canvas>
            <div class="sh-ticket-foil" aria-hidden="true"></div>
            <button class="sh-ticket-close" type="button" data-ticket-close aria-label="بستن اطلاعیه">×</button>
            <div class="sh-ticket-main">
                <span class="sh-ticket-tag"><i aria-hidden="true"></i>{{ $registrationTicket->priority === 'urgent' ? 'فرصت مهم آکادمی' : 'اطلاعیه ویژه آکادمی شیخان' }}</span>
                <div class="sh-ticket-mark" aria-hidden="true"><span>ADMIT</span><b>✦</b><small>ACADEMY / {{ $registrationTicket->id }}</small></div>
                <h2 id="sh-ticket-title">{{ $registrationTicket->title }}</h2>
                <p id="sh-ticket-description">{{ \Illuminate\Support\Str::limit(strip_tags($registrationTicket->body), 300) }}</p>
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
                    <div class="sh-ticket-no-expiry"><span class="sh-ticket-label">اطلاعیه آکادمی</span><strong>همراه مسیر یادگیری تو</strong><p>برای مشاهده جزئیات و شرایط، از دکمه زیر استفاده کن.</p></div>
                @endif
                <div class="sh-ticket-barcode" aria-hidden="true"></div>
                <span class="sh-ticket-id">SHEIKHAN · ACADEMY</span>
            </aside>
        </article>
    </div>
    <button class="sh-ticket-reopen" type="button" data-ticket-reopen hidden><i aria-hidden="true"></i>مشاهده اطلاعیه ویژه</button>
</section>
@push('scripts')
<script>
(function () {
    const root = document.querySelector('[data-sh-ticket]');
    if (!root || root.dataset.initialized === '1') return;
    root.dataset.initialized = '1';
    const card = root.querySelector('[data-ticket-card]');
    const closeButton = root.querySelector('[data-ticket-close]');
    const reopenButton = root.querySelector('[data-ticket-reopen]');
    const canvas = root.querySelector('[data-ticket-canvas]');
    const context = canvas?.getContext('2d');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const endTime = root.dataset.endsAt ? Date.parse(root.dataset.endsAt) : null;
    const storageKey = 'sh-ticket-dismissed-{{ $registrationTicket->id }}';
    let timer = null, frame = null, points = [], width = 0, height = 0;

    const fa = value => Number(value).toLocaleString('fa-IR', {minimumIntegerDigits: 2, useGrouping: false});
    function countdown() {
        if (!Number.isFinite(endTime)) return;
        const seconds = Math.max(0, Math.floor((endTime - Date.now()) / 1000));
        const values = {days: Math.floor(seconds / 86400), hours: Math.floor(seconds % 86400 / 3600), minutes: Math.floor(seconds % 3600 / 60), seconds: seconds % 60};
        Object.entries(values).forEach(([key, value]) => {
            const node = root.querySelector('[data-' + key + ']');
            if (node) node.textContent = fa(value);
        });
        if (seconds === 0) {
            clearInterval(timer);
            root.hidden = true;
            reopenButton.hidden = true;
        }
    }
    function resizeCanvas() {
        if (!canvas || !context) return;
        const rect = canvas.getBoundingClientRect();
        width = rect.width; height = rect.height;
        const ratio = Math.min(window.devicePixelRatio || 1, 1.5);
        canvas.width = Math.round(width * ratio); canvas.height = Math.round(height * ratio);
        context.setTransform(ratio, 0, 0, ratio, 0, 0);
        const colors = ['#22e1ff', '#ff3d81', '#ffc83d', '#7a5cff'];
        points = Array.from({length: width < 640 ? 22 : 38}, (_, i) => ({x: Math.random()*width, y: Math.random()*height, vx:(Math.random()-.5)*.35, vy:(Math.random()-.5)*.35, color:colors[i%4]}));
    }
    function animate() {
        if (!context || root.hidden || reducedMotion) return;
        context.clearRect(0,0,width,height);
        points.forEach((point,i) => {
            point.x += point.vx; point.y += point.vy;
            if(point.x<0||point.x>width) point.vx *= -1;
            if(point.y<0||point.y>height) point.vy *= -1;
            context.fillStyle=point.color; context.globalAlpha=.65; context.beginPath(); context.arc(point.x,point.y,1.5,0,Math.PI*2); context.fill();
            for(let j=i+1;j<points.length;j++){const other=points[j],d=Math.hypot(point.x-other.x,point.y-other.y);if(d<105){context.globalAlpha=(1-d/105)*.16;context.strokeStyle=point.color;context.beginPath();context.moveTo(point.x,point.y);context.lineTo(other.x,other.y);context.stroke();}}
        });
        frame=requestAnimationFrame(animate);
    }
    function stop(){if(frame)cancelAnimationFrame(frame);frame=null;}
    function show(){root.hidden=false;card.hidden=false;reopenButton.hidden=true;resizeCanvas();stop();animate();if(Number.isFinite(endTime)){countdown();clearInterval(timer);timer=window.setInterval(countdown,1000);}}
    function hide(){card.hidden=true;reopenButton.hidden=false;stop();clearInterval(timer);try{sessionStorage.setItem(storageKey,'1')}catch(_){}}
    closeButton?.addEventListener('click',hide);
    reopenButton?.addEventListener('click',show);
    root.addEventListener('pointermove',event=>{
        if(reducedMotion||!card||window.innerWidth<760||card.hidden)return;
        const rect=card.getBoundingClientRect(),x=(event.clientX-rect.left)/rect.width-.5,y=(event.clientY-rect.top)/rect.height-.5;
        card.style.setProperty('--ticket-x',((event.clientX-rect.left)/rect.width*100)+'%');
        card.style.setProperty('--ticket-y',((event.clientY-rect.top)/rect.height*100)+'%');
        card.style.transform='perspective(1200px) rotateX('+(-y*3.5)+'deg) rotateY('+(x*4.5)+'deg)';
    });
    root.addEventListener('pointerleave',()=>{if(card)card.style.transform='';});
    window.addEventListener('resize',()=>{if(!root.hidden)resizeCanvas();});
    window.addEventListener('pagehide',()=>{stop();clearInterval(timer);});
    let dismissed=false;try{dismissed=sessionStorage.getItem(storageKey)==='1'}catch(_){}
    if(dismissed){card.hidden=true;reopenButton.hidden=false;}else{show();}
})();
</script>
@endpush
@endif
