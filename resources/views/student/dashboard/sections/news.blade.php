<section class="page" id="s-news"><div class="card"><div class="ch"><h2>اخبار مهم</h2><span class="mu">منتشرشده توسط آکادمی</span></div>
    @forelse($announcements as $announcement)
        <article class="news"><b>{{ $announcement->title }}</b>
            @if(filled($announcement->body))<p>{{ strip_tags((string) $announcement->body) }}</p>@endif
            <span class="mu">{{ \App\Support\PersianDate::format($announcement->published_at, 'l، d F Y ساعت H:i') }}</span>
        </article>
    @empty<div class="empty">در حال حاضر اطلاعیه‌ای برای نمایش وجود ندارد.</div>@endforelse
</div></section>
