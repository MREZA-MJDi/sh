@if($registrationTicket ?? false)
@php
    $announcementUrl = $registrationTicket->action_url ?: route('public.courses.index');
    $announcementLabel = $registrationTicket->action_label ?: 'مشاهده دوره‌ها';
@endphp
<section class="sh-public-announcement" aria-labelledby="sh-public-announcement-title">
    <div class="sh-public-announcement__card">
        <span class="sh-public-announcement__eyebrow">اطلاعیه آکادمی شیخان</span>
        <div class="sh-public-announcement__content">
            <div>
                <h2 id="sh-public-announcement-title">{{ $registrationTicket->title }}</h2>
                <p>{{ strip_tags($registrationTicket->body) }}</p>
                <a class="sh-public-announcement__action" href="{{ $announcementUrl }}">
                    {{ $announcementLabel }}
                    <span aria-hidden="true">←</span>
                </a>
            </div>
            <div class="sh-public-announcement__symbol" aria-hidden="true">✦</div>
        </div>
    </div>
</section>
@endif
