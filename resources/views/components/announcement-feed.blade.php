@if(($announcements ?? collect())->isNotEmpty())
<section class="sh-announcement-feed" aria-labelledby="sh-announcement-heading">
    <div class="sh-announcement-feed-head">
        <span class="sh-announcement-orbit" aria-hidden="true">✦</span>
        <div><span class="sh-announcement-eyebrow">اطلاعیه‌های آکادمی</span><h2 id="sh-announcement-heading">خبرهای مهم برای شما</h2></div>
        <span class="sh-announcement-count">{{ $announcements->count() }} اطلاعیه</span>
    </div>
    <div class="sh-announcement-list">
        @foreach($announcements as $announcement)
            <article class="sh-announcement-card sh-priority-{{ $announcement->priority ?? 'normal' }}">
                <div class="sh-announcement-card-mark" aria-hidden="true"><span>{{ ($announcement->priority ?? 'normal') === 'urgent' ? '!' : (($announcement->priority ?? 'normal') === 'important' ? '★' : '✦') }}</span></div>
                <div class="sh-announcement-card-content">
                    <div class="sh-announcement-meta">
                        <span>{{ ($announcement->priority ?? 'normal') === 'urgent' ? 'فوری' : ((($announcement->priority ?? 'normal') === 'important') ? 'مهم' : 'اطلاعیه') }}</span>
                        @if($announcement->published_at)<time datetime="{{ $announcement->published_at->toIso8601String() }}">{{ \App\Support\PersianDate::format($announcement->published_at, 'Y/m/d') }}</time>@endif
                        @if($announcement->is_read ?? false)<span class="sh-announcement-read-state">خوانده‌شده</span>@endif
                    </div>
                    <h3>{{ $announcement->title }}</h3>
                    <div class="sh-announcement-body">{!! $announcement->body !!}</div>
                    <div class="sh-announcement-actions">
                        @if($announcement->action_url && $announcement->action_label)
                            <a class="sh-announcement-action" href="{{ $announcement->action_url }}">{{ $announcement->action_label }} <span aria-hidden="true">←</span></a>
                        @endif
                        @unless($announcement->is_read ?? false)
                            <form method="POST" action="{{ route('announcements.read', $announcement) }}">@csrf @method('PATCH')<button class="sh-announcement-mark-read" type="submit">متوجه شدم</button></form>
                        @endunless
                    </div>
                </div>
            </article>
        @endforeach
    </div>
</section>
@endif
